<?php
session_start();
require_once "../bd/conexion.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $dni = trim($_POST['dni']);
    $nombre = trim($_POST['nombre']);
    $correo = trim($_POST['correo']);
    $password = $_POST['password'];

    // Validar duplicados
    $sqlCheck = "SELECT id_usuario FROM usuarios WHERE dni = :dni OR correo = :correo";
    $stmtCheck = $pdo->prepare($sqlCheck);
    $stmtCheck->execute([':dni' => $dni, ':correo' => $correo]);
    
    if ($stmtCheck->rowCount() > 0) {
        // Usuario ya existe, redirigir al login con error
        header("Location: ../../frontend/inicio.php?error=usuario_ya_existe");
        exit;
    }

    try {
        $pdo->beginTransaction();

        // 1. Crear Usuario
        $passHash = password_hash($password, PASSWORD_BCRYPT);
        $sqlUser = "INSERT INTO usuarios (dni, nombre_completo, correo, password_hash, estado, rol, fecha_registro) 
                    VALUES (:dni, :nombre, :correo, :pass, 1, 'cliente', NOW())";
        $stmtUser = $pdo->prepare($sqlUser);
        $stmtUser->execute([
            ':dni' => $dni,
            ':nombre' => $nombre,
            ':correo' => $correo,
            ':pass' => $passHash
        ]);
        
        $idUsuario = $pdo->lastInsertId();

        // 2. Crear Tarjeta Automática (Para que el dashboard funcione)
        $numTarjeta = '4111 **** **** ' . rand(1000, 9999);
        $sqlCard = "INSERT INTO tarjetas (id_usuario, numero_enmascarado, marca, saldo_disponible, estado, uso_internacional, modo_inteligente) 
                    VALUES (:id, :num, 'VISA', 1500.00, 'activa', 0, 1)";
        $stmtCard = $pdo->prepare($sqlCard);
        $stmtCard->execute([':id' => $idUsuario, ':num' => $numTarjeta]);
        
        $idTarjeta = $pdo->lastInsertId();

        // 3. Crear Configuración de Seguridad Inicial
        $sqlConfig = "INSERT INTO config_seguridad_tarjeta (id_tarjeta, limite_semanal, horario_inicio, horario_fin, notificar_email)
                      VALUES (:id_tarjeta, 1000.00, '06:00:00', '23:59:00', 1)";
        $stmtConfig = $pdo->prepare($sqlConfig);
        $stmtConfig->execute([':id_tarjeta' => $idTarjeta]);

        $pdo->commit();

        // Autologin (Opcional) o redirigir a login
        header("Location: ../../frontend/inicio.php?mensaje=registro_exitoso");
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        die("Error en registro: " . $e->getMessage());
    }
}
?>
