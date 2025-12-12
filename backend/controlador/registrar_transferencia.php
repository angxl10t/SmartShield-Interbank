<?php
session_start();

if (!isset($_SESSION['autenticado']) || $_SESSION['autenticado'] !== true) {
    header("Location: ../../frontend/inicio.php");
    exit;
}

require_once "../bd/conexion.php";
require_once "../ml/ml_smartshield.php";

$idUsuario = $_SESSION['id_usuario'] ?? null;

if (!$idUsuario) {
    die("Usuario no identificado en la sesión.");
}

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

$sqlTarjeta = "SELECT t.id_tarjeta, t.saldo_disponible,
                      c.id_config, c.gasto_semanal_actual, c.fecha_ultimo_reset_semanal, c.limite_semanal
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

$idTarjeta            = (int)$info['id_tarjeta'];
$saldoDisponible      = (float)$info['saldo_disponible'];
$idConfig             = $info['id_config'] ?? null;
$gastoSemanalActual   = isset($info['gasto_semanal_actual']) ? (float)$info['gasto_semanal_actual'] : 0;
$fechaUltimoReset     = $info['fecha_ultimo_reset_semanal'] ?? null;
$limiteSemanal        = isset($info['limite_semanal']) ? (float)$info['limite_semanal'] : 0;

if ($monto > $saldoDisponible) {
    header("Location: ../../frontend/index.php?error=saldo");
    exit;
}

$hoy        = new DateTime('now');
$inicioSemana = (clone $hoy)->modify('monday this week')->setTime(0, 0, 0);

if ($fechaUltimoReset) {
    $dtUltimoReset = new DateTime($fechaUltimoReset);
    if ($dtUltimoReset < $inicioSemana) {
        $gastoSemanalActual = 0;
    }
} else {
    $gastoSemanalActual = 0;
}

$nuevoGastoSemanal = $gastoSemanalActual + $monto;

$pdo->beginTransaction();

try {
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

    $sqlUpdSaldo = "UPDATE tarjetas
                    SET saldo_disponible = saldo_disponible - :monto
                    WHERE id_tarjeta = :id_tarjeta";
    $stmtSaldo = $pdo->prepare($sqlUpdSaldo);
    $stmtSaldo->execute([
        ':monto'      => $monto,
        ':id_tarjeta' => $idTarjeta
    ]);

    $hoy = new DateTime();

    $sqlSuma = "SELECT COALESCE(SUM(monto), 0) AS total_semana
            FROM transacciones
            WHERE id_tarjeta = :id_tarjeta
              AND YEARWEEK(fecha_hora, 1) = YEARWEEK(NOW(), 1)";

    $stmtSuma = $pdo->prepare($sqlSuma);
    $stmtSuma->execute([
        ':id_tarjeta' => $idTarjeta
    ]);

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

    // Obtener ID de la transacción recién insertada
    $idTransaccion = $pdo->lastInsertId();
    
    // === ANÁLISIS MACHINE LEARNING INTELIGENTE ===
    // El ML analiza: patrón de gasto, frecuencia, lugar, monto
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
    
    // ML genera alertas automáticamente basado en patrones
    generateSmartMLAlert($pdo, $idUsuario, $idTarjeta, $idTransaccion, $mlTransactionData);

    // === IA BASADA EN REGLAS (sistema original) ===
    evaluar_riesgos_y_generar_alertas(
        $pdo,
        $idUsuario,
        $idTarjeta,
        $idTransaccion,
        $monto,
        date('Y-m-d H:i:s'),
        $destino,
        $aliasDestino,
        $numeroCuenta
    );


    $pdo->commit();
    header("Location: ../../frontend/index.php?ok=1");
    exit;
} catch (Exception $e) {
    $pdo->rollBack();
    die("Error al registrar la transferencia: " . $e->getMessage());
}


