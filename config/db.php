<?php
$host = "localhost";
$user = "root";   // usuario por defecto en XAMPP
$pass = "";       // en XAMPP por defecto no hay contraseña
$db   = "shopping_db";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}
?>
