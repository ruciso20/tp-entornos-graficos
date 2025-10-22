<?php
echo "<h3>Diagnóstico de conexión MySQL</h3>";

// Probar diferentes combinaciones
$configs = [
    ['localhost', 'root', ''],
    ['localhost', 'root', 'root'],
    ['127.0.0.1', 'root', '']
];

foreach ($configs as $config) {
    list($host, $user, $pass) = $config;
    
    echo "Probando: $user@$host (pass: " . (empty($pass) ? 'vacía' : '***') . ")<br>";
    
    try {
        $conn = new mysqli($host, $user, $pass);
        if ($conn->connect_error) {
            echo "❌ Error: " . $conn->connect_error . "<br><br>";
        } else {
            echo "✅ ¡Conexión exitosa!<br><br>";
            $conn->close();
            break;
        }
    } catch (Exception $e) {
        echo "❌ Exception: " . $e->getMessage() . "<br><br>";
    }
}
?>