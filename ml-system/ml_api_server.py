"""
API REST para Machine Learning de SmartShield
Servidor independiente para análisis ML de fraudes
"""

from flask import Flask, request, jsonify
from flask_cors import CORS
from smartshield_ml import SmartShieldML
import os
import json
from datetime import datetime
import logging

# Configurar logging
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

app = Flask(__name__)
CORS(app)

# Configuración de base de datos
DB_CONFIG = {
    'host': 'localhost',
    'user': 'root',
    'password': '',  # Cambiar si tienes contraseña en MySQL
    'database': 'interbank',
    'port': 3306
}

# Inicializar sistema ML
ml_system = SmartShieldML(DB_CONFIG)

# Cargar modelos al iniciar
try:
    if ml_system.load_ml_models():
        logger.info("✅ Modelos ML cargados al iniciar")
    else:
        logger.warning("⚠️ No se encontraron modelos ML pre-entrenados")
except Exception as e:
    logger.error(f"❌ Error cargando modelos: {e}")

@app.route('/ml/health', methods=['GET'])
def ml_health_check():
    """Verificar estado del sistema ML"""
    return jsonify({
        'status': 'ok',
        'service': 'SmartShield ML API',
        'ml_trained': ml_system.is_trained,
        'timestamp': datetime.now().isoformat()
    })

@app.route('/ml/predict', methods=['POST'])
def ml_predict_fraud():
    """
    Predicción ML de fraude para una transacción
    
    Body JSON:
    {
        "id_usuario": 1,
        "monto": 500.0,
        "tipo_transaccion": "transferencia",
        "moneda": "PEN",
        "destino": "Netflix",
        "saldo_disponible": 1000.0,
        "limite_diario": 300.0,
        "limite_semanal": 1000.0
    }
    """
    try:
        data = request.get_json()
        
        # Validar datos requeridos
        required_fields = ['id_usuario', 'monto']
        for field in required_fields:
            if field not in data:
                return jsonify({'error': f'Campo requerido: {field}'}), 400
        
        # Preparar datos para ML con valores por defecto
        transaction_data = {
            'id_usuario': data['id_usuario'],
            'monto': float(data['monto']),
            'tipo_transaccion': data.get('tipo_transaccion', 'transferencia'),
            'moneda': data.get('moneda', 'PEN'),
            'destino': data.get('destino', 'Transferencia'),
            'alias_destino': data.get('alias_destino', ''),
            'fecha_hora': datetime.now(),
            'hora_transaccion': datetime.now().hour,
            'dia_semana': datetime.now().weekday() + 1,
            'saldo_disponible': float(data.get('saldo_disponible', 1000.0)),
            'limite_credito': float(data.get('limite_credito', 5000.0)),
            'limite_diario': float(data.get('limite_diario', 300.0)),
            'limite_semanal': float(data.get('limite_semanal', 1000.0)),
            'limite_mensual': float(data.get('limite_mensual', 3000.0)),
            'gasto_semanal_actual': float(data.get('gasto_semanal_actual', 0.0)),
            'gasto_mensual_actual': float(data.get('gasto_mensual_actual', 0.0)),
            'horario_inicio': int(data.get('horario_inicio', 8)),
            'horario_fin': int(data.get('horario_fin', 22)),
            'tipo_tarjeta': data.get('tipo_tarjeta', 'credito'),
            'estado_tarjeta': data.get('estado_tarjeta', 'activa')
        }
        
        # Realizar predicción ML
        ml_result = ml_system.predict_fraud_ml(transaction_data)
        
        return jsonify({
            'success': True,
            'ml_prediction': ml_result,
            'input_data': {k: str(v) for k, v in transaction_data.items()},
            'api_version': '1.0'
        })
        
    except Exception as e:
        logger.error(f"Error en predicción ML: {e}")
        return jsonify({
            'success': False,
            'error': str(e)
        }), 500

@app.route('/ml/train', methods=['POST'])
def ml_retrain_models():
    """Reentrenar modelos ML con datos actualizados"""
    try:
        logger.info("🔄 Iniciando reentrenamiento ML...")
        
        # Extraer datos actualizados
        df = ml_system.extract_ml_features()
        
        if len(df) < 10:
            return jsonify({
                'success': False,
                'error': 'Datos insuficientes para reentrenar ML (mínimo 10 registros)',
                'records_found': len(df)
            }), 400
        
        # Reentrenar modelos
        success = ml_system.train_ml_models(df)
        
        if success:
            ml_system.save_ml_models()
            
            return jsonify({
                'success': True,
                'message': f'Modelos ML reentrenados exitosamente',
                'records_used': len(df),
                'timestamp': datetime.now().isoformat()
            })
        else:
            return jsonify({
                'success': False,
                'error': 'Error durante el reentrenamiento'
            }), 500
        
    except Exception as e:
        logger.error(f"Error reentrenando ML: {e}")
        return jsonify({
            'success': False,
            'error': str(e)
        }), 500

