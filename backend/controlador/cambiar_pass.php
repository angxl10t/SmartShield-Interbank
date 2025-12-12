<?php
require_once "../bd/conexion.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $dni = trim($_POST['dni']);
    $correo = trim($_POST['correo']);
    $newPass = $_POST['new_password'];

    // 1. Verificar que el usuario existe
    $sql = "SELECT id_usuario FROM usuarios WHERE dni = :dni AND correo = :correo LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':dni' => $dni, ':correo' => $correo]);
    
    if ($stmt->rowCount() > 0) {
        // 2. Actualizar contraseña
        $hash = password_hash($newPass, PASSWORD_BCRYPT);
        
        $upd = "UPDATE usuarios SET password_hash = :hash WHERE dni = :dni";
        $stmtUpd = $pdo->prepare($upd);
        $stmtUpd->execute([':hash' => $hash, ':dni' => $dni]);

        // Redirigir al login con éxito
        header("Location: ../../frontend/inicio.php?mensaje=clave_actualizada");
        exit;
    } else {
        // Datos incorrectos
        echo "<script>
            alert('Los datos ingresados no coinciden con ningún usuario.');
            window.location.href='../../frontend/recuperar.php';
        </script>";
        exit;
    }
}
?>
