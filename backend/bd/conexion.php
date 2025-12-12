<?php
// Detectamos si existe la variable del Dockerfile (Render)
$env_host = getenv('DB_HOST');

if ($env_host) {
    // ESTAMOS EN RENDER
    $host = $env_host; // Será 127.0.0.1
    $password = "";    // Contraseña vacía que configuramos en el entrypoint
} else {
    // ESTAMOS EN TU PC (Local)
    $host = 'db';
    $password = 'root';
}

$base_datos = "interbank";
$usuario = "root";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$base_datos;charset=utf8", $usuario, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage() . " (Host: $host)");
}
?>