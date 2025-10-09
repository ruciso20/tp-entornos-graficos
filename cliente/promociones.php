<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] != 'cliente') {
    header("Location: ../login.php");
    exit;
}

include("../config/db.php");

// Obtener promociones disponibles para el cliente según su categoría
$categoria_cliente = $_SESSION['categoria'];
$usuario_id = $_SESSION['user_id'];

$promociones = $conn->query("
    SELECT p.*, l.nombre as local_nombre, l.codigo_local
    FROM promociones p 
    LEFT JOIN locales l ON p.codLocal = l.id 
    WHERE p.estadoPromo = 'aprobada' 
    AND p.fechaHastaPromo >= CURDATE()
    AND (
        p.categoriaCliente = '$categoria_cliente' 
        OR p.categoriaCliente = 'Inicial'
        OR ('$categoria_cliente' = 'Premium' AND p.categoriaCliente IN ('Inicial', 'Medium', 'Premium'))
        OR ('$categoria_cliente' = 'Medium' AND p.categoriaCliente IN ('Inicial', 'Medium'))
    )
    ORDER BY p.fechaCreacion DESC
");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Promociones - Cliente</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="../dashboard.php">🛍️ Mis Promociones</a>
            <div>
                <span class="navbar-text text-white me-3">
                    Categoría: <span class="badge bg-warning"><?php echo $categoria_cliente; ?></span>
                </span>
                <a href="../dashboard.php" class="btn btn-outline-light">← Volver</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h2>🎁 Promociones Disponibles para Ti</h2>
        <p class="text-muted">Estas son las promociones a las que tienes acceso según tu categoría <strong><?php echo $categoria_cliente; ?></strong></p>

        <div class="row">
            <?php if ($promociones->num_rows > 0): ?>
                <?php while($promo = $promociones->fetch_assoc()): 
                    $es_exclusiva = $promo['categoriaCliente'] == $categoria_cliente && $categoria_cliente != 'Inicial';
                ?>
                <div class="col-md-6 mb-4">
                    <div class="card h-100 <?php echo $es_exclusiva ? 'border-warning' : ''; ?>">
                        <div class="card-body">
                            <?php if ($es_exclusiva): ?>
                                <span class="badge bg-warning float-end">⭐ Exclusiva</span>
                            <?php endif; ?>
                            <h5 class="card-title"><?php echo $promo['textoPromo']; ?></h5>
                            <p class="card-text">
                                <strong>🏪 Local:</strong> <?php echo $promo['local_nombre']; ?><br>
                                <strong>📅 Válida hasta:</strong> <?php echo date('d/m/Y', strtotime($promo['fechaHastaPromo'])); ?><br>
                                <strong>📆 Días:</strong> <?php echo $promo['diasSemana']; ?>
                            </p>
                            <div class="mb-3">
                                <span class="badge bg-<?php 
                                    echo $promo['categoriaCliente'] == 'Premium' ? 'danger' : 
                                         ($promo['categoriaCliente'] == 'Medium' ? 'warning' : 'info'); 
                                ?>">
                                    Para: <?php echo $promo['categoriaCliente']; ?>
                                </span>
                                <?php if ($promo['categoriaCliente'] == $categoria_cliente): ?>
                                    <span class="badge bg-success">✅ Disponible para ti</span>
                                <?php endif; ?>
                            </div>
                            <button class="btn btn-primary btn-sm" onclick="usarPromocion(<?php echo $promo['codPromo']; ?>)">
                                🎯 Usar Esta Promoción
                            </button>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="alert alert-info text-center">
                        <h5>No hay promociones disponibles</h5>
                        <p>Vuelve más tarde para descubrir nuevas ofertas.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Información de categorías -->
        <div class="card mt-5">
            <div class="card-header">
                <h5>📊 Tu Progreso de Categoría</h5>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-md-4">
                        <div class="card <?php echo $categoria_cliente == 'Inicial' ? 'bg-primary text-white' : 'bg-light'; ?>">
                            <div class="card-body">
                                <h5>👤 Inicial</h5>
                                <p>Promociones básicas</p>
                                <?php echo $categoria_cliente == 'Inicial' ? '<span class="badge bg-warning">ACTUAL</span>' : ''; ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card <?php echo $categoria_cliente == 'Medium' ? 'bg-warning text-dark' : 'bg-light'; ?>">
                            <div class="card-body">
                                <h5>👥 Medium</h5>
                                <p>+ Promociones exclusivas</p>
                                <?php echo $categoria_cliente == 'Medium' ? '<span class="badge bg-warning">ACTUAL</span>' : ''; ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card <?php echo $categoria_cliente == 'Premium' ? 'bg-danger text-white' : 'bg-light'; ?>">
                            <div class="card-body">
                                <h5>⭐ Premium</h5>
                                <p>Todas las promociones + beneficios VIP</p>
                                <?php echo $categoria_cliente == 'Premium' ? '<span class="badge bg-warning">ACTUAL</span>' : ''; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mt-3">
                    <p class="text-muted">
                        <small>💡 <strong>Consejo:</strong> Usa más promociones para subir de categoría y desbloquear beneficios exclusivos.</small>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script>
    function usarPromocion(codPromo) {
        if (confirm('¿Quieres usar esta promoción?')) {
            alert('¡Promoción aplicada! Presenta este código en el local: PROMO-' + codPromo);
            // Aquí iría la lógica para registrar el uso de la promoción
        }
    }
    </script>
</body>
</html>