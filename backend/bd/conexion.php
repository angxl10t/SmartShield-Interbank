<?php
// Detectamos el entorno
$host = getenv('DB_HOST') ?: "db";
$usuario = "root";
$base_datos = "interbank";

// Definimos la contraseña según el entorno
if ($host === 'localhost' || $host === '127.0.0.1') {
    $password = "";       // Render
} else {
    $password = "root";   // Tu PC Local
}

try {
    // Usamos las variables definidas arriba
    $pdo = new PDO("mysql:host=$host;dbname=$base_datos;charset=utf8", $usuario, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}
?>