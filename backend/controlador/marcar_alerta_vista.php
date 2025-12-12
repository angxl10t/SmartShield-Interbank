<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['autenticado']) || $_SESSION['autenticado'] !== true) {
    echo json_encode(['ok' => false, 'error' => 'no_auth']);
    exit;
}

require_once "../bd/conexion.php";

$idUsuario = (int)$_SESSION['id_usuario'];
$idAlerta  = isset($_POST['id_alerta']) ? (int)$_POST['id_alerta'] : 0;

if ($idAlerta <= 0) {
    echo json_encode(['ok' => false, 'error' => 'id_invalido']);
    exit;
}

$sql = "UPDATE alertas
        SET estado = 'vista'
        WHERE id_alerta = :id_alerta
          AND id_usuario = :id_usuario";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    ':id_alerta'  => $idAlerta,
    ':id_usuario' => $idUsuario
]);

echo json_encode(['ok' => true]);
