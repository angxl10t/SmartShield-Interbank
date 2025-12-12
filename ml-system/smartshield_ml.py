"""
Sistema de Machine Learning para SmartShield Interbank
Detección avanzada de fraudes y análisis de comportamiento financiero
Independiente del sistema de IA basado en reglas existente
"""

import pandas as pd
import numpy as np
from sklearn.ensemble import IsolationForest, RandomForestClassifier
from sklearn.preprocessing import StandardScaler, LabelEncoder
from sklearn.model_selection import train_test_split
from sklearn.metrics import classification_report, confusion_matrix, roc_auc_score
import mysql.connector
from datetime import datetime, timedelta
import joblib
import warnings
import os
warnings.filterwarnings('ignore')

class SmartShieldML:
    def __init__(self, db_config):
        """
        Inicializar el sistema ML de SmartShield
        
        Args:
            db_config (dict): Configuración de conexión a MySQL
        """
        self.db_config = db_config
        self.scaler = StandardScaler()
        self.isolation_forest = IsolationForest(
            contamination=0.15,  # 15% de datos considerados anómalos
            random_state=42,
            n_estimators=100
        )
        self.fraud_classifier = RandomForestClassifier(
            n_estimators=200,
            max_depth=10,
            min_samples_split=5,
            min_samples_leaf=2,
            random_state=42
        )
        self.label_encoders = {}
        self.feature_names = []
        self.is_trained = False
        
    def connect_db(self):
        """Conectar a la base de datos MySQL"""
        return mysql.connector.connect(**self.db_config)
    
    def extract_ml_features(self):
        """
        Extraer características específicas para Machine Learning
        
        Returns:
            pd.DataFrame: Dataset con características procesadas para ML
        """
        conn = self.connect_db()
        
        # Query optimizada para ML - extrae datos de los últimos 90 días
        query = """
        SELECT 
            u.id_usuario,
            u.dni,
            
            -- Información de tarjeta
            t.id_tarjeta,
            t.tipo as tipo_tarjeta,
            t.estado as estado_tarjeta,
            t.saldo_disponible,
            t.limite_credito,
            
            -- Datos de transacciones
            tr.id_transaccion,
            tr.tipo as tipo_transaccion,
            tr.monto,
            tr.moneda,
            tr.fecha_hora,
            HOUR(tr.fecha_hora) as hora_transaccion,
            DAYOFWEEK(tr.fecha_hora) as dia_semana,
            DAYOFMONTH(tr.fecha_hora) as dia_mes,
            MONTH(tr.fecha_hora) as mes,
            tr.estado as estado_transaccion,
            tr.destino,
            tr.alias_destino,
            
            -- Configuración de seguridad
            cs.limite_diario,
            cs.limite_semanal,
            cs.limite_mensual,
            cs.gasto_semanal_actual,
            cs.gasto_mensual_actual,
            HOUR(cs.horario_inicio) as horario_inicio,
            HOUR(cs.horario_fin) as horario_fin,
            cs.modo_viaje,
            cs.notificar_email,
            cs.notificar_sms
            
        FROM usuarios u
        INNER JOIN tarjetas t ON u.id_usuario = t.id_usuario
        INNER JOIN transacciones tr ON u.id_usuario = tr.id_usuario
        LEFT JOIN config_seguridad_tarjeta cs ON t.id_tarjeta = cs.id_tarjeta
        
        WHERE tr.fecha_hora >= DATE_SUB(NOW(), INTERVAL 90 DAY)
          AND tr.estado = 'aplicada'
          AND t.estado = 'activa'
        
        ORDER BY tr.fecha_hora DESC
        """
        
        df = pd.read_sql(query, conn)
        conn.close()
        
        if len(df) == 0:
            print("⚠️ No hay datos suficientes para ML")
            return pd.DataFrame()
        
        return self.engineer_ml_features(df)
    
    def engineer_ml_features(self, df):
        """
        Crear características avanzadas para Machine Learning
        
        Args:
            df (pd.DataFrame): Dataset crudo
            
        Returns:
            pd.DataFrame: Dataset con características de ML
        """
        # Manejar valores nulos
        df = df.fillna({
            'limite_diario': 300.0,
            'limite_semanal': 1000.0,
            'limite_mensual': 3000.0,
            'gasto_semanal_actual': 0.0,
            'gasto_mensual_actual': 0.0,
            'horario_inicio': 8,
            'horario_fin': 22,
            'modo_viaje': 0,
            'notificar_email': 1,
            'notificar_sms': 0
        })
        
        # Convertir fecha_hora a datetime
        df['fecha_hora'] = pd.to_datetime(df['fecha_hora'])
        
        # === CARACTERÍSTICAS TEMPORALES ===
        df['es_fin_semana'] = df['dia_semana'].isin([1, 7]).astype(int)  # Domingo=1, Sábado=7
        df['es_horario_nocturno'] = ((df['hora_transaccion'] >= 22) | (df['hora_transaccion'] <= 6)).astype(int)
        df['es_madrugada'] = ((df['hora_transaccion'] >= 0) & (df['hora_transaccion'] <= 5)).astype(int)
        df['es_horario_laboral'] = ((df['hora_transaccion'] >= 9) & (df['hora_transaccion'] <= 17)).astype(int)
        
        # Horario fuera de configuración
        df['fuera_horario'] = ((df['hora_transaccion'] < df['horario_inicio']) | 
                              (df['hora_transaccion'] > df['horario_fin'])).astype(int)
        
        # === CARACTERÍSTICAS FINANCIERAS ===
        df['ratio_monto_limite_diario'] = df['monto'] / (df['limite_diario'] + 1)
        df['ratio_monto_limite_semanal'] = df['monto'] / (df['limite_semanal'] + 1)
        df['ratio_saldo_limite'] = df['saldo_disponible'] / (df['limite_credito'] + 1)
        df['ratio_gasto_semanal'] = df['gasto_semanal_actual'] / (df['limite_semanal'] + 1)
        df['ratio_gasto_mensual'] = df['gasto_mensual_actual'] / (df['limite_mensual'] + 1)
        
        # Booleanos de límites
        df['excede_limite_diario'] = (df['monto'] > df['limite_diario']).astype(int)
        df['excede_limite_semanal'] = (df['gasto_semanal_actual'] > df['limite_semanal']).astype(int)
        df['cerca_limite_semanal'] = (df['ratio_gasto_semanal'] >= 0.8).astype(int)
        
        # === CARACTERÍSTICAS DE COMPORTAMIENTO ===
        # Calcular estadísticas por usuario
        user_stats = df.groupby('id_usuario').agg({
            'monto': ['mean', 'std', 'min', 'max', 'count'],
            'hora_transaccion': ['mean', 'std'],
            'dia_semana': lambda x: x.mode().iloc[0] if len(x.mode()) > 0 else x.iloc[0]
        }).round(2)
        
        user_stats.columns = ['monto_promedio', 'monto_std', 'monto_min', 'monto_max', 'total_transacciones',
                             'hora_promedio', 'hora_std', 'dia_preferido']
        
        # Unir estadísticas al dataframe principal
        df = df.merge(user_stats, left_on='id_usuario', right_index=True, how='left')
        
        # Características derivadas del comportamiento
        df['monto_vs_promedio'] = df['monto'] / (df['monto_promedio'] + 1)
        df['monto_inusual'] = (df['monto'] > (df['monto_promedio'] + 2 * df['monto_std'])).astype(int)
        df['hora_inusual'] = (abs(df['hora_transaccion'] - df['hora_promedio']) > 3).astype(int)
        
        # === CARACTERÍSTICAS DE DESTINO ===
        # Frecuencia de destinos
        destino_counts = df['destino'].value_counts()
        df['destino_frecuencia'] = df['destino'].map(destino_counts)
        df['destino_nuevo'] = (df['destino_frecuencia'] == 1).astype(int)
        
        # === CARACTERÍSTICAS TEMPORALES AVANZADAS ===
        df['dias_desde_registro'] = (df['fecha_hora'] - df['fecha_hora'].min()).dt.days
        df['transacciones_por_dia'] = df.groupby(['id_usuario', df['fecha_hora'].dt.date])['id_transaccion'].transform('count')
        df['es_primera_transaccion_dia'] = df.groupby(['id_usuario', df['fecha_hora'].dt.date]).cumcount() == 0
        df['es_primera_transaccion_dia'] = df['es_primera_transaccion_dia'].astype(int)
        
        # === ENCODING DE VARIABLES CATEGÓRICAS ===
        categorical_cols = ['tipo_tarjeta', 'tipo_transaccion', 'moneda', 'estado_tarjeta']
        for col in categorical_cols:
            if col not in self.label_encoders:
                self.label_encoders[col] = LabelEncoder()
                df[col + '_encoded'] = self.label_encoders[col].fit_transform(df[col].astype(str))
            else:
                # Para nuevos datos, manejar categorías no vistas
                try:
                    df[col + '_encoded'] = self.label_encoders[col].transform(df[col].astype(str))
                except ValueError:
                    # Si hay categorías nuevas, asignar valor por defecto
                    df[col + '_encoded'] = 0
        
        # Seleccionar características finales para ML
        self.feature_names = [
            # Temporales
            'hora_transaccion', 'dia_semana', 'es_fin_semana', 'es_horario_nocturno',
            'es_madrugada', 'es_horario_laboral', 'fuera_horario',
            
            # Financieras
            'monto', 'ratio_monto_limite_diario', 'ratio_monto_limite_semanal',
            'ratio_saldo_limite', 'ratio_gasto_semanal', 'ratio_gasto_mensual',
            'excede_limite_diario', 'excede_limite_semanal', 'cerca_limite_semanal',
            
            # Comportamiento
            'monto_vs_promedio', 'monto_inusual', 'hora_inusual',
            'total_transacciones', 'transacciones_por_dia', 'destino_frecuencia',
            'destino_nuevo', 'es_primera_transaccion_dia',
            
            # Categóricas codificadas
            'tipo_tarjeta_encoded', 'tipo_transaccion_encoded', 'moneda_encoded'
        ]
        
        return df
    
    def create_fraud_labels(self, df):
        """
        Crear etiquetas de fraude basadas en reglas de negocio avanzadas
        
        Args:
            df (pd.DataFrame): Dataset procesado
            
        Returns:
            pd.Series: Etiquetas de fraude (1=fraude, 0=normal)
        """
        # Condiciones de fraude más sofisticadas
        fraud_conditions = (
            # Límites excedidos
            (df['excede_limite_diario'] == 1) |
            (df['excede_limite_semanal'] == 1) |
            
            # Horarios sospechosos
            (df['fuera_horario'] == 1) |
            (df['es_madrugada'] == 1) |
            
            # Montos inusuales
            (df['monto_inusual'] == 1) |
            (df['ratio_monto_limite_diario'] > 1.5) |
            
            # Comportamiento anómalo
            (df['hora_inusual'] == 1) |
            (df['transacciones_por_dia'] > 10) |
            
            # Destinos nuevos con montos altos
            ((df['destino_nuevo'] == 1) & (df['monto'] > df['monto_promedio'] * 2)) |
            
            # Fin de semana + horario nocturno + monto alto
            ((df['es_fin_semana'] == 1) & (df['es_horario_nocturno'] == 1) & 
             (df['ratio_monto_limite_diario'] > 0.5))
        )
        
        return fraud_conditions.astype(int)
    
    def train_ml_models(self, df):
        """
        Entrenar modelos de Machine Learning
        
        Args:
            df (pd.DataFrame): Dataset procesado
        """
        if len(df) < 10:
            print("❌ Datos insuficientes para entrenar ML (mínimo 10 registros)")
            return False
        
        print(f"🧠 Entrenando modelos ML con {len(df)} registros...")
        
        # Preparar características
        X = df[self.feature_names].fillna(0)
        
        # Crear etiquetas de fraude
        y = self.create_fraud_labels(df)
        
        print(f"📊 Distribución de clases: Normal={sum(y==0)}, Fraude={sum(y==1)}")
        
        # Dividir datos
        if len(np.unique(y)) > 1:  # Solo si hay ambas clases
            X_train, X_test, y_train, y_test = train_test_split(
                X, y, test_size=0.2, random_state=42, stratify=y
            )
        else:
            X_train, X_test, y_train, y_test = train_test_split(
                X, y, test_size=0.2, random_state=42
            )
        
        # Normalizar características
        X_train_scaled = self.scaler.fit_transform(X_train)
        X_test_scaled = self.scaler.transform(X_test)
        
        # Entrenar Isolation Forest (detección de anomalías)
        print("🔍 Entrenando Isolation Forest...")
        self.isolation_forest.fit(X_train_scaled)
        
        # Entrenar Random Forest (clasificación supervisada)
        print("🌳 Entrenando Random Forest Classifier...")
        self.fraud_classifier.fit(X_train_scaled, y_train)
        
        # Evaluar modelos
        if len(X_test) > 0:
            # Predicciones
            anomaly_pred = self.isolation_forest.predict(X_test_scaled)
            fraud_pred = self.fraud_classifier.predict(X_test_scaled)
            fraud_proba = self.fraud_classifier.predict_proba(X_test_scaled)
            
            print("\n📈 Evaluación Isolation Forest:")
            print(f"Anomalías detectadas: {sum(anomaly_pred == -1)}/{len(anomaly_pred)}")
            
            print("\n📈 Evaluación Random Forest:")
            print(classification_report(y_test, fraud_pred, zero_division=0))
            
            if len(np.unique(y_test)) > 1 and fraud_proba.shape[1] > 1:
                auc_score = roc_auc_score(y_test, fraud_proba[:, 1])
                print(f"AUC Score: {auc_score:.3f}")
        
        self.is_trained = True
        print("✅ Modelos ML entrenados exitosamente")
        return True
    
    def predict_fraud_ml(self, transaction_data):
        """
        Predecir riesgo de fraude usando Machine Learning
        
        Args:
            transaction_data (dict): Datos de la transacción
            
        Returns:
            dict: Resultado de predicción ML
        """
        if not self.is_trained:
            return self.get_fallback_prediction(transaction_data)
        
        try:
            # Convertir a DataFrame
            df = pd.DataFrame([transaction_data])
            df_processed = self.engineer_ml_features(df)
            
            if len(df_processed) == 0:
                return self.get_fallback_prediction(transaction_data)
            
            # Seleccionar características
            X = df_processed[self.feature_names].fillna(0)
            X_scaled = self.scaler.transform(X)
            
            # Predicciones ML
            anomaly_score = self.isolation_forest.decision_function(X_scaled)[0]
            is_anomaly = self.isolation_forest.predict(X_scaled)[0] == -1
            
            fraud_probability = self.fraud_classifier.predict_proba(X_scaled)[0][1]
            is_fraud_ml = self.fraud_classifier.predict(X_scaled)[0]
            
            # Calcular score de riesgo ML combinado (0-100)
            ml_risk_score = min(100, max(0, 
                (fraud_probability * 60) +  # 60% peso clasificador
                (40 if is_anomaly else 0)   # 40% peso detector anomalías
            ))
            
            # Generar recomendaciones ML
            ml_recommendations = self.generate_ml_recommendations(
                ml_risk_score, df_processed.iloc[0], is_anomaly, fraud_probability
            )
            
            return {
                'ml_risk_score': round(ml_risk_score, 2),
                'fraud_probability': round(fraud_probability * 100, 2),
                'is_anomaly': bool(is_anomaly),
                'is_fraud_ml': bool(is_fraud_ml),
                'anomaly_score': round(anomaly_score, 4),
                'ml_recommendations': ml_recommendations,
                'ml_model_used': True,
                'timestamp': datetime.now().isoformat()
            }
            
        except Exception as e:
            print(f"❌ Error en predicción ML: {e}")
            return self.get_fallback_prediction(transaction_data)
    
    def generate_ml_recommendations(self, risk_score, transaction_data, is_anomaly, fraud_prob):
        """
        Generar recomendaciones específicas de Machine Learning
        
        Args:
            risk_score (float): Score de riesgo ML
            transaction_data (pd.Series): Datos de la transacción
            is_anomaly (bool): Si es anomalía detectada
            fraud_prob (float): Probabilidad de fraude
            
        Returns:
            list: Lista de recomendaciones ML
        """
        recommendations = []
        
        # Recomendaciones basadas en score ML
        if risk_score >= 80:
            recommendations.extend([
                "🤖 ML CRÍTICO: Patrón de fraude detectado por IA",
                "🔴 Bloquear tarjeta inmediatamente",
                "📞 Verificación telefónica obligatoria"
            ])
        elif risk_score >= 60:
            recommendations.extend([
                "🤖 ML ALTO: Comportamiento anómalo detectado",
                "🟡 Solicitar autenticación adicional",
                "📊 Revisar patrones de transacciones recientes"
            ])
        elif risk_score >= 30:
            recommendations.extend([
                "🤖 ML MODERADO: Ligera desviación del patrón normal",
                "📈 Monitorear próximas transacciones"
            ])
        else:
            recommendations.append("🤖 ML NORMAL: Patrón de comportamiento típico")
        
        # Recomendaciones específicas ML
        if is_anomaly:
            recommendations.append("🔍 Anomalía detectada por Isolation Forest")
        
        if fraud_prob > 0.7:
            recommendations.append(f"⚠️ Alta probabilidad de fraude: {fraud_prob*100:.1f}%")
        
        # Análisis de características específicas
        if hasattr(transaction_data, 'monto_inusual') and transaction_data.get('monto_inusual', 0) == 1:
            recommendations.append("💰 Monto significativamente superior al promedio")
        
        if hasattr(transaction_data, 'hora_inusual') and transaction_data.get('hora_inusual', 0) == 1:
            recommendations.append("⏰ Horario inusual para este usuario")
        
        if hasattr(transaction_data, 'destino_nuevo') and transaction_data.get('destino_nuevo', 0) == 1:
            recommendations.append("🎯 Primer uso de este destinatario")
        
        return recommendations
    
    def get_fallback_prediction(self, transaction_data):
        """
        Predicción de respaldo cuando ML no está disponible
        """
        return {
            'ml_risk_score': 0,
            'fraud_probability': 0,
            'is_anomaly': False,
            'is_fraud_ml': False,
            'anomaly_score': 0,
            'ml_recommendations': ["🤖 ML no disponible - usando reglas básicas"],
            'ml_model_used': False,
            'timestamp': datetime.now().isoformat()
        }
    
    def save_ml_models(self, path='ml-models/'):
        """Guardar modelos ML entrenados"""
        os.makedirs(path, exist_ok=True)
        
        joblib.dump(self.scaler, f'{path}ml_scaler.pkl')
        joblib.dump(self.isolation_forest, f'{path}ml_isolation_forest.pkl')
        joblib.dump(self.fraud_classifier, f'{path}ml_fraud_classifier.pkl')
        joblib.dump(self.label_encoders, f'{path}ml_label_encoders.pkl')
        joblib.dump(self.feature_names, f'{path}ml_feature_names.pkl')
        
        print(f"✅ Modelos ML guardados en {path}")
    
    def load_ml_models(self, path='ml-models/'):
        """Cargar modelos ML pre-entrenados"""
        try:
            self.scaler = joblib.load(f'{path}ml_scaler.pkl')
            self.isolation_forest = joblib.load(f'{path}ml_isolation_forest.pkl')
            self.fraud_classifier = joblib.load(f'{path}ml_fraud_classifier.pkl')
            self.label_encoders = joblib.load(f'{path}ml_label_encoders.pkl')
            self.feature_names = joblib.load(f'{path}ml_feature_names.pkl')
            self.is_trained = True
            
            print(f"✅ Modelos ML cargados desde {path}")
            return True
        except Exception as e:
            print(f"❌ Error cargando modelos ML: {e}")
            return False

# Función principal de entrenamiento
def train_smartshield_ml():
    """Entrenar el sistema ML de SmartShield"""
    
    # Configuración de base de datos
    db_config = {
        'host': '127.0.0.1',
        'user': 'root',
        'password': '',  # Cambiar si tienes contraseña
        'database': 'interbank',
        'port': 3306
    }
    
    print("🤖 Iniciando entrenamiento SmartShield ML...")
    
    # Inicializar sistema ML
    ml_system = SmartShieldML(db_config)
    
    # Extraer datos
    print("📊 Extrayendo datos para ML...")
    df = ml_system.extract_ml_features()
    
    if len(df) == 0:
        print("❌ No hay datos suficientes para entrenar")
        return False
    
    print(f"✅ Datos extraídos: {len(df)} registros")
    
    # Entrenar modelos
    success = ml_system.train_ml_models(df)
    
    if success:
        # Guardar modelos
        ml_system.save_ml_models()
        print("\n🎯 Sistema ML SmartShield entrenado y guardado!")
        return True
    else:
        print("\n❌ Error en el entrenamiento ML")
        return False

if __name__ == "__main__":
    train_smartshield_ml()