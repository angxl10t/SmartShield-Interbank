<?php
/**
 * API para análisis ML en el dashboard
 * Endpoint independiente para mostrar análisis de Machine Learning
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

session_start();

if (!isset($_SESSION['autenticado']) || $_SESSION['autenticado'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

require_once "../bd/conexion.php";
require_once "../ml/ml_smartshield.php";

$idUsuario = $_SESSION['id_usuario'];

try {
    // Verificar si ML está disponible
    $mlAvailable = isMLSystemAvailable();
    
    if (!$mlAvailable) {
        echo json_encode([
            'success' => false,
            'error' => 'Sistema ML no disponible',
            'ml_available' => false
        ]);
        exit;
    }
    
    // Obtener análisis ML del usuario
    $mlUserAnalysis = getUserMLAnalysis($idUsuario, 30);
    
    // Obtener estadísticas básicas de la BD
    $sqlStats = "
        SELECT 
            COUNT(*) as total_transacciones,
            AVG(monto) as monto_promedio,
            MAX(monto) as monto_maximo,
            MIN(monto) as monto_minimo,
            SUM(monto) as gasto_total,
            COUNT(DISTINCT DATE(fecha_hora)) as dias_activos,
            COUNT(CASE WHEN HOUR(fecha_hora) BETWEEN 22 AND 23 OR HOUR(fecha_hora) BETWEEN 0 AND 6 THEN 1 END) as transacciones_nocturnas
        FROM transacciones 
        WHERE id_usuario = :id_usuario 
        AND fecha_hora >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ";
    
    $stmt = $pdo->prepare($sqlStats);
    $stmt->execute([':id_usuario' => $idUsuario]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Obtener alertas ML recientes
    $sqlAlertasML = "
        SELECT 
            COUNT(*) as total_alertas_ml,
            COUNT(CASE WHEN tipo_alerta IN ('fraude_ml_detectado', 'riesgo_ml_alto') THEN 1 END) as alertas_ml_criticas,
            AVG(CASE WHEN tipo_alerta IN ('fraude_ml_detectado', 'riesgo_ml_alto') THEN nivel_riesgo END) as riesgo_promedio_ml
        FROM alertas 
        WHERE id_usuario = :id_usuario 
        AND fecha_hora >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        AND tipo_alerta IN ('fraude_ml_detectado', 'riesgo_ml_alto')
    ";
    
    $stmt = $pdo->prepare($sqlAlertasML);
    $stmt->execute([':id_usuario' => $idUsuario]);
    $alertasML = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Obtener información del modelo ML
    $mlModelInfo = getMLModelStatus();
    
    // Calcular métricas ML específicas
    $mlMetrics = [
        'ml_system_active' => $mlAvailable,
        'ml_models_trained' => $mlModelInfo ? $mlModelInfo['ml_trained'] : false,
        'ml_feature_count' => $mlModelInfo ? $mlModelInfo['feature_count'] : 0,
        'transacciones_analizadas_ml' => $stats['total_transacciones'],
        'alertas_ml_generadas' => $alertasML['total_alertas_ml'] ?: 0,
        'precision_ml' => $alertasML['total_alertas_ml'] > 0 ? 
            round(($alertasML['alertas_ml_criticas'] / $alertasML['total_alertas_ml']) * 100, 1) : 0
    ];
    
    // Score ML del usuario (si está disponible el análisis)
    $mlUserScore = 0;
    $mlClassification = 'Sin análisis ML';
    $mlColor = '#6c757d';
    $mlRecommendations = ['🤖 Análisis ML no disponible'];
    
    if ($mlUserAnalysis) {
        $mlUserScore = $mlUserAnalysis['ml_score'];
        $mlClassification = $mlUserAnalysis['ml_classification'];
        $mlColor = $mlUserAnalysis['ml_color'];
        $mlRecommendations = $mlUserAnalysis['ml_recommendations'];
    }
    
    // Patrones ML detectados
    $mlPatterns = [];
    if ($stats['total_transacciones'] > 0) {
        $nocturnas_pct = ($stats['transacciones_nocturnas'] / $stats['total_transacciones']) * 100;
        
        if ($nocturnas_pct > 30) {
            $mlPatterns[] = "🌙 Alto porcentaje de transacciones nocturnas ({$nocturnas_pct}%)";
        }
        
        if ($stats['dias_activos'] > 0) {
            $freq_diaria = $stats['total_transacciones'] / $stats['dias_activos'];
            if ($freq_diaria > 5) {
                $mlPatterns[] = "📊 Alta frecuencia transaccional ({$freq_diaria} por día)";
            }
        }
        
        if ($stats['monto_maximo'] > ($stats['monto_promedio'] * 3)) {
            $mlPatterns[] = "💰 Variabilidad alta en montos de transacciones";
        }
    }
    
    if (empty($mlPatterns)) {
        $mlPatterns[] = "✅ Patrones de comportamiento estables";
    }
    
    $response = [
        'success' => true,
        'ml_analysis' => [
            'ml_available' => $mlAvailable,
            'user_id' => $idUsuario,
            'analysis_period' => '30 días',
            
            // Score y clasificación ML
            'ml_score' => $mlUserScore,
            'ml_classification' => $mlClassification,
            'ml_color' => $mlColor,
            
            // Estadísticas ML
            'ml_statistics' => [
                'transacciones_analizadas' => (int)$stats['total_transacciones'],
                'monto_promedio_ml' => round($stats['monto_promedio'] ?: 0, 2),
                'monto_maximo_ml' => round($stats['monto_maximo'] ?: 0, 2),
                'dias_activos_ml' => (int)$stats['dias_activos'],
                'transacciones_nocturnas' => (int)$stats['transacciones_nocturnas'],
                'frecuencia_diaria' => $stats['dias_activos'] > 0 ? 
                    round($stats['total_transacciones'] / $stats['dias_activos'], 1) : 0
            ],
            
            // Alertas ML
            'ml_alerts' => [
                'total_ml' => (int)$alertasML['total_alertas_ml'],
                'criticas_ml' => (int)$alertasML['alertas_ml_criticas'],
                'riesgo_promedio_ml' => round($alertasML['riesgo_promedio_ml'] ?: 0, 1)
            ],
            
            // Métricas del sistema ML
            'ml_metrics' => $mlMetrics,
            
            // Patrones detectados por ML
            'ml_patterns' => $mlPatterns,
            
            // Recomendaciones ML
            'ml_recommendations' => $mlRecommendations,
            
            // Información del modelo
            'ml_model_info' => $mlModelInfo,
            
            // Análisis completo del usuario (si disponible)
            'ml_user_analysis' => $mlUserAnalysis,
            
            'timestamp' => date('c')
        ]
    ];
    
    echo json_encode($response, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error al obtener análisis ML: ' . $e->getMessage(),
        'ml_available' => false
    ]);
}