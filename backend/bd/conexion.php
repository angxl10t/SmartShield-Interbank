<?php
// MODO INTELIGENTE:
// Preguntamos al sistema: "¿Existe un servidor llamado 'db'?"
// Si no existe (Render), nos conectamos a nosotros mismos (127.0.0.1).
// Si sí existe (Tu PC), nos conectamos a él.

$host_check = @gethostbyname('db');

if ($host_check === 'db') {
    // Si devuelve el mismo nombre, es que NO encontró la IP.
    // ESTAMOS EN RENDER
    $host = '127.0.0.1';
    $password = ''; 
} else {
    // Si devuelve una IP (ej. 172.18.0.2), es que SÍ existe.
    // ESTAMOS EN TU PC (Docker Compose)
    $host = 'db';
    $password = 'root';
}

$base_datos = "interbank";
$usuario = "root";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$base_datos;charset=utf8", $usuario, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error crítico conectando a la BD: " . $e->getMessage() . " (Intentando en host: $host)");
}
?>