<?php
// LÓGICA DE DETECCIÓN AUTOMÁTICA
// Intentamos resolver la IP del host 'db'
$check_dns = @gethostbyname('db');

// Si gethostbyname devuelve la misma cadena ('db'), significa que NO encontró la IP.
// Por lo tanto, NO estamos en Docker Compose, estamos en Render.
if ($check_dns === 'db') {
    $host = '127.0.0.1'; // Render (Localhost por red)
    $password = '';      // Sin contraseña
} else {
    $host = 'db';        // Tu PC (Docker Compose)
    $password = 'root';  // Contraseña root
}

$base_datos = "interbank";
$usuario = "root";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$base_datos;charset=utf8", $usuario, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage() . " (Intentando conectar a: $host)");
}
?>