<?php
/**
 * Integración Machine Learning para SmartShield
 * Sistema independiente de ML que complementa la IA basada en reglas
 */

class SmartShieldML {
    private $ml_api_url;
    private $timeout;
    private $api_key;
    
    public function __construct($ml_api_url = 'http://localhost:5001') {
        $this->ml_api_url = $ml_api_url;
        $this->timeout = 15; // 15 segundos timeout para ML
        $this->api_key = 'smartshield_ml_2024'; // Clave para ML
    }
    
    /**
     * Verificar si el sistema ML está disponible
     */
    public function isMLAvailable() {
        try {
            $response = $this->makeMLRequest('GET', '/ml/health');
            return $response !== false && 
                   isset($response['status']) && 
                   $response['status'] === 'ok';
        } catch (Exception $e) {
            error_log("Sistema ML no disponible: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Predicción ML de fraude para una transacción
     */
    public function predictMLFraud($transactionData) {
        try {
            if (!$this->isMLAvailable()) {
                return $this->getMLFallback($transactionData);
            }
            
            $response = $this->makeMLRequest('POST', '/ml/predict', $transactionData);
            
            if ($response && $response['success']) {
                return $response['ml_prediction'];
            }
            
            return $this->getMLFallback($transactionData);
            
        } catch (Exception $e) {
            error_log("Error en predicción ML: " . $e->getMessage());
            return $this->getMLFallback($transactionData);
        }
    }
    
    /**
     * Análisis ML completo de comportamiento de usuario
     */
    public function analyzeMLUserBehavior($userId, $days = 30) {
        try {
            if (!$this->isMLAvailable()) {
                return null;
            }
            
            $data = [
                'id_usuario' => $userId,
                'dias_analisis' => $days
            ];
            
            $response = $this->makeMLRequest('POST', '/ml/analyze-user', $data);
            
            if ($response && $response['success']) {
                return $response['ml_user_analysis'];
            }
            
            return null;
            
        } catch (Exception $e) {
            error_log("Error en análisis ML de usuario: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Reentrenar modelos ML
     */
    public function retrainMLModels() {
        try {
            if (!$this->isMLAvailable()) {
                return false;
            }
            
            $response = $this->makeMLRequest('POST', '/ml/train');
            
            return $response && $response['success'];
            
        } catch (Exception $e) {
            error_log("Error al reentrenar modelos ML: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtener información de los modelos ML
     */
    public function getMLModelInfo() {
        try {
            if (!$this->isMLAvailable()) {
                return null;
            }
            
            $response = $this->makeMLRequest('GET', '/ml/model-info');
            
            return $response ?: null;
            
        } catch (Exception $e) {
            error_log("Error al obtener info de modelos ML: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Obtener importancia de características ML
     */
    public function getMLFeatureImportance() {
        try {
            if (!$this->isMLAvailable()) {
                return null;
            }
            
            $response = $this->makeMLRequest('GET', '/ml/feature-importance');
            
            return $response ?: null;
            
        } catch (Exception $e) {
            error_log("Error al obtener importancia ML: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Realizar petición HTTP al sistema ML
     */
    private function makeMLRequest($method, $endpoint, $data = null) {
        $url = $this->ml_api_url . $endpoint;
        
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json',
                'X-ML-API-Key: ' . $this->api_key
            ]
        ]);
        
        if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);
        
        if ($error) {
            throw new Exception("cURL Error ML: " . $error);
        }
        
        if ($httpCode >= 400) {
            throw new Exception("HTTP Error ML: " . $httpCode);
        }
        
        return json_decode($response, true);
    }
    
    /**
     * Sistema de respaldo ML cuando no está disponible
     */
    private function getMLFallback($transactionData) {
        return [
            'ml_risk_score' => 0,
            'fraud_probability' => 0,
            'is_anomaly' => false,
            'is_fraud_ml' => false,
            'anomaly_score' => 0,
            'ml_recommendations' => ["🤖 Sistema ML no disponible"],
            'ml_model_used' => false,
            'timestamp' => date('c')
        ];
    }
    
    /**
     * Generar alerta ML si el riesgo es alto
     */
    public function generateMLAlert($pdo, $userId, $cardId, $transactionId, $mlResult) {
        try {
            // Solo generar alerta ML si el riesgo es significativo
            if ($mlResult['ml_risk_score'] >= 50 && $mlResult['ml_model_used']) {
                
                $alertType = $mlResult['is_fraud_ml'] ? 'fraude_ml_detectado' : 'riesgo_ml_alto';
                
                $title = $mlResult['is_fraud_ml'] ? 
                    'Fraude detectado por Machine Learning' : 
                    'Riesgo alto detectado por ML';
                
                $message = "🤖 Análisis de Machine Learning:\n\n";
                $message .= "• Score de riesgo ML: " . $mlResult['ml_risk_score'] . "%\n";
                $message .= "• Probabilidad de fraude: " . $mlResult['fraud_probability'] . "%\n";
                
                if ($mlResult['is_anomaly']) {
                    $message .= "• Anomalía detectada por IA\n";
                }
                
                $message .= "\n🔍 Recomendaciones ML:\n";
                $message .= implode("\n", $mlResult['ml_recommendations']);
                
                $riskLevel = min(100, $mlResult['ml_risk_score']);
                
                // Insertar alerta ML en la base de datos
                $sql = "INSERT INTO alertas 
                        (id_usuario, id_tarjeta, id_transaccion, tipo_alerta, titulo, mensaje, nivel_riesgo)
                        VALUES (?, ?, ?, ?, ?, ?, ?)";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $userId,
                    $cardId,
                    $transactionId,
                    $alertType,
                    $title,
                    $message,
                    $riskLevel
                ]);
                
                return true;
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log("Error generando alerta ML: " . $e->getMessage());
            return false;
        }
    }
}

/**
 * Funciones helper para usar ML en el sistema
 */

function analyzeTransactionML($transactionData) {
    $ml = new SmartShieldML();
    return $ml->predictMLFraud($transactionData);
}

function getUserMLAnalysis($userId, $days = 30) {
    $ml = new SmartShieldML();
    return $ml->analyzeMLUserBehavior($userId, $days);
}

function generateSmartMLAlert($pdo, $userId, $cardId, $transactionId, $transactionData) {
    try {
        // Análisis ML inteligente de patrones
        $mlAnalysis = analyzeTransactionPatterns($pdo, $userId, $transactionData);
        
        // Solo generar alerta si ML detecta riesgo real
        if ($mlAnalysis['risk_score'] >= 60) {
            $alertType = $mlAnalysis['risk_score'] >= 80 ? 'fraude_ml_alto' : 'riesgo_ml_detectado';
            
            $title = "🤖 ML: " . $mlAnalysis['alert_reason'];
            
            $message = "Machine Learning ha detectado:\n\n";
            $message .= "🎯 Patrón detectado: " . $mlAnalysis['pattern_detected'] . "\n";
            $message .= "📊 Score de riesgo: " . $mlAnalysis['risk_score'] . "%\n";
            $message .= "🔍 Análisis: " . $mlAnalysis['analysis_detail'] . "\n\n";
            $message .= "💡 Recomendación ML: " . $mlAnalysis['recommendation'];
            
            // Insertar alerta ML
            $sql = "INSERT INTO alertas 
                    (id_usuario, id_tarjeta, id_transaccion, tipo_alerta, titulo, mensaje, nivel_riesgo)
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $userId,
                $cardId,
                $transactionId,
                $alertType,
                $title,
                $message,
                $mlAnalysis['risk_score']
            ]);
            
            return true;
        }
        
        return false;
        
    } catch (Exception $e) {
        error_log("Error en ML Alert: " . $e->getMessage());
        return false;
    }
}

function analyzeTransactionPatterns($pdo, $userId, $currentTransaction) {
    // Obtener historial del usuario (últimos 30 días)
    $sql = "SELECT monto, destino, HOUR(fecha_hora) as hora, 
                   DAYOFWEEK(fecha_hora) as dia_semana,
                   DATE(fecha_hora) as fecha
            FROM transacciones 
            WHERE id_usuario = ? 
            AND fecha_hora >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            ORDER BY fecha_hora DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userId]);
    $historial = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $riskScore = 0;
    $patterns = [];
    $alertReason = "Comportamiento normal";
    $analysis = "Transacción dentro de patrones habituales";
    $recommendation = "Continúa con normalidad";
    
    if (count($historial) > 0) {
        // 1. ANÁLISIS DE MONTO - Patrón de gasto
        $montos = array_column($historial, 'monto');
        $montoPromedio = array_sum($montos) / count($montos);
        $montoActual = $currentTransaction['monto'];
        
        if ($montoActual > ($montoPromedio * 3)) {
            $riskScore += 35;
            $patterns[] = "Monto inusualmente alto";
            $alertReason = "Monto fuera del patrón habitual";
            $analysis = "El monto es 3x mayor al promedio histórico (S/{$montoPromedio})";
        }
        
        // 2. ANÁLISIS DE FRECUENCIA
        $transaccionesHoy = 0;
        $fechaHoy = date('Y-m-d');
        foreach ($historial as $trans) {
            if ($trans['fecha'] === $fechaHoy) {
                $transaccionesHoy++;
            }
        }
        
        if ($transaccionesHoy >= 5) {
            $riskScore += 25;
            $patterns[] = "Alta frecuencia diaria";
            $alertReason = "Frecuencia inusual de transacciones";
            $analysis = "Ya realizaste {$transaccionesHoy} transacciones hoy";
        }
        
        // 3. ANÁLISIS DE HORARIO
        $horaActual = $currentTransaction['hora_transaccion'];
        if ($horaActual >= 23 || $horaActual <= 5) {
            $riskScore += 20;
            $patterns[] = "Horario nocturno";
            $alertReason = "Transacción en horario inusual";
            $analysis = "Transacción realizada a las {$horaActual}:00 hrs";
        }
        
        // 4. ANÁLISIS DE DESTINO - Lugar
        $destinoActual = $currentTransaction['destino'];
        $destinosHistoricos = array_column($historial, 'destino');
        $destinoConocido = in_array($destinoActual, $destinosHistoricos);
        
        if (!$destinoConocido && count($historial) > 3) {
            $riskScore += 30;
            $patterns[] = "Destino nuevo";
            $alertReason = "Transferencia a destino desconocido";
            $analysis = "Primera vez que transfieres a '{$destinoActual}'";
        }
        
        // 5. ANÁLISIS DE PATRÓN SEMANAL
        $diaActual = $currentTransaction['dia_semana'];
        if ($diaActual == 1 || $diaActual == 7) { // Domingo o Sábado
            $transaccionesFinde = 0;
            foreach ($historial as $trans) {
                if ($trans['dia_semana'] == 1 || $trans['dia_semana'] == 7) {
                    $transaccionesFinde++;
                }
            }
            
            if ($transaccionesFinde < 2 && count($historial) > 10) {
                $riskScore += 15;
                $patterns[] = "Transacción en fin de semana";
                $analysis .= " - Inusual para fin de semana";
            }
        }
    }
    
    // Ajustar recomendaciones según el riesgo
    if ($riskScore >= 80) {
        $recommendation = "Verificar identidad - Riesgo muy alto detectado";
    } elseif ($riskScore >= 60) {
        $recommendation = "Monitorear transacción - Patrón inusual";
    }
    
    return [
        'risk_score' => min(100, $riskScore),
        'pattern_detected' => implode(', ', $patterns) ?: 'Comportamiento normal',
        'alert_reason' => $alertReason,
        'analysis_detail' => $analysis,
        'recommendation' => $recommendation,
        'patterns_found' => $patterns
    ];
}

function isMLSystemAvailable() {
    $ml = new SmartShieldML();
    return $ml->isMLAvailable();
}

function getMLModelStatus() {
    $ml = new SmartShieldML();
    return $ml->getMLModelInfo();
}

function retrainMLSystem() {
    $ml = new SmartShieldML();
    return $ml->retrainMLModels();
}