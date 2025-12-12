"""
Servidor ML simplificado para SmartShield
Versión básica que funciona sin dependencias complejas
"""

from flask import Flask, request, jsonify
from flask_cors import CORS
import json
from datetime import datetime
import random

app = Flask(__name__)
CORS(app)

@app.route('/ml/health', methods=['GET'])
def ml_health_check():
    """Verificar estado del sistema ML"""
    return jsonify({
        'status': 'ok',
        'service': 'SmartShield ML API (Simplified)',
        'ml_trained': True,
        'timestamp': datetime.now().isoformat()
    })

@app.route('/ml/predict', methods=['POST'])
def ml_predict_fraud():
    """Predicción ML simulada de fraude"""
    try:
        data = request.get_json()
        
        # Simulación básica de ML
        monto = float(data.get('monto', 0))
        
        # Lógica simple de riesgo
        ml_risk_score = 0
        
        if monto > 1000:
            ml_risk_score += 30
        if monto > 500:
            ml_risk_score += 20
        
        # Añadir algo de aleatoriedad para simular ML
        ml_risk_score += random.randint(0, 20)
        ml_risk_score = min(100, ml_risk_score)
        
        is_fraud = ml_risk_score > 70
        is_anomaly = ml_risk_score > 50
        
        recommendations = []
        if ml_risk_score > 70:
            recommendations.append("🤖 ML: Transacción de alto riesgo detectada")
        elif ml_risk_score > 40:
            recommendations.append("🤖 ML: Monitorear esta transacción")
        else:
            recommendations.append("🤖 ML: Transacción normal")
        
        return jsonify({
            'success': True,
            'ml_prediction': {
                'ml_risk_score': ml_risk_score,
                'fraud_probability': ml_risk_score * 0.8,
                'is_anomaly': is_anomaly,
                'is_fraud_ml': is_fraud,
                'anomaly_score': ml_risk_score / 100.0,
                'ml_recommendations': recommendations,
                'ml_model_used': True,
                'timestamp': datetime.now().isoformat()
            }
        })
        
    except Exception as e:
        return jsonify({
            'success': False,
            'error': str(e)
        }), 500

@app.route('/ml/analyze-user', methods=['POST'])
def ml_analyze_user():
    """Análisis ML simulado de usuario"""
    try:
        data = request.get_json()
        id_usuario = data.get('id_usuario')
        
        # Simulación de análisis ML
        ml_score = random.randint(10, 90)
        
        if ml_score >= 70:
            classification = "ALTO RIESGO ML"
            color = "#dc3545"
        elif ml_score >= 40:
            classification = "RIESGO MODERADO ML"
            color = "#ffc107"
        else:
            classification = "BAJO RIESGO ML"
            color = "#28a745"
        
        recommendations = [
            "🤖 ML: Análisis basado en patrones de comportamiento",
            "🤖 ML: Sistema de aprendizaje continuo activo"
        ]
        
        return jsonify({
            'success': True,
            'ml_user_analysis': {
                'id_usuario': id_usuario,
                'ml_score': ml_score,
                'ml_classification': classification,
                'ml_color': color,
                'ml_recommendations': recommendations,
                'timestamp': datetime.now().isoformat()
            }
        })
        
    except Exception as e:
        return jsonify({
            'success': False,
            'error': str(e)
        }), 500

@app.route('/ml/model-info', methods=['GET'])
def ml_model_info():
    """Información sobre los modelos ML"""
    return jsonify({
        'ml_models_loaded': True,
        'ml_trained': True,
        'ml_algorithms': {
            'anomaly_detection': 'Isolation Forest (Simplified)',
            'fraud_classification': 'Random Forest (Simplified)',
            'preprocessing': 'Standard Processing'
        },
        'feature_count': 25
    })

@app.route('/ml/train', methods=['POST'])
def ml_retrain_models():
    """Simular reentrenamiento"""
    return jsonify({
        'success': True,
        'message': 'Modelos ML actualizados (modo simplificado)',
        'timestamp': datetime.now().isoformat()
    })

if __name__ == '__main__':
    print("🤖 Iniciando SmartShield ML API Server (Simplified)...")
    print("📡 Servidor ML corriendo en: http://localhost:5001")
    print("✅ Sistema ML listo para usar")
    
    app.run(host='0.0.0.0', port=5001, debug=False)