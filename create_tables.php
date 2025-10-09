<?php
include("config/db.php");

$sql = "CREATE TABLE IF NOT EXISTS promociones (
    codPromo INT AUTO_INCREMENT PRIMARY KEY,
    textoPromo VARCHAR(200) NOT NULL,
    fechaDesdePromo DATE NOT NULL,
    fechaHastaPromo DATE NOT NULL,
    categoriaCliente ENUM('Inicial', 'Medium', 'Premium') DEFAULT 'Inicial',
    diasSemana VARCHAR(20) NOT NULL,
    estadoPromo ENUM('pendiente', 'aprobada', 'denegada') DEFAULT 'pendiente',
    codLocal INT NOT NULL,
    fechaCreacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql)) {
    echo "✅ Tabla 'promociones' creada<br>";
} else {
    echo "❌ Error: " . $conn->error . "<br>";
}

$sql = "CREATE TABLE IF NOT EXISTS novedades (
    codNovedad INT AUTO_INCREMENT PRIMARY KEY,
    textoNovedad VARCHAR(200) NOT NULL,
    fechaDesdeNovedad DATE NOT NULL,
    fechaHastaNovedad DATE NOT NULL,
    categoriaCliente ENUM('Inicial', 'Medium', 'Premium') DEFAULT 'Inicial',
    fechaCreacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql)) {
    echo "✅ Tabla 'novedades' creada<br>";
} else {
    echo "❌ Error: " . $conn->error . "<br>";
}

echo "<a href='dashboard.php'>Volver al Dashboard</a>";
?>