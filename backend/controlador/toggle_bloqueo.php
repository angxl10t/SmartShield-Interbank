<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['autenticado']) || $_SESSION['autenticado'] !== true) {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

require_once "../bd/conexion.php";

$idUsuario = $_SESSION['id_usuario'];

try {
    // 1. Verificar estado actual
    $sql = "SELECT id_tarjeta, estado FROM tarjetas WHERE id_usuario = :id LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $idUsuario]);
    $tarjeta = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$tarjeta) {
        throw new Exception("Tarjeta no encontrada");
    }

    // 2. Alternar estado
    $nuevoEstado = ($tarjeta['estado'] === 'activa') ? 'bloqueada' : 'activa';

    $upd = "UPDATE tarjetas SET estado = :estado WHERE id_tarjeta = :id_tarjeta";
    $stmtUpd = $pdo->prepare($upd);
    $stmtUpd->execute([
        ':estado' => $nuevoEstado,
        ':id_tarjeta' => $tarjeta['id_tarjeta']
    ]);

    echo json_encode([
        'success' => true, 
        'nuevo_estado' => $nuevoEstado,
        'mensaje' => ($nuevoEstado === 'bloqueada') ? 'Tarjeta bloqueada correctamente' : 'Tarjeta activada correctamente'
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
