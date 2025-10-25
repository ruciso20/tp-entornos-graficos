<?php
$servername = "localhost";
$username = "root";      
$password = "Alem105800";
$dbname = "shopping_db";

// $servername = processs.env.DB_HOST;
// $username = processs.env.DB_USERNAME;      
// $password = processs.env.DB_PASSWORD;
// $dbname = processs.env.DB_NAME;


$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}
?>