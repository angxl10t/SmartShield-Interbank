<?php
session_start();

if (!isset($_SESSION['autenticado']) || $_SESSION['autenticado'] !== true) {
    header("Location: ../../frontend/inicio.php");
    exit;
}

require_once "../bd/conexion.php";

try {
    if (!isset($_POST['id_tarjeta'])) {
        throw new Exception("Tarjeta no especificada");
    }

    $idTarjeta = (int)$_POST['id_tarjeta'];

    $usoInternacional = isset($_POST['uso_internacional']) ? 1 : 0;

    $horarioInicio = $_POST['horario_inicio'] ?? '06:00';
    $horarioFin    = $_POST['horario_fin'] ?? '23:00';

    if (strlen($horarioInicio) === 5) $horarioInicio .= ':00';
    if (strlen($horarioFin) === 5) $horarioFin .= ':00';

    $limiteSemanal = isset($_POST['limite_semanal']) ? (float)$_POST['limite_semanal'] : 0;
    $modoInteligente = isset($_POST['modo_inteligente']) ? 1 : 0;

    $sqlCheck = "SELECT id_config 
                 FROM config_seguridad_tarjeta 
                 WHERE id_tarjeta = :id_tarjeta
                 LIMIT 1";
    $stmtCheck = $pdo->prepare($sqlCheck);
    $stmtCheck->execute([':id_tarjeta' => $idTarjeta]);
    $row = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        $sqlUpdate = "UPDATE config_seguridad_tarjeta
                      SET uso_internacional = :uso,
                          horario_inicio   = :h_inicio,
                          horario_fin      = :h_fin,
                          limite_semanal   = :limite,
                          modo_viaje       = :modo
                      WHERE id_config = :id_config";
        $stmtUpd = $pdo->prepare($sqlUpdate);
        $stmtUpd->execute([
            ':uso'       => $usoInternacional,
            ':h_inicio'  => $horarioInicio,
            ':h_fin'     => $horarioFin,
            ':limite'    => $limiteSemanal,
            ':modo'      => $modoInteligente,
            ':id_config' => $row['id_config']
        ]);
    } else {
        $sqlIns = "INSERT INTO config_seguridad_tarjeta
                    (id_tarjeta, uso_internacional, horario_inicio, horario_fin, limite_semanal, modo_viaje)
                   VALUES
                    (:id_tarjeta, :uso, :h_inicio, :h_fin, :limite, :modo)";
        $stmtIns = $pdo->prepare($sqlIns);
        $stmtIns->execute([
            ':id_tarjeta' => $idTarjeta,
            ':uso'        => $usoInternacional,
            ':h_inicio'   => $horarioInicio,
            ':h_fin'      => $horarioFin,
            ':limite'     => $limiteSemanal,
            ':modo'       => $modoInteligente
        ]);
    }

    header("Location: ../../frontend/configuracion.php?guardado=1");
    exit;

} catch (Exception $e) {
    header("Location: ../../frontend/configuracion.php?error=1");
    exit;
}
