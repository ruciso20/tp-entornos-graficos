<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // permite que frontend acceda desde otro puerto

include '../includes/db.php'; //metemos la BDD creada

// Ejemplo de datos devueltos
$data = [
    "mensaje" => "API funcionando correctamente",
    "fecha" => date("Y-m-d H:i:s")
];

echo json_encode($data);
?>