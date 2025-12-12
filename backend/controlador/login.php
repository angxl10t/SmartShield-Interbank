<?php
session_start();
require_once "../bd/conexion.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $dni = trim($_POST['dni'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($dni === "" || $password === "") {
        header("Location: ../../frontend/inicio.php?error=campos_vacios");
        exit;
    }

    $sql = "SELECT id_usuario, dni, correo, nombre_completo, password_hash, estado, rol 
            FROM usuarios 
            WHERE dni = :dni
            LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':dni' => $dni]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$usuario) {
        header("Location: ../../frontend/inicio.php?error=usuario_no_encontrado");
        exit;
    }

    if ((int)$usuario['estado'] !== 1) {
        header("Location: ../../frontend/inicio.php?error=usuario_bloqueado");
        exit;
    }

    if (!password_verify($password, $usuario['password_hash'])) {
        header("Location: ../../frontend/inicio.php?error=contrasena_invalida");
        exit;
    }

    $_SESSION['id_usuario']     = $usuario['id_usuario'];
    $_SESSION['nombre']         = $usuario['nombre_completo'];
    $_SESSION['dni']            = $usuario['dni'];
    $_SESSION['rol']            = $usuario['rol'];
    $_SESSION['autenticado']    = true;

    header("Location: ../../frontend/index.php");
    exit;
} else {
    header("Location: ../../frontend/inicio.php");
    exit;
}