@app.route('/ml/analyze-user', methods=['POST'])
def ml_analyze_user():
    """
    Análisis ML completo de un usuario
    
    Body JSON:
    {
        "id_usuario": 1,
        "dias_analisis": 30
    }
    """
    try:
        data = request.get_json()
        id_usuario = data.get('id_usuario')
        dias_analisis = data.get('dias_analisis', 30)
        
        if not id_usuario:
            return jsonify({'error': 'id_usuario requerido'}), 400
        
        # Obtener datos del usuario para análisis ML
        conn = ml_system.connect_db()
        cursor = conn.cursor(dictionary=True)
        
        # Estadísticas ML del usuario
        query_ml_stats = """
        SELECT 
            COUNT(*) as total_transacciones,
            AVG(monto) as monto_promedio,
            STDDEV(monto) as monto_desviacion,
            MAX(monto) as monto_maximo,
            MIN(monto) as monto_minimo,
            SUM(monto) as gasto_total,
            AVG(HOUR(fecha_hora)) as hora_promedio,
            STDDEV(HOUR(fecha_hora)) as hora_desviacion,
            COUNT(DISTINCT DATE(fecha_hora)) as dias_activos,
            COUNT(DISTINCT destino) as destinos_unicos,
            COUNT(CASE WHEN HOUR(fecha_hora) BETWEEN 22 AND 23 OR HOUR(fecha_hora) BETWEEN 0 AND 6 THEN 1 END) as transacciones_nocturnas,
            COUNT(CASE WHEN DAYOFWEEK(fecha_hora) IN (1,7) THEN 1 END) as transacciones_fin_semana
        FROM transacciones 
        WHERE id_usuario = %s 
        AND fecha_hora >= DATE_SUB(NOW(), INTERVAL %s DAY)
        """
        
        cursor.execute(query_ml_stats, (id_usuario, dias_analisis))
        ml_stats = cursor.fetchone()
        
        # Patrones de comportamiento ML
        query_patterns = """
        SELECT 
            HOUR(fecha_hora) as hora,
            COUNT(*) as frecuencia,
            AVG(monto) as monto_promedio_hora
        FROM transacciones 
        WHERE id_usuario = %s 
        AND fecha_hora >= DATE_SUB(NOW(), INTERVAL %s DAY)
        GROUP BY HOUR(fecha_hora)
        ORDER BY frecuencia DESC
        """
        
        cursor.execute(query_patterns, (id_usuario, dias_analisis))
        patterns = cursor.fetchall()
        
        conn.close()
        
        # Calcular métricas ML avanzadas
        ml_risk_factors = {
            'variabilidad_monto': (ml_stats['monto_desviacion'] or 0) / (ml_stats['monto_promedio'] or 1),
            'variabilidad_horario': (ml_stats['hora_desviacion'] or 0),
            'frecuencia_nocturna': (ml_stats['transacciones_nocturnas'] or 0) / (ml_stats['total_transacciones'] or 1),
            'frecuencia_fin_semana': (ml_stats['transacciones_fin_semana'] or 0) / (ml_stats['total_transacciones'] or 1),
            'diversidad_destinos': (ml_stats['destinos_unicos'] or 0) / (ml_stats['total_transacciones'] or 1),
            'actividad_diaria': (ml_stats['total_transacciones'] or 0) / (ml_stats['dias_activos'] or 1)
        }
        
        # Score ML del usuario
        ml_user_score = 0
        
        # Factores de riesgo ML
        if ml_risk_factors['variabilidad_monto'] > 2.0:
            ml_user_score += 25  # Montos muy variables
        
        if ml_risk_factors['variabilidad_horario'] > 4.0:
            ml_user_score += 20  # Horarios muy variables
        
        if ml_risk_factors['frecuencia_nocturna'] > 0.3:
            ml_user_score += 30  # Muchas transacciones nocturnas
        
        if ml_risk_factors['actividad_diaria'] > 5:
            ml_user_score += 15  # Muy activo
        
        if ml_risk_factors['diversidad_destinos'] < 0.3:
            ml_user_score += 10  # Pocos destinos (patrón repetitivo)
        
        # Clasificación ML
        if ml_user_score >= 70:
            ml_classification = "ALTO RIESGO ML"
            ml_color = "#dc3545"
        elif ml_user_score >= 40:
            ml_classification = "RIESGO MODERADO ML"
            ml_color = "#ffc107"
        else:
            ml_classification = "BAJO RIESGO ML"
            ml_color = "#28a745"
        
        # Recomendaciones ML específicas
        ml_recommendations = []
        
        if ml_risk_factors['variabilidad_monto'] > 2.0:
            ml_recommendations.append("🤖 ML: Montos muy variables - posible comportamiento anómalo")
        
        if ml_risk_factors['frecuencia_nocturna'] > 0.2:
            ml_recommendations.append("🤖 ML: Alto porcentaje de transacciones nocturnas")
        
        if ml_risk_factors['actividad_diaria'] > 5:
            ml_recommendations.append("🤖 ML: Actividad transaccional muy alta")
        
        if not ml_recommendations:
            ml_recommendations.append("🤖 ML: Patrón de comportamiento estable")
        
        return jsonify({
            'success': True,
            'ml_user_analysis': {
                'id_usuario': id_usuario,
                'periodo_analisis': f'{dias_analisis} días',
                'ml_score': ml_user_score,
                'ml_classification': ml_classification,
                'ml_color': ml_color,
                'ml_statistics': ml_stats,
                'ml_risk_factors': ml_risk_factors,
                'ml_patterns': patterns[:5],  # Top 5 horas más frecuentes
                'ml_recommendations': ml_recommendations,
                'timestamp': datetime.now().isoformat()
            }
        })
        
    except Exception as e:
        logger.error(f"Error en análisis ML de usuario: {e}")
        return jsonify({
            'success': False,
            'error': str(e)
        }), 500

