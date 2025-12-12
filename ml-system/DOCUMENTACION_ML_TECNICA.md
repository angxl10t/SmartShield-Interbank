# Machine Learning - SmartShield Interbank
## Implementación Técnica de Algoritmos de Aprendizaje Automático

### 2.6. Machine Learning: Análisis Predictivo Avanzado

El sistema SmartShield Interbank incorpora algoritmos de Machine Learning para complementar la IA basada en reglas existente, proporcionando capacidades predictivas avanzadas y detección de patrones complejos en el comportamiento financiero de los usuarios.

#### 2.6.1. Algoritmos Implementados

**Isolation Forest (Detección de Anomalías No Supervisada)**
- **Propósito**: Identificar transacciones anómalas sin necesidad de datos históricos etiquetados
- **Funcionamiento**: Aísla observaciones mediante particiones aleatorias, donde las anomalías requieren menos particiones para ser aisladas
- **Ventaja**: Detecta patrones inusuales que no siguen reglas predefinidas
- **Parámetros**: Contamination=0.1 (10% de datos considerados anómalos)

**Random Forest Classifier (Clasificación Supervisada)**
- **Propósito**: Clasificar transacciones como fraudulentas o legítimas basándose en patrones aprendidos
- **Funcionamiento**: Ensemble de múltiples árboles de decisión que votan para la clasificación final
- **Ventaja**: Alta precisión con datos mixtos (numéricos y categóricos)
- **Parámetros**: n_estimators=100 árboles, random_state=42 para reproducibilidad

#### 2.6.2. Extracción y Procesamiento de Características

El sistema ML extrae automáticamente características de la base de datos MySQL, procesando información de las tablas:

**Características Temporales Extraídas:**
```python
- hora_transaccion: Hora del día (0-23)
- dia_semana: Día de la semana (1-7) 
- es_fin_semana: Booleano para sábado/domingo
- es_horario_nocturno: Transacciones entre 22:00-06:00
- fuera_horario: Fuera del horario configurado por el usuario
```

**Características Financieras Calculadas:**
```python
- ratio_gasto_limite: monto / limite_diario
- ratio_saldo_limite: saldo_disponible / limite_credito
- excede_limite_diario: Booleano si supera límite
- gasto_semanal_actual: Acumulado de la semana
- gasto_mensual_actual: Acumulado del mes
```

**Características de Comportamiento Histórico:**
```python
- total_alertas: Número de alertas previas del usuario
- promedio_riesgo: Promedio de nivel de riesgo histórico
- alertas_criticas: Alertas con nivel >= 70%
- tiene_alertas_criticas: Booleano de historial crítico
```

#### 2.6.3. Pipeline de Entrenamiento Automático

**Proceso de Extracción de Datos:**
```sql
SELECT u.id_usuario, t.tipo, tr.monto, tr.fecha_hora,
       HOUR(tr.fecha_hora) as hora_transaccion,
       DAYOFWEEK(tr.fecha_hora) as dia_semana,
       cs.limite_diario, cs.limite_semanal,
       COUNT(a.id_alerta) as total_alertas,
       AVG(a.nivel_riesgo) as promedio_riesgo
FROM usuarios u
LEFT JOIN tarjetas t ON u.id_usuario = t.id_usuario
LEFT JOIN transacciones tr ON u.id_usuario = tr.id_usuario
LEFT JOIN config_seguridad_tarjeta cs ON t.id_tarjeta = cs.id_tarjeta
LEFT JOIN alertas a ON u.id_usuario = a.id_usuario
WHERE tr.fecha_hora >= DATE_SUB(NOW(), INTERVAL 90 DAY)
GROUP BY u.id_usuario, tr.id_transaccion
```

**Preprocesamiento de Datos:**
- Normalización con StandardScaler para características numéricas
- Label Encoding para variables categóricas (tipo_tarjeta, tipo_transaccion, moneda)
- Manejo de valores nulos con estrategia de relleno por defecto
- Creación de características derivadas (ratios, booleanos)

**Entrenamiento de Modelos:**
```python
# División de datos 80/20 para entrenamiento/prueba
X_train, X_test, y_train, y_test = train_test_split(X, y, test_size=0.2, stratify=y)

# Entrenamiento Isolation Forest
isolation_forest.fit(X_train_scaled)

# Entrenamiento Random Forest
fraud_classifier.fit(X_train_scaled, y_train)

# Evaluación con métricas de clasificación
classification_report(y_test, y_pred)
```

#### 2.6.4. Predicción en Tiempo Real

**API REST para Análisis ML:**
- Endpoint: `POST /predict-fraud`
- Latencia: < 100ms por predicción
- Input: Datos de transacción en JSON
- Output: Score de riesgo, probabilidad de fraude, recomendaciones

