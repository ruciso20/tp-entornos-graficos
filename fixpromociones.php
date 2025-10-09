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
    echo "✅ Tabla 'promociones' creada correctamente<br>";
    
    // Verificar estructura
    $result = $conn->query("DESCRIBE promociones");
    echo "<h4>Estructura de la tabla:</h4>";
    echo "<table border='1'><tr><th>Field</th><th>Type</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td></tr>";
    }
    echo "</table>";
} else {
    echo "❌ Error: " . $conn->error . "<br>";
}

echo "<a href='dashboard.php'>Volver al Dashboard</a>";
?>