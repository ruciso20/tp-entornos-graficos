<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$rol = $_SESSION['rol'];
$nombre = $_SESSION['nombre'];

// Estadísticas para el admin
include("config/db.php");

// Inicializar estadísticas con valores por defecto
$stats = [
    'total_usuarios' => 0,
    'total_locales' => 0,
    'pendientes_aprobacion' => 0,
    'promociones_pendientes' => 0
];

// Obtener estadísticas de forma segura
try {
    $stats['total_usuarios'] = $conn->query("SELECT COUNT(*) as total FROM usuarios")->fetch_assoc()['total'];
} catch (Exception $e) {}

try {
    $stats['total_locales'] = $conn->query("SELECT COUNT(*) as total FROM locales")->fetch_assoc()['total'];
} catch (Exception $e) {}

try {
    $stats['pendientes_aprobacion'] = $conn->query("SELECT COUNT(*) as total FROM usuarios WHERE estado='pendiente' AND rol='dueno'")->fetch_assoc()['total'];
} catch (Exception $e) {}

try {
    $stats['promociones_pendientes'] = $conn->query("SELECT COUNT(*) as total FROM promociones WHERE estadoPromo='pendiente'")->fetch_assoc()['total'];
} catch (Exception $e) {}
try {
$stats['locales_activos'] = $conn->query("SELECT COUNT(*) as total FROM locales WHERE estado='activo'")->fetch_assoc()['total'];
} catch (Exception $e) {}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">🛍️ Shopping Rosario - Admin</a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text text-white me-3">
                    Hola, <?php echo $nombre; ?> (<?php echo ucfirst($rol); ?>)
                </span>
                <a href="logout.php" class="btn btn-outline-light">Cerrar Sesión</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Estadísticas -->
        <?php if ($rol == 'admin'): ?>
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-white bg-primary">
                    <div class="card-body text-center">
                        <h5><?php echo $stats['total_usuarios']; ?></h5>
                        <p>Total Usuarios</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-success">
                    <div class="card-body text-center">
                        <h5><?php echo $stats['total_locales']; ?></h5>
                        <p>Locales Activos</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-warning">
                    <div class="card-body text-center">
                        <h5><?php echo $stats['pendientes_aprobacion']; ?></h5>
                        <p>Dueños Pendientes</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-info">
                    <div class="card-body text-center">
                        <h5><?php echo $stats['promociones_pendientes']; ?></h5>
                        <p>Promos Pendientes</p>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h2>Panel de Control</h2>
                        <p>Rol: <strong><?php echo ucfirst($rol); ?></strong></p>
                        
                        <?php if ($rol == 'admin'): ?>
                            <div class="alert alert-info">
                                <h5>Panel de Administrador</h5>
                                <div class="row mt-3">
                                    <div class="col-md-3 mb-3">
                                        <a href="admin/admin_usuarios.php" class="btn btn-primary w-100">
                                            👥 Usuarios<br>
                                            <small><?php echo $stats['pendientes_aprobacion']; ?> pendientes</small>
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <a href="admin/admin_locales.php" class="btn btn-success w-100">
                                            🏪 Locales<br>
                                            <small><?php echo $stats['total_locales']; ?> activos</small>
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <a href="admin/admin_promociones.php" class="btn btn-warning w-100">
                                            🎁 Promociones<br>
                                            <small><?php echo $stats['promociones_pendientes']; ?> pendientes</small>
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <a href="admin/admin_novedades.php" class="btn btn-info w-100">📢 Novedades</a>
                                    </div>
                                </div>
                                <div class="row mt-2">
                                    <div class="col-md-3 mb-3">
                                        <a href="admin/admin_reportes.php" class="btn btn-dark w-100">📊 Reportes</a>
                                    </div>
                                </div>
                            </div>
                        <?php elseif ($rol == 'dueno'): ?>
                            <div class="alert alert-warning">
                                <h5>Panel de Dueño de Local</h5>
                                <p>Gestiona las promociones de tu local.</p>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-success">
                                <h5>Panel de Cliente</h5>
                                <p>Explora y utiliza las promociones disponibles.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>