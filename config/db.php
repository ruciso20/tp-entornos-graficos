<?php
$servername = "localhost";
$username = "root";
$password = "Alem105800";
$dbname = "shopping_db";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}
