<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] != 'cliente') {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$categoria = $_SESSION['categoria'];
$nombre = $_SESSION['nombre'];

include("../config/db.php");

// Obtener estadísticas del cliente
$stats_query = $conn->query("
    SELECT 
        COUNT(*) as total_promociones,
        SUM(CASE WHEN estado = 'aceptada' THEN 1 ELSE 0 END) as promociones_aceptadas,
        SUM(CASE WHEN estado = 'rechazada' THEN 1 ELSE 0 END) as promociones_rechazadas,
        SUM(CASE WHEN estado = 'enviada' THEN 1 ELSE 0 END) as promociones_pendientes
    FROM uso_promociones 
    WHERE codCliente = $user_id
");
$stats = $stats_query->fetch_assoc();

// Calcular progreso para siguiente categoría
$progreso = 0;
$siguiente_categoria = '';
$promociones_requeridas = 0;

if ($categoria == 'Inicial') {
    $promociones_requeridas = 5; // Ejemplo: 5 promociones para subir a Medium
    $siguiente_categoria = 'Medium';
    $progreso = min(100, ($stats['promociones_aceptadas'] / $promociones_requeridas) * 100);
} elseif ($categoria == 'Medium') {
    $promociones_requeridas = 15; // Ejemplo: 15 promociones para subir a Premium
    $siguiente_categoria = 'Premium';
    $progreso = min(100, ($stats['promociones_aceptadas'] / $promociones_requeridas) * 100);
} else {
    $progreso = 100;
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Mi Perfil - Cliente</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand">
                <strong>Shopping Rosario - Mi Perfil</strong>
            </a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text text-white me-3">
                    <?php echo $nombre; ?>
                    <span class="badge bg-<?php
                                            echo $categoria == 'Premium' ? 'danger' : ($categoria == 'Medium' ? 'warning' : 'primary');
                                            ?>">
                        <?php echo $categoria; ?>
                    </span>
                </span>
                <a href="../index.php" class="btn btn-outline-light me-2">Volver</a>
                <a href="../logout.php" class="btn btn-outline-light">Cerrar Sesión</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-4">
                <!-- Información del perfil -->
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5>Información Personal</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Nombre:</strong> <?php echo $nombre; ?></p>
                        <p><strong>Categoría Actual:</strong>
                            <span class="badge bg-<?php
                                                    echo $categoria == 'Premium' ? 'danger' : ($categoria == 'Medium' ? 'warning' : 'primary');
                                                    ?>">
                                <?php echo $categoria; ?>
                            </span>
                        </p>
                        <p><strong>Usuario ID:</strong> #<?php echo $user_id; ?></p>
                    </div>
                </div>

                <!-- Estadísticas -->
                <div class="card mt-4">
                    <div class="card-header bg-success text-white">
                        <h5>Mis Estadísticas</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Promociones utilizadas:</strong> <?php echo $stats['promociones_aceptadas']; ?></p>
                        <p><strong>Promociones pendientes:</strong> <?php echo $stats['promociones_pendientes']; ?></p>
                        <p><strong>Promociones rechazadas:</strong> <?php echo $stats['promociones_rechazadas']; ?></p>
                        <p><strong>Total solicitudes:</strong> <?php echo $stats['total_promociones']; ?></p>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <!-- Progreso de categoría -->
                <div class="card">
                    <div class="card-header bg-warning text-dark">
                        <h5>Progreso de Categoría</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($categoria != 'Premium'): ?>
                            <p>Progreso hacia <strong><?php echo $siguiente_categoria; ?></strong>:</p>
                            <div class="progress mb-3" style="height: 25px;">
                                <div class="progress-bar progress-bar-striped progress-bar-animated"
                                    role="progressbar"
                                    style="width: <?php echo $progreso; ?>%"
                                    aria-valuenow="<?php echo $progreso; ?>"
                                    aria-valuemin="0"
                                    aria-valuemax="100">
                                    <?php echo number_format($progreso, 1); ?>%
                                </div>
                            </div>
                            <p class="text-muted">
                                <small>
                                    Has utilizado <?php echo $stats['promociones_aceptadas']; ?> de <?php echo $promociones_requeridas; ?> promociones requeridas para subir a <?php echo $siguiente_categoria; ?>.
                                </small>
                            </p>
                        <?php else: ?>
                            <div class="alert alert-success">
                                <h6>¡Felicidades!</h6>
                                <p class="mb-0">Has alcanzado la categoría máxima. Disfruta de todos los beneficios Premium.</p>
                            </div>
                        <?php endif; ?>

                        <!-- Beneficios por categoría -->
                        <div class="row mt-4 text-center">
                            <div class="col-md-4">
                                <div class="card <?php echo $categoria == 'Inicial' ? 'border-primary' : ''; ?>">
                                    <div class="card-body">
                                        <h6>👤 Inicial</h6>
                                        <small class="text-muted">
                                            • Promociones básicas<br>
                                            • Acceso limitado
                                        </small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card <?php echo $categoria == 'Medium' ? 'border-warning' : ''; ?>">
                                    <div class="card-body">
                                        <h6>Medium</h6>
                                        <small class="text-muted">
                                            • + Promociones exclusivas<br>
                                            • Beneficios adicionales
                                        </small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card <?php echo $categoria == 'Premium' ? 'border-danger' : ''; ?>">
                                    <div class="card-body">
                                        <h6>Premium</h6>
                                        <small class="text-muted">
                                            • Todas las promociones<br>
                                            • Beneficios VIP<br>
                                            • Atención preferencial
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Consejos -->
                <div class="card mt-4">
                    <div class="card-header bg-info text-white">
                        <h6>💡 Consejos para subir de categoría</h6>
                    </div>
                    <div class="card-body">
                        <ul class="mb-0">
                            <li>Utiliza promociones regularmente</li>
                            <li>Visita diferentes locales del shopping</li>
                            <li>Revisa las novedades frecuentemente</li>
                            <li>Las promociones se cuentan solo cuando son aceptadas por el local</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>