//FUNCIONES 

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
    PDO $pdo,
    $idUsuario,
    $idTarjeta,
    $idTransaccion,
    $monto,
    $fechaHora,
    $destino,
    $aliasDestino,
    $numeroCuenta
) {
    $sql = "SELECT limite_semanal, gasto_semanal_actual,
                   horario_inicio, horario_fin
            FROM config_seguridad_tarjeta
            WHERE id_tarjeta = :id_tarjeta
            LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id_tarjeta' => $idTarjeta]);
    $config = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$config) {
        return; 
    }

    $limiteSemanal = (float)($config['limite_semanal'] ?? 0);
    $gastoSemanal  = (float)($config['gasto_semanal_actual'] ?? 0);
    $horaInicio    = $config['horario_inicio'] ?? null; 
    $horaFin       = $config['horario_fin'] ?? null;

    $tituloBase = $aliasDestino !== '' ? $aliasDestino
        : ($destino !== '' ? $destino : 'Transferencia');

    // ESTE AVISA SOBRE SI YA VAS A LLEGAR A TU LIMITEE
    if ($limiteSemanal > 0) {
        $ratio = $gastoSemanal / $limiteSemanal;

        if ($ratio >= 0.7 && $ratio < 1.0) {
            crear_alerta(
                $pdo,
                $idUsuario,
                $idTarjeta,
                $idTransaccion,
                'limite_cercano',
                'Consumo cercano a tu límite semanal',
                "Tu gasto semanal con SmartShield Interbank se acerca al límite configurado. 
Revisa tus últimas compras para mantener controlado tu presupuesto.",
                60
            );
        } elseif ($ratio >= 1.0) {
            crear_alerta(
                $pdo,
                $idUsuario,
                $idTarjeta,
                $idTransaccion,
                'limite_superado',
                'Has superado tu límite semanal',
                "Tu consumo semanal ha superado el tope configurado. 
Te recomendamos revisar tus operaciones recientes y, si es necesario, ajustar tu límite.",
                80
            );
        }
    }

    // FUERA DEL RANGO Q CONFIGURAS EN CONFIGURACION
    if ($horaInicio && $horaFin) {
        $horaTx   = (new DateTime($fechaHora))->format('H:i:s');

        if ($horaInicio < $horaFin) {
            $fueraHorario = ($horaTx < $horaInicio || $horaTx > $horaFin);
        } else {
            $fueraHorario = !($horaTx >= $horaInicio || $horaTx <= $horaFin);
        }

        if ($fueraHorario) {
            crear_alerta(
                $pdo,
                $idUsuario,
                $idTarjeta,
                $idTransaccion,
                'fuera_horario',
                'Operación fuera del horario configurado',
                "Se detectó una transferencia realizada fuera del horario que tienes configurado 
para tus compras habituales. Verifica si reconoces esta operación.",
                70
            );
        }
    }

    // CALCULA EL PROMEDIO Y TE DICE MONTO INUSUAL EN BASE AL PROMEDIO
    $sqlProm = "SELECT AVG(monto) AS promedio
                FROM transacciones
                WHERE id_usuario = :id_usuario
                  AND fecha_hora >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                  AND monto > 0";
    $stmtProm = $pdo->prepare($sqlProm);
    $stmtProm->execute([':id_usuario' => $idUsuario]);
    $promRow = $stmtProm->fetch(PDO::FETCH_ASSOC);
    $promedio = (float)($promRow['promedio'] ?? 0);

    if ($promedio > 0 && $monto >= 3 * $promedio) {
        crear_alerta(
            $pdo,
            $idUsuario,
            $idTarjeta,
            $idTransaccion,
            'monto_inusual',
            'Monto inusualmente alto detectado',
            "Esta transferencia tiene un monto superior a tu consumo promedio. 
Si no reconoces la operación, te sugerimos bloquear temporalmente tu tarjeta desde SmartShield.",
            75
        );
    }

    // DESTINO NUEVO CON MONTO ALTO
    if ($numeroCuenta !== '') {
        $sqlDest = "SELECT COUNT(*) 
                    FROM transacciones
                    WHERE id_usuario = :id_usuario
                      AND numero_cuenta = :cuenta
                      AND id_transaccion <> :id_tx";
        $stmtDest = $pdo->prepare($sqlDest);
        $stmtDest->execute([
            ':id_usuario' => $idUsuario,
            ':cuenta'     => $numeroCuenta,
            ':id_tx'      => $idTransaccion
        ]);
        $veces = (int)$stmtDest->fetchColumn();

        if ($veces === 0 && $monto >= 3000) { 
            crear_alerta(
                $pdo,
                $idUsuario,
                $idTarjeta,
                $idTransaccion,
                'destino_nuevo',
                'Transferencia importante a un nuevo destinatario',
                "Realizaste una transferencia de S/ " . number_format($monto, 2) .
                    " a un destinatario que nunca antes habías utilizado. 
Si no eres tú, comunícate con el banco de inmediato.",
                70
            );
        }
    }
}