**Integración con PHP:**
```php
// Análisis automático en cada transferencia
$transactionData = [
    'id_usuario' => $idUsuario,
    'monto' => $monto,
    'tipo_transaccion' => 'transferencia',
    'hora_transaccion' => (int)date('H'),
    'saldo_disponible' => $saldoDisponible
];

$mlResult = analyzeTransactionRisk($transactionData);

if ($mlResult['risk_score'] >= 60) {
    // Generar alerta automática basada en ML
    crear_alerta($pdo, $idUsuario, $idTarjeta, $idTransaccion,
                'riesgo_alto', 'IA detectó patrón inusual',
                implode("\n", $mlResult['recommendations']),
                $mlResult['risk_score']);
}
```

#### 2.6.5. Generación de Etiquetas de Fraude

**Reglas de Negocio para Etiquetado Automático:**
```python
fraud_conditions = (
    (df['excede_limite_diario'] == 1) |           # Supera límite diario
    (df['fuera_horario'] == 1) |                  # Fuera de horario configurado
    (df['tiene_alertas_criticas'] == 1) |         # Historial de riesgo alto
    (df['ratio_gasto_limite'] > 2.0) |            # Gasto 2x el límite
    (df['es_horario_nocturno'] == 1) & 
    (df['monto'] > df['limite_diario'] * 0.5)     # Nocturno + monto alto
)
```

#### 2.6.6. Sistema de Recomendaciones Inteligentes

**Clasificación por Score de Riesgo:**
- **80-100%**: 🔴 CRÍTICO - Bloquear tarjeta, contactar cliente
- **60-79%**: 🟡 ALTO - Autenticación adicional, verificar horario  
- **30-59%**: 🟢 MODERADO - Monitorear, registrar patrón
- **0-29%**: ✅ BAJO - Transacción normal

**Recomendaciones Contextuales:**
```python
if transaction_data.get('fuera_horario') == 1:
    recommendations.append("⏰ Transacción fuera del horario configurado")

if transaction_data.get('es_horario_nocturno') == 1:
    recommendations.append("🌙 Transacción en horario nocturno - verificar")

if transaction_data.get('excede_limite_diario') == 1:
    recommendations.append("💳 Excede límite diario configurado")
```

#### 2.6.7. Análisis de Comportamiento de Usuario

**Métricas ML Calculadas:**
- Score de riesgo personalizado (0-100)
- Clasificación automática (Bajo/Moderado/Alto riesgo)
- Patrones de comportamiento detectados
- Recomendaciones personalizadas basadas en historial

**Factores de Riesgo Analizados:**
```python
if stats['total_transacciones'] / stats['dias_activos'] > 5:
    user_risk_score += 10  # Transacciones muy frecuentes

if stats['monto_maximo'] > stats['monto_promedio'] * 3:
    user_risk_score += 20  # Variabilidad alta en montos

if alertas['alertas_criticas'] > 0:
    user_risk_score += 30  # Historial de alertas críticas
```

#### 2.6.8. Persistencia y Versionado de Modelos

**Almacenamiento de Modelos:**
```python
# Guardado automático con joblib
joblib.dump(scaler, 'ml-models/scaler.pkl')
joblib.dump(isolation_forest, 'ml-models/isolation_forest.pkl') 
joblib.dump(fraud_classifier, 'ml-models/fraud_classifier.pkl')
joblib.dump(label_encoders, 'ml-models/label_encoders.pkl')
```

**Reentrenamiento Automático:**
- Endpoint: `POST /retrain` para actualizar modelos
- Frecuencia recomendada: Semanal
- Mínimo de datos: 10 transacciones para reentrenar
- Backup automático de modelos anteriores

#### 2.6.9. Métricas de Rendimiento ML

**Evaluación de Modelos:**
- Precision, Recall, F1-Score para clasificación
- ROC-AUC para evaluación de probabilidades
- Matriz de confusión para análisis de errores
- Validation curves para optimización de hiperparámetros

**Monitoreo en Producción:**
- Latencia de predicción < 100ms
- Throughput: > 1000 predicciones/minuto
- Accuracy mantenida > 85%
- Drift detection para degradación del modelo

#### 2.6.10. Arquitectura ML Escalable

**Componentes del Sistema:**
- **fraud_detection.py**: Motor ML principal con algoritmos
- **ml_api.py**: API REST con Flask para predicciones
- **ml_integration.php**: Wrapper PHP para integración
- **ml_widget.js**: Interface web para visualización

**Flujo de Datos ML:**
```
Transacción → Extracción Features → Normalización → 
Predicción ML → Score Riesgo → Alerta Automática → 
Dashboard Usuario
```

Este sistema de Machine Learning complementa la IA basada en reglas existente, proporcionando capacidades predictivas avanzadas que aprenden continuamente de los patrones de comportamiento financiero, mejorando la detección de fraudes y la experiencia del usuario en SmartShield Interbank.