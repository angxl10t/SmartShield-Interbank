<?php
session_start();

// --- ZONA HORARIA Y ERRORES ---
date_default_timezone_set('America/Lima'); 
ini_set('display_errors', 0); // Ocultar errores en pantalla para el usuario
error_reporting(E_ALL);
// ------------------------------

if (!isset($_SESSION['autenticado']) || $_SESSION['autenticado'] !== true) {
    header("Location: ../../frontend/inicio.php");
    exit;
}

// 1. Cargar Conexión (Ruta relativa dentro de backend)
require_once "../bd/conexion.php";

// 2. Cargar Machine Learning con RUTA CORREGIDA y PROTECCIÓN DE FALLOS
$ruta_ml_raiz = "../../ml/mi_smartshield.php"; // Ruta si 'ml' está en la raíz
$ruta_ml_backend = "../ml/mi_smartshield.php"; // Ruta si 'ml' está en backend

if (file_exists($ruta_ml_raiz)) {
    require_once $ruta_ml_raiz;
} elseif (file_exists($ruta_ml_backend)) {
    require_once $ruta_ml_backend;
} else {
    // === MODO DE SEGURIDAD (FALLBACK) ===
    // Si no encuentra el archivo ML, definimos funciones vacías para que NO SALGA ERROR FATAL
    if (!function_exists('generateSmartMLAlert')) {
        function generateSmartMLAlert($a, $b, $c, $d, $e) { return false; }
    }
}

$idUsuario = $_SESSION['id_usuario'] ?? null;

if (!$idUsuario) {
    die("Usuario no identificado en la sesión.");
}

// 3. Recoger datos
$destino      = trim($_POST['destinatario']   ?? '');
$aliasDestino = trim($_POST['alias_destino']  ?? '');
$numeroCuenta = trim($_POST['cuenta_destino'] ?? '');
$moneda       = trim($_POST['moneda']         ?? 'PEN');
$monto        = isset($_POST['monto']) ? (float)$_POST['monto'] : 0;
$descripcion  = trim($_POST['descripcion']    ?? '');

if ($destino === '' || $numeroCuenta === '' || $monto <= 0) {
    header("Location: ../../frontend/index.php?error=datos");
    exit;
}

if ($descripcion === '') {
    $descripcion = 'Transferencia simulada';
}

// 4. Obtener datos de tarjeta Y configuración de seguridad
$sqlTarjeta = "SELECT t.id_tarjeta, t.saldo_disponible, t.uso_internacional,
                      c.id_config, c.gasto_semanal_actual, c.fecha_ultimo_reset_semanal, 
                      c.limite_semanal, c.horario_inicio, c.horario_fin
               FROM tarjetas t
               LEFT JOIN config_seguridad_tarjeta c
                    ON c.id_tarjeta = t.id_tarjeta
               WHERE t.id_usuario = :id_usuario
               LIMIT 1";

$stmt = $pdo->prepare($sqlTarjeta);
$stmt->execute([':id_usuario' => $idUsuario]);
$info = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$info) {
    die("No se encontró una tarjeta asociada al usuario.");
}

// Variables de tarjeta y configuración
$idTarjeta          = (int)$info['id_tarjeta'];
$saldoDisponible    = (float)$info['saldo_disponible'];
$usoInternacional   = (int)$info['uso_internacional']; 

$idConfig           = $info['id_config'] ?? null;
$limiteSemanal      = isset($info['limite_semanal']) ? (float)$info['limite_semanal'] : 0;
$gastoSemanalActual = isset($info['gasto_semanal_actual']) ? (float)$info['gasto_semanal_actual'] : 0;
$fechaUltimoReset   = $info['fecha_ultimo_reset_semanal'] ?? null;
$horarioInicio      = $info['horario_inicio'] ?? '00:00:00';
$horarioFin         = $info['horario_fin'] ?? '23:59:59';

// =================================================================================
// 🛡️ BLOQUE 1: VALIDACIONES DE SEGURIDAD (SMARTSHIELD)
// =================================================================================

// A. VALIDACIÓN DE SALDO
if ($monto > $saldoDisponible) {
    header("Location: ../../frontend/index.php?error=saldo");
    exit;
}

// B. VALIDACIÓN DE USO INTERNACIONAL
if ($moneda === 'USD' && $usoInternacional === 0) {
    header("Location: ../../frontend/index.php?error=bloqueo_internacional");
    exit;
}

// C. VALIDACIÓN DE HORARIO
$horaActual = date('H:i:s');
$transaccionPermitidaHorario = false;

