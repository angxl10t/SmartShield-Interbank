<?php
session_start();
header('Content-Type: application/json');

// 1. Seguridad: Verificar que el usuario esté logueado
if (!isset($_SESSION['autenticado']) || $_SESSION['autenticado'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

require_once "../bd/conexion.php";

$idUsuario = $_SESSION['id_usuario'];

try {
    // 2. Buscar la tarjeta del usuario
    $sql = "SELECT id_tarjeta, estado FROM tarjetas WHERE id_usuario = :id LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $idUsuario]);
    $tarjeta = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$tarjeta) {
        throw new Exception("No se encontró una tarjeta asociada.");
    }

    // 3. Invertir el estado (Si es 'activa' pasa a 'bloqueada', y viceversa)
    // Asegúrate de que en tu BD la columna se llame 'estado' y use estos valores.
    $nuevoEstado = ($tarjeta['estado'] === 'activa') ? 'bloqueada' : 'activa';

    // 4. Guardar el cambio en la base de datos
    $upd = "UPDATE tarjetas SET estado = :estado WHERE id_tarjeta = :id_tarjeta";
    $stmtUpd = $pdo->prepare($upd);
    $stmtUpd->execute([
        ':estado' => $nuevoEstado,
        ':id_tarjeta' => $tarjeta['id_tarjeta']
    ]);

    // 5. Responder al Frontend que todo salió bien
    echo json_encode([
        'success' => true, 
        'nuevo_estado' => $nuevoEstado,
        'mensaje' => ($nuevoEstado === 'bloqueada') ? '🚫 Tarjeta BLOQUEADA correctamente.' : '✅ Tarjeta REACTIVADA correctamente.'
    ]);

} catch (Exception $e) {
    // Si algo falla, enviar el error
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
