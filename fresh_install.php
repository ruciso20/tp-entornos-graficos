<?php
$servername = "localhost";
$username = "root";      
$password = "7350";
$dbname = "shopping_db";

echo "<h1>Instalación Fresca - Desde Cero</h1>";

// Conectar
$conn = new mysqli($servername, $username, $password);

if ($conn->connect_error) {
    die("Error conexión: " . $conn->connect_error);
}

// Eliminar y recrear la base de datos
$conn->query("DROP DATABASE IF EXISTS $dbname");
$conn->query("CREATE DATABASE $dbname");
$conn->select_db($dbname);

echo "✅ Base de datos recreada<br>";

// Crear tabla usuarios con estructura SIMPLE y garantizada
$sql = "CREATE TABLE usuarios (
    codUsuario INT AUTO_INCREMENT PRIMARY KEY,
    nombreUsuario VARCHAR(100) NOT NULL,
    claveUsuario VARCHAR(255) NOT NULL,
    tipoUsuario VARCHAR(15) NOT NULL,
    estado VARCHAR(10) DEFAULT 'pendiente'
)";

if ($conn->query($sql)) {
    echo "✅ Tabla 'usuarios' creada EXITOSAMENTE<br>";
} else {
    echo "❌ Error: " . $conn->error . "<br>";
}

// Verificar que la columna existe
$result = $conn->query("DESCRIBE usuarios");
echo "<h3>Columnas en la tabla:</h3>";
while ($row = $result->fetch_assoc()) {
    echo "• " . $row['Field'] . " (" . $row['Type'] . ")<br>";
}

// Insertar admin
$admin_email = "admin@shopping.com";
$admin_password = password_hash("admin123", PASSWORD_DEFAULT);

$sql = "INSERT INTO usuarios (nombreUsuario, claveUsuario, tipoUsuario, estado) 
        VALUES ('$admin_email', '$admin_password', 'administrador', 'aprobado')";

if ($conn->query($sql)) {
    echo "✅ Admin creado: admin@shopping.com / admin123<br>";
} else {
    echo "❌ Error creando admin: " . $conn->error . "<br>";
}

$conn->close();
echo "<hr><h2>🎉 INSTALACIÓN COMPLETADA</h2>";
?>