<?php
$conn = new mysqli("localhost", "root", "7350", "shopping_db");

echo "<h2>Debug - Estructura de la Base de Datos</h2>";

// Verificar si la tabla existe
$result = $conn->query("SHOW TABLES LIKE 'usuarios'");
if ($result->num_rows > 0) {
    echo "✅ La tabla 'usuarios' existe<br>";
    
    // Mostrar estructura de la tabla
    $structure = $conn->query("DESCRIBE usuarios");
    echo "<h3>Estructura de la tabla 'usuarios':</h3>";
    echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $structure->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . $row['Default'] . "</td>";
        echo "<td>" . $row['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "❌ La tabla 'usuarios' NO existe<br>";
}

$conn->close();
?>