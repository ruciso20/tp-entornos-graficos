<?php
$host = "localhost"; // o puede ser 127.0.0.1
$user = "root";      // por defecto en XAMPP
$pass = "";          // vacío por defecto en XAMPP
$db   = "egdb"; //nombre de la BDD de c/u

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}
?>