if ($horarioInicio <= $horarioFin) {
    // Rango normal (Ej: 06:00 a 23:00)
    if ($horaActual >= $horarioInicio && $horaActual <= $horarioFin) {
        $transaccionPermitidaHorario = true;
    }
} else {
    // Rango nocturno cruzado (Ej: 22:00 a 06:00)
    if ($horaActual >= $horarioInicio || $horaActual <= $horarioFin) {
        $transaccionPermitidaHorario = true;
    }
}

if (!$transaccionPermitidaHorario) {
    header("Location: ../../frontend/index.php?error=bloqueo_horario");
    exit;
}

// D. REINICIO DE GASTO SEMANAL
$hoy          = new DateTime('now');
$inicioSemana = (clone $hoy)->modify('monday this week')->setTime(0, 0, 0);

if ($fechaUltimoReset) {
    $dtUltimoReset = new DateTime($fechaUltimoReset);
    if ($dtUltimoReset < $inicioSemana) {
        $gastoSemanalActual = 0;
    }
} else {
    $gastoSemanalActual = 0;
}

// E. VALIDACIÓN DE LÍMITE SEMANAL
if ($limiteSemanal > 0) {
    $gastoProyectado = $gastoSemanalActual + $monto;
    if ($gastoProyectado > $limiteSemanal) {
        header("Location: ../../frontend/index.php?error=bloqueo_limite");
        exit;
    }
}

// =================================================================================
// PROCESAMIENTO DE TRANSFERENCIA
// =================================================================================

$nuevoGastoSemanal = $gastoSemanalActual + $monto;

$pdo->beginTransaction();

try {
    // 1. Insertar transacción
    $sqlIns = "INSERT INTO transacciones
               (id_usuario, id_tarjeta, tipo, destino, alias_destino, numero_cuenta,
                moneda, monto, fecha_hora, descripcion, estado)
               VALUES
               (:id_usuario, :id_tarjeta, :tipo, :destino, :alias_destino, :numero_cuenta,
                :moneda, :monto, NOW(), :descripcion, :estado)";

    $stmtIns = $pdo->prepare($sqlIns);
    $stmtIns->execute([
        ':id_usuario'    => $idUsuario,
        ':id_tarjeta'    => $idTarjeta,
        ':tipo'          => 'transferencia',
        ':destino'       => $destino,
        ':alias_destino' => $aliasDestino !== '' ? $aliasDestino : null,
        ':numero_cuenta' => $numeroCuenta,
        ':moneda'        => $moneda,
        ':monto'         => $monto,
        ':descripcion'   => $descripcion,
        ':estado'        => 'aplicada'
    ]);

    // 2. Actualizar saldo
    $sqlUpdSaldo = "UPDATE tarjetas
                    SET saldo_disponible = saldo_disponible - :monto
                    WHERE id_tarjeta = :id_tarjeta";
    $stmtSaldo = $pdo->prepare($sqlUpdSaldo);
    $stmtSaldo->execute([
        ':monto'      => $monto,
        ':id_tarjeta' => $idTarjeta
    ]);

    // 3. Verificar total real semanal
    $sqlSuma = "SELECT COALESCE(SUM(monto), 0) AS total_semana
            FROM transacciones
            WHERE id_tarjeta = :id_tarjeta
              AND YEARWEEK(fecha_hora, 1) = YEARWEEK(NOW(), 1)";
    $stmtSuma = $pdo->prepare($sqlSuma);
    $stmtSuma->execute([':id_tarjeta' => $idTarjeta]);
    $rowSuma = $stmtSuma->fetch(PDO::FETCH_ASSOC);
    $nuevoGastoSemanal = (float)($rowSuma['total_semana'] ?? 0);

    if ($idConfig) {
        $sqlUpdConfig = "UPDATE config_seguridad_tarjeta
                         SET gasto_semanal_actual = :gasto,
                             fecha_ultimo_reset_semanal = :fecha
                         WHERE id_config = :id_config";
        $stmtCfg = $pdo->prepare($sqlUpdConfig);
        $stmtCfg->execute([
            ':gasto'     => $nuevoGastoSemanal,
            ':fecha'     => $hoy->format('Y-m-d H:i:s'),
            ':id_config' => $idConfig
        ]);
    }

    $idTransaccion = $pdo->lastInsertId();
    
    // === ANÁLISIS MACHINE LEARNING ===
    $mlTransactionData = [
        'id_usuario' => $idUsuario,
        'monto' => $monto,
        'tipo_transaccion' => 'transferencia',
        'moneda' => $moneda,
        'destino' => $destino,
        'alias_destino' => $aliasDestino,
        'saldo_disponible' => $saldoDisponible - $monto,
        'limite_semanal' => $limiteSemanal,
        'gasto_semanal_actual' => $nuevoGastoSemanal,
        'hora_transaccion' => (int)date('H'),
        'dia_semana' => (int)date('N'),
        'tipo_tarjeta' => 'credito'
    ];
    
    // Intentar ejecutar ML si existe la función
    if (function_exists('generateSmartMLAlert')) {
        generateSmartMLAlert($pdo, $idUsuario, $idTarjeta, $idTransaccion, $mlTransactionData);
    } 
    
    // Ejecutar reglas clásicas
    evaluar_riesgos_y_generar_alertas($pdo, $idUsuario, $idTarjeta, $idTransaccion, $monto, date('Y-m-d H:i:s'), $destino, $aliasDestino, $numeroCuenta);

    $pdo->commit();
    header("Location: ../../frontend/index.php?ok=1");
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    die("Error al registrar la transferencia: " . $e->getMessage());
}

