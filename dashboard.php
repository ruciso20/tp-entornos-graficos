<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$rol = $_SESSION['rol'];
$nombre = $_SESSION['nombre'];
$categoria = $_SESSION['categoria'];

// Estadísticas para el cliente
include("config/db.php");

// Contar promociones disponibles
$promociones_disponibles = $conn->query("
    SELECT COUNT(*) as total 
    FROM promociones 
    WHERE estadoPromo = 'aprobada' 
    AND fechaHastaPromo >= CURDATE()
    AND (
        categoriaCliente = '$categoria' 
        OR categoriaCliente = 'Inicial'
        OR ('$categoria' = 'Premium' AND categoriaCliente IN ('Inicial', 'Medium', 'Premium'))
        OR ('$categoria' = 'Medium' AND categoriaCliente IN ('Inicial', 'Medium'))
    )
")->fetch_assoc()['total'];

// Contar novedades disponibles
$novedades_disponibles = $conn->query("
    SELECT COUNT(*) as total 
    FROM novedades 
    WHERE fecha_fin >= CURDATE() 
    AND estado = 'activa'
    AND (
        categoria_objetivo = '$categoria'
        OR categoria_objetivo = 'Inicial'
        OR ('$categoria' = 'Premium' AND categoria_objetivo IN ('Inicial', 'Medium', 'Premium'))
        OR ('$categoria' = 'Medium' AND categoria_objetivo IN ('Inicial', 'Medium'))
    )
")->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - Cliente</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .card-stat {
            transition: transform 0.3s;
        }
        .card-stat:hover {
            transform: translateY(-5px);
        }
        .progress {
            height: 20px;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <strong>🛍️ Shopping Rosario - Cliente</strong>
            </a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text text-white me-3">
                    Hola, <?php echo $nombre; ?> 
                    <span class="badge bg-<?php 
                        echo $categoria == 'Premium' ? 'danger' : 
                             ($categoria == 'Medium' ? 'warning' : 'primary'); 
                    ?>">
                        <?php echo $categoria; ?>
                    </span>
                </span>
                <a href="logout.php" class="btn btn-outline-light">Cerrar Sesión</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Estadísticas para cliente -->
        <?php if ($rol == 'cliente'): ?>
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card card-stat text-white bg-primary">
                    <div class="card-body text-center">
                        <h3><?php echo $promociones_disponibles; ?></h3>
                        <p>Promociones Disponibles</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card card-stat text-white bg-success">
                    <div class="card-body text-center">
                        <h3><?php echo $novedades_disponibles; ?></h3>
                        <p>Novedades Activas</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card card-stat text-white bg-info">
                    <div class="card-body text-center">
                        <h3><?php echo $categoria; ?></h3>
                        <p>Tu Categoría</p>
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
                            <!-- Panel Admin (ya existe) -->
                            <div class="alert alert-info">
                                <h5>Panel de Administrador</h5>
                                <div class="row mt-3">
                                    <div class="col-md-3 mb-3">
                                        <a href="admin/admin_usuarios.php" class="btn btn-primary w-100">👥 Usuarios</a>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <a href="admin/admin_locales.php" class="btn btn-success w-100">🏪 Locales</a>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <a href="admin/admin_promociones.php" class="btn btn-warning w-100">🎁 Promociones</a>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <a href="admin/admin_novedades.php" class="btn btn-info w-100">📢 Novedades</a>
                                    </div>
                                </div>
                            </div>

                        <?php elseif ($rol == 'cliente'): ?>
                            <!-- PANEL CLIENTE -->
                            <div class="alert alert-success">
                                <h5>🎯 Panel de Cliente</h5>
                                <p>Bienvenido <strong><?php echo $nombre; ?></strong>. Disfruta de tus beneficios como cliente <strong><?php echo $categoria; ?></strong>.</p>
                                
                                <div class="row mt-4">
                                    <div class="col-md-6 mb-3">
                                        <div class="card card-hover h-100">
                                            <div class="card-body text-center">
                                                <h3>🎁 Promociones</h3>
                                                <p>Descubre y utiliza promociones exclusivas según tu categoría</p>
                                                <a href="cliente/promociones.php" class="btn btn-primary w-100">
                                                    Ver Promociones (<?php echo $promociones_disponibles; ?>)
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <div class="card card-hover h-100">
                                            <div class="card-body text-center">
                                                <h3>📢 Novedades</h3>
                                                <p>Mantente informado de las últimas novedades del shopping</p>
                                                <a href="cliente/novedades.php" class="btn btn-info w-100">
                                                    Ver Novedades (<?php echo $novedades_disponibles; ?>)
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row mt-2">
                                    <div class="col-md-6 mb-3">
                                        <div class="card card-hover h-100">
                                            <div class="card-body text-center">
                                                <h3>👤 Mi Perfil</h3>
                                                <p>Gestiona tu perfil y revisa tu progreso de categoría</p>
                                                <a href="cliente/perfil.php" class="btn btn-warning w-100">Mi Perfil</a>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <div class="card card-hover h-100">
                                            <div class="card-body text-center">
                                                <h3>📊 Mi Historial</h3>
                                                <p>Revisa las promociones que has utilizado</p>
                                                <a href="cliente/historial.php" class="btn btn-secondary w-100">Mi Historial</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Progreso de categoría -->
                                <div class="card mt-4">
                                    <div class="card-header">
                                        <h6>📈 Tu Progreso de Categoría</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row text-center">
                                            <div class="col-md-4">
                                                <div class="card <?php echo $categoria == 'Inicial' ? 'bg-primary text-white' : 'bg-light'; ?>">
                                                    <div class="card-body">
                                                        <h5>👤 Inicial</h5>
                                                        <p>Promociones básicas</p>
                                                        <?php echo $categoria == 'Inicial' ? '<span class="badge bg-warning">ACTUAL</span>' : ''; ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="card <?php echo $categoria == 'Medium' ? 'bg-warning text-dark' : 'bg-light'; ?>">
                                                    <div class="card-body">
                                                        <h5>👥 Medium</h5>
                                                        <p>+ Promociones exclusivas</p>
                                                        <?php echo $categoria == 'Medium' ? '<span class="badge bg-warning">ACTUAL</span>' : ''; ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="card <?php echo $categoria == 'Premium' ? 'bg-danger text-white' : 'bg-light'; ?>">
                                                    <div class="card-body">
                                                        <h5>⭐ Premium</h5>
                                                        <p>Todas las promociones + beneficios VIP</p>
                                                        <?php echo $categoria == 'Premium' ? '<span class="badge bg-warning">ACTUAL</span>' : ''; ?>
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

                        <?php elseif ($rol == 'dueno'): ?>
                            <!-- Panel Dueño (ya existe) -->
                            <div class="alert alert-warning">
                                <h5>Panel de Dueño de Local</h5>
                                <p>Gestiona las promociones de tu local.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>