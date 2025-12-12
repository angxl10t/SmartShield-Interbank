<?php
// force_reset.php
echo "🔧 INICIANDO RESETEO DE CONTRASEÑA...\n";

// Cargamos tu conexión "inteligente"
require_once 'backend/bd/conexion.php';

// Generamos el hash limpio y fresco usando PHP
$pass = "123456";
$hash = password_hash($pass, PASSWORD_BCRYPT);

try {
    // Forzamos la actualización para el usuario con ID 1
    $sql = "UPDATE usuarios SET password_hash = :hash WHERE id_usuario = 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':hash' => $hash]);
    
    echo "✅ ÉXITO: Contraseña restablecida a '123456' para el usuario ID 1.\n";
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}
?>