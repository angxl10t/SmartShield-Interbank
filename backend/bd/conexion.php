<?php
// DETECCIÓN INTELIGENTE DE ENTORNO
$check_dns = @gethostbyname('db');

if ($check_dns === 'db') {
    // ESTAMOS EN RENDER (Entorno Web)
    $host = '127.0.0.1';
    $usuario = 'admin_db';  // <--- Usamos el usuario nuevo
    $password = '123456';   // <--- Con su contraseña
} else {
    // ESTAMOS EN TU PC (Docker Compose)
    $host = 'db';
    $usuario = 'root';
    $password = 'root';
}

$base_datos = "interbank";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$base_datos;charset=utf8", $usuario, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage() . " (Host: $host, Usuario: $usuario)");
}
?>