@app.route('/ml/model-info', methods=['GET'])
def ml_model_info():
    """Información sobre los modelos ML"""
    try:
        model_path = 'ml-models/'
        models_exist = all(os.path.exists(f'{model_path}{f}') for f in [
            'ml_scaler.pkl', 'ml_isolation_forest.pkl', 
            'ml_fraud_classifier.pkl', 'ml_label_encoders.pkl'
        ])
        
        return jsonify({
            'ml_models_loaded': models_exist,
            'ml_trained': ml_system.is_trained,
            'model_path': model_path,
            'ml_algorithms': {
                'anomaly_detection': 'Isolation Forest (contamination=0.15)',
                'fraud_classification': 'Random Forest (n_estimators=200)',
                'preprocessing': 'Standard Scaler + Label Encoders'
            },
            'ml_features': ml_system.feature_names if ml_system.feature_names else [],
            'feature_count': len(ml_system.feature_names) if ml_system.feature_names else 0
        })
        
    except Exception as e:
        return jsonify({
            'success': False,
            'error': str(e)
        }), 500

@app.route('/ml/feature-importance', methods=['GET'])
def ml_feature_importance():
    """Obtener importancia de características ML"""
    try:
        if not ml_system.is_trained:
            return jsonify({
                'success': False,
                'error': 'Modelos ML no entrenados'
            }), 400
        
        # Obtener importancia de características del Random Forest
        if hasattr(ml_system.fraud_classifier, 'feature_importances_'):
            importances = ml_system.fraud_classifier.feature_importances_
            feature_importance = dict(zip(ml_system.feature_names, importances))
            
            # Ordenar por importancia
            sorted_features = sorted(feature_importance.items(), 
                                   key=lambda x: x[1], reverse=True)
            
            return jsonify({
                'success': True,
                'feature_importance': dict(sorted_features),
                'top_features': sorted_features[:10]  # Top 10
            })
        else:
            return jsonify({
                'success': False,
                'error': 'Importancia de características no disponible'
            }), 400
        
    except Exception as e:
        return jsonify({
            'success': False,
            'error': str(e)
        }), 500

if __name__ == '__main__':
    print("🤖 Iniciando SmartShield ML API Server...")
    print("📡 Endpoints ML disponibles:")
    print("  GET  /ml/health - Estado del sistema ML")
    print("  POST /ml/predict - Predicción ML de fraude")
    print("  POST /ml/train - Reentrenar modelos ML")
    print("  POST /ml/analyze-user - Análisis ML de usuario")
    print("  GET  /ml/model-info - Información de modelos ML")
    print("  GET  /ml/feature-importance - Importancia de características")
    
    app.run(host='0.0.0.0', port=5001, debug=True)