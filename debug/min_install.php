<?php
// install.php - Configuración completa para la base de datos
$servername = "localhost";
$username = "root";
$password = "7350";
$dbname = "shopping_db";

echo "<!DOCTYPE html>";
echo "<html>";
echo "<head>";
echo "<title>Instalación - Shopping Rosario</title>";
echo "<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css' rel='stylesheet'>";
echo "</head>";
echo "<body class='container mt-5'>";
echo "<h1 class='text-primary'>Instalación del Sistema</h1>";

// Crear conexión
$conn = new mysqli($servername, $username, $password);

// Verificar conexión
if ($conn->connect_error) {
    die("<div class='alert alert-danger'>Error de conexión: " . $conn->connect_error . "</div>");
}

echo "<div class='alert alert-success'>✅ Conectado a MySQL</div>";

// Crear base de datos si no existe
$sql = "CREATE DATABASE IF NOT EXISTS $dbname";
if ($conn->query($sql) === TRUE) {
    echo "<div class='alert alert-success'>✅ Base de datos 'shopping_db' creada/existe</div>";
} else {
    echo "<div class='alert alert-danger'>❌ Error creando BD: " . $conn->error . "</div>";
}

// Usar la base de datos
$conn->select_db($dbname);

// Crear tabla usuarios
$sql = "CREATE TABLE IF NOT EXISTS usuarios (
    codUsuario INT AUTO_INCREMENT PRIMARY KEY,
    nombreUsuario VARCHAR(100) UNIQUE NOT NULL,
    claveUsuario VARCHAR(255) NOT NULL,
    tipoUsuario ENUM('administrador', 'dueño', 'cliente') NOT NULL,
    categoriaCliente ENUM('Inicial', 'Medium', 'Premium') DEFAULT 'Inicial',
    estado ENUM('pendiente', 'aprobado', 'rechazado') DEFAULT 'pendiente',
    fechaRegistro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "<div class='alert alert-success'>✅ Tabla 'usuarios' creada correctamente</div>";
} else {
    echo "<div class='alert alert-danger'>❌ Error creando tabla usuarios: " . $conn->error . "</div>";
}

// Crear tabla locales
$sql = "CREATE TABLE IF NOT EXISTS locales (
    codLocal INT AUTO_INCREMENT PRIMARY KEY,
    nombreLocal VARCHAR(100) NOT NULL,
    ubicacionLocal VARCHAR(50),
    rubroLocal VARCHAR(20),
    codUsuario INT,
    estado ENUM('activo', 'inactivo') DEFAULT 'activo'
)";

if ($conn->query($sql) === TRUE) {
    echo "<div class='alert alert-success'>✅ Tabla 'locales' creada correctamente</div>";
} else {
    echo "<div class='alert alert-danger'>❌ Error creando tabla locales: " . $conn->error . "</div>";
}

// Crear usuario administrador
$admin_email = "admin@shopping.com";
$admin_password = password_hash("admin123", PASSWORD_DEFAULT);

// IMPORTANTE: nombreUsuario CORRECTO (sin la 'm' extra)
$check_admin = "SELECT * FROM usuarios WHERE nombreUsuario='$admin_email'";
$result = $conn->query($check_admin);

if ($result && $result->num_rows == 0) {
    $sql = "INSERT INTO usuarios (nombreUsuario, claveUsuario, tipoUsuario, estado) 
            VALUES ('$admin_email', '$admin_password', 'administrador', 'aprobado')";

    if ($conn->query($sql) === TRUE) {
        echo "<div class='alert alert-success'>✅ Usuario administrador creado</div>";
        echo "<div class='alert alert-info'>";
        echo "<strong>Credenciales de administrador:</strong><br>";
        echo "📧 Email: admin@shopping.com<br>";
        echo "🔑 Password: admin123";
        echo "</div>";
    } else {
        echo "<div class='alert alert-danger'>❌ Error creando admin: " . $conn->error . "</div>";
    }
} else {
    echo "<div class='alert alert-warning'>✅ Usuario administrador ya existe</div>";
}

$conn->close();

echo "<hr>";
echo "<div class='alert alert-success'>";
echo "<h3>🎉 Instalación completada!</h3>";
echo "<p>El sistema está listo para usar.</p>";
echo "</div>";

echo "<div class='mt-3'>";
echo "<a href='login.php' class='btn btn-success me-2'>Ir al Login</a>";
echo "<a href='index.php' class='btn btn-primary'>Ir al Inicio</a>";
echo "</div>";

echo "</body>";
echo "</html>";
