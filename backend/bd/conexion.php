<?php
// la bd se llama interbank, la pondre en la estrcutura

$host = "db";
$dbname = "interbank";
$usuario = "root";
$clave = "root"; 

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $usuario, $clave);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}
