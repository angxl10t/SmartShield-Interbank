cat > fix.php <<EOF
<?php
// 1. Conectamos a la base de datos
require_once 'backend/bd/conexion.php';

// 2. Definimos la nueva contraseña y la encriptamos correctamente
$nuevo_pass = '123456';
$hash_seguro = password_hash($nuevo_pass, PASSWORD_BCRYPT);

// 3. Actualizamos el usuario con ID 1
try {
    $sql = "UPDATE usuarios SET password_hash = :hash WHERE id_usuario = 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':hash' => $hash_seguro]);
    
    echo "\n------------------------------------------------\n";
    echo "✅ EXITO: Contraseña actualizada correctamente.\n";
    echo "🔑 Nueva contraseña: 123456\n";
    echo "🔐 Hash generado: " . $hash_seguro . "\n";
    echo "------------------------------------------------\n";
} catch (PDOException $e) {
    echo "❌ ERROR: " . $e->getMessage();
}
?>
EOF