<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] != 'cliente') {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$codPromo = $_GET['codPromo'];

include("../config/db.php");

// Verificar que la promoción existe y está disponible
$promo_query = $conn->prepare("
    SELECT p.*, l.nombreLocal 
    FROM promociones p 
    JOIN locales l ON p.codLocal = l.codLocal 
    WHERE p.codPromo = ? AND p.estadoPromo = 'aprobada'
    AND p.fechaHastaPromo >= CURDATE() 
    AND p.fechaDesdePromo <= CURDATE()
");
$promo_query->bind_param("i", $codPromo);
$promo_query->execute();
$promo = $promo_query->get_result()->fetch_assoc();

if (!$promo) {
    $_SESSION['error'] = "Promoción no disponible";
    header("Location: promociones.php");
    exit;
}

// Verificar si ya usó esta promoción
$usada_query = $conn->prepare("
    SELECT * FROM uso_promociones 
    WHERE codCliente = ? AND codPromo = ? AND estado = 'aceptada'
");
$usada_query->bind_param("ii", $user_id, $codPromo);
$usada_query->execute();
$usada = $usada_query->get_result()->fetch_assoc();

if ($usada) {
    $_SESSION['error'] = "Ya has utilizado esta promoción";
    header("Location: promociones.php");
    exit;
}

// Registrar uso de promoción
$insert_query = $conn->prepare("
    INSERT INTO uso_promociones (codCliente, codPromo, fechaUsoPromo, estado) 
    VALUES (?, ?, CURDATE(), 'enviada')
");
$insert_query->bind_param("ii", $user_id, $codPromo);

if ($insert_query->execute()) {
    $_SESSION['success'] = "Promoción utilizada correctamente. Espera la confirmación del local.";
} else {
    $_SESSION['error'] = "Error al utilizar la promoción";
}

header("Location: promociones.php");
exit;
?>