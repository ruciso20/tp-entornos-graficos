<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] != 'cliente') {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$promo_id = $_POST['promo_id'] ?? $_GET['promo_id'] ?? null;


if (!$promo_id) {
    $_SESSION['error'] = "❌ Promoción no especificada";
    header("Location: promociones.php");
    exit;
}

include("../config/db.php");

// Verificar que la promoción existe y está disponible
$promo_query = $conn->prepare("
    SELECT p.*, l.nombre as local_nombre, l.id as local_id
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
    INSERT INTO uso_promociones (cliente_id, promocion_id, estado, local_id) 
    VALUES (?, ?, 'pendiente', ?)
");
$insert_query->bind_param("iii", $user_id, $promo_id, $promo['local_id']);

if ($insert_query->execute()) {
    $uso_id = $conn->insert_id; // Obtener el ID del uso recién creado

    // Preparar datos para el comprobante
    $fecha_fin = date('d/m/Y', strtotime($promo['fecha_fin']));
    $categoria = ucfirst($promo['categoria_minima']);

    // Mensaje de éxito con botón para imprimir comprobante
    $_SESSION['success'] = "
    ✅ Solicitud enviada correctamente. 
    <br><br>
    <strong>📋 Resumen:</strong>
    <ul>
        <li><strong>Promoción:</strong> {$promo['titulo']}</li>
        <li><strong>Local:</strong> {$promo['local_nombre']}</li>
        <li><strong>Código:</strong> PROMO-{$promo_id}-{$uso_id}</li>
    </ul>
    
    <button class='btn btn-success btn-lg' onclick='imprimirComprobante({$uso_id}, {$promo_id}, \"{$promo['local_nombre']}\", \"{$promo['titulo']}\", \"{$promo['descripcion']}\", \"{$fecha_fin}\", \"{$promo['dias_validos']}\", \"{$promo['categoria_minima']}\")'>
        🖨️ Imprimir Comprobante
    </button>
    
    <a href='promociones.php' class='btn btn-outline-secondary btn-lg'>
        ← Volver a Promociones
    </a>
    ";
} else {
    $_SESSION['error'] = "❌ Error al utilizar la promoción: " . $conn->error;
}

header("Location: promociones.php");
exit;
