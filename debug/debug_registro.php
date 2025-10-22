<?php
session_start();
include("config/db.php");

echo "<h3>Debug del Registro</h3>";

// Simulamos un registro manual para ver si funciona
$nombre = "Juan Pérez Debug";
$email = "debug_" . time() . "@test.com"; // Email único
$password = password_hash("debug123", PASSWORD_DEFAULT);
$tipo = "cliente";

echo "Intentando registrar:<br>";
echo "Nombre: $nombre<br>";
echo "Email: $email<br>";
echo "Tipo: $tipo<br>";

// 1. Verificar si el email ya existe
$check_sql = "SELECT * FROM usuarios WHERE email='$email'";
$check_result = $conn->query($check_sql);

if ($check_result->num_rows > 0) {
    echo "❌ ERROR: El email ya existe<br>";
} else {
    echo "✅ Email disponible<br>";
    
    // 2. Intentar insertar
    $sql = "INSERT INTO usuarios (nombre, email, password, rol, estado, categoria_cliente) 
            VALUES ('$nombre', '$email', '$password', '$tipo', 'pendiente', 'Inicial')";
    
    if ($conn->query($sql) === TRUE) {
        echo "✅ REGISTRO EXITOSO en la BD<br>";
        echo "ID del nuevo usuario: " . $conn->insert_id . "<br>";
        echo "Puedes probar login con: $email / debug123<br>";
    } else {
        echo "❌ ERROR en el INSERT: " . $conn->error . "<br>";
    }
}

echo "<hr>";
echo "<a href='register.php'>Volver al registro</a> | ";
echo "<a href='login.php'>Ir al login</a>";
?>