<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] != 'cliente') {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$promo_id = isset($_GET['promo_id']) ? $_GET['promo_id'] : null;

if (!$promo_id) {
    $_SESSION['error'] = "❌ Promoción no especificada";
    header("Location: promociones.php");
    exit;
}

include("../config/db.php");

// Verificar que la promoción existe y está disponible
$promo_query = $conn->prepare("
    SELECT p.*, l.nombre as local_nombre 
    FROM promociones p 
    JOIN locales l ON p.local_id = l.id 
    WHERE p.id = ? AND p.estado = 'aprobada'
    AND p.fecha_fin >= CURDATE() 
    AND p.fecha_inicio <= CURDATE()
");
$promo_query->bind_param("i", $promo_id);
$promo_query->execute();
$promo = $promo_query->get_result()->fetch_assoc();

if (!$promo) {
    $_SESSION['error'] = "❌ Promoción no disponible o ha expirado";
    header("Location: promociones.php");
    exit;
}

// Verificar si ya tiene una solicitud PENDIENTE o USADA para esta promoción
$usada_query = $conn->prepare("
    SELECT * FROM uso_promociones 
    WHERE cliente_id = ? AND promocion_id = ? AND estado IN ('pendiente', 'usada')
");
$usada_query->bind_param("ii", $user_id, $promo_id);
$usada_query->execute();
$usada = $usada_query->get_result()->fetch_assoc();

if ($usada) {
    if ($usada['estado'] == 'pendiente') {
        $_SESSION['error'] = "❌ Ya tienes una solicitud pendiente para esta promoción. Espera la confirmación del local.";
    } else {
        $_SESSION['error'] = "❌ Ya has utilizado esta promoción anteriormente.";
    }
    header("Location: promociones.php");
    exit;
}

// Registrar uso de promoción como PENDIENTE
$insert_query = $conn->prepare("
    INSERT INTO uso_promociones (cliente_id, promocion_id, estado) 
    VALUES (?, ?, 'pendiente')
");
$insert_query->bind_param("ii", $user_id, $promo_id);

if ($insert_query->execute()) {
    $_SESSION['success'] = "✅ Solicitud enviada correctamente. Presenta este código en el local: <strong>PROMO-" . $promo_id . "</strong>. Espera la confirmación del dueño.";
} else {
    $_SESSION['error'] = "❌ Error al utilizar la promoción: " . $conn->error;
}

header("Location: promociones.php");
exit;
