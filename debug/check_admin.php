<?php
include("config/db.php");

// Verificar si existe el admin
$sql = "SELECT * FROM usuarios WHERE email='admin@shopping.com'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    echo "✅ Admin existe: admin@shopping.com<br>";
    
    // Crear password para admin si no tiene
    $row = $result->fetch_assoc();
    if (empty($row['password'])) {
        $hashed_password = password_hash("admin123", PASSWORD_DEFAULT);
        $update_sql = "UPDATE usuarios SET password='$hashed_password' WHERE email='admin@shopping.com'";
        $conn->query($update_sql);
        echo "✅ Password de admin actualizado: admin123<br>";
    }
} else {
    // Crear admin si no existe
    $hashed_password = password_hash("admin123", PASSWORD_DEFAULT);
    $sql = "INSERT INTO usuarios (nombre, email, password, rol, estado) 
            VALUES ('Administrador', 'admin@shopping.com', '$hashed_password', 'admin', 'aprobado')";
    
    if ($conn->query($sql)) {
        echo "✅ Admin creado: admin@shopping.com / admin123<br>";
    }
}

echo "<a href='login.php'>Ir al Login</a>";
?>