// FUNCIONES DE APOYO

function crear_alerta(PDO $pdo, $idUsuario, $idTarjeta, $idTransaccion, $tipo, $titulo, $mensaje, $nivelRiesgo = 50)
{
    $sql = "INSERT INTO alertas
            (id_usuario, id_tarjeta, id_transaccion, tipo_alerta, titulo, mensaje, nivel_riesgo)
            VALUES
            (:id_usuario, :id_tarjeta, :id_transaccion, :tipo_alerta, :titulo, :mensaje, :nivel_riesgo)";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':id_usuario'     => $idUsuario,
        ':id_tarjeta'     => $idTarjeta,
        ':id_transaccion' => $idTransaccion,
        ':tipo_alerta'    => $tipo,
        ':titulo'         => $titulo,
        ':mensaje'        => $mensaje,
        ':nivel_riesgo'   => $nivelRiesgo
    ]);
}

function evaluar_riesgos_y_generar_alertas(
    PDO $pdo, $idUsuario, $idTarjeta, $idTransaccion, $monto, $fechaHora, $destino, $aliasDestino, $numeroCuenta
) {
    $sql = "SELECT limite_semanal, gasto_semanal_actual FROM config_seguridad_tarjeta WHERE id_tarjeta = :id_tarjeta LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id_tarjeta' => $idTarjeta]);
    $config = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$config) return;

    $limiteSemanal = (float)($config['limite_semanal'] ?? 0);
    $gastoSemanal  = (float)($config['gasto_semanal_actual'] ?? 0);

    if ($limiteSemanal > 0) {
        $ratio = $gastoSemanal / $limiteSemanal;
        if ($ratio >= 0.7 && $ratio < 1.0) {
            crear_alerta($pdo, $idUsuario, $idTarjeta, $idTransaccion, 'limite_cercano',
                'Consumo cercano a tu límite semanal',
                "Tu gasto semanal con SmartShield se acerca al límite configurado.", 60);
        }
    }

    $sqlProm = "SELECT AVG(monto) AS promedio FROM transacciones 
                WHERE id_usuario = :id_usuario AND fecha_hora >= DATE_SUB(NOW(), INTERVAL 30 DAY) AND monto > 0";
    $stmtProm = $pdo->prepare($sqlProm);
    $stmtProm->execute([':id_usuario' => $idUsuario]);
    $promRow = $stmtProm->fetch(PDO::FETCH_ASSOC);
    $promedio = (float)($promRow['promedio'] ?? 0);

    if ($promedio > 0 && $monto >= 3 * $promedio) {
        crear_alerta($pdo, $idUsuario, $idTarjeta, $idTransaccion, 'monto_inusual',
            'Monto inusualmente alto detectado',
            "Esta transferencia tiene un monto superior a tu consumo promedio.", 75);
    }

    if ($numeroCuenta !== '') {
        $sqlDest = "SELECT COUNT(*) FROM transacciones 
                    WHERE id_usuario = :id_usuario AND numero_cuenta = :cuenta AND id_transaccion <> :id_tx";
        $stmtDest = $pdo->prepare($sqlDest);
        $stmtDest->execute([':id_usuario' => $idUsuario, ':cuenta' => $numeroCuenta, ':id_tx' => $idTransaccion]);
        $veces = (int)$stmtDest->fetchColumn();

        if ($veces === 0 && $monto >= 3000) { 
            crear_alerta($pdo, $idUsuario, $idTarjeta, $idTransaccion, 'destino_nuevo',
                'Transferencia importante a un nuevo destinatario',
                "Transferencia de S/ " . number_format($monto, 2) . " a un nuevo destinatario.", 70);
        }
    }
}
?>
