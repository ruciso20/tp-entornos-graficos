<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$rol = $_SESSION['rol'];
$nombre = $_SESSION['nombre'];
$categoria = $_SESSION['categoria'];

// determinar el titulo según el tipo de usuario
$titulo_dashboard = "Shopping Rosario - ";
switch ($rol) {
    case 'admin':
        $titulo_dashboard .= "Administrador";
        break;
    case 'dueno':
        $titulo_dashboard .= "Dueño de Local";
        break;
    case 'cliente':
        $titulo_dashboard .= "Cliente";
        break;
    default:
        $titulo_dashboard .= "Usuario";
}

// conexión a la base de datos
include("config/db.php");

// estadisticas específicas por tipo de usuario

if ($rol == 'cliente') {
    // contar promociones disponibles para el cliente segun su categoria
    $promociones_disponibles = $conn->query("
        SELECT COUNT(*) as total 
        FROM promociones 
        WHERE estado = 'aprobada' 
        AND fecha_fin >= CURDATE()
        AND fecha_inicio <= CURDATE()
        AND (
            categoria_minima = '$categoria' 
            OR categoria_minima = 'Inicial'
            OR ('$categoria' = 'Premium' AND categoria_minima IN ('Inicial', 'Medium', 'Premium'))
            OR ('$categoria' = 'Medium' AND categoria_minima IN ('Inicial', 'Medium'))
        )
    ")->fetch_assoc()['total'];

    // contar las novedades disponibles para cliente
    $novedades_disponibles = $conn->query("
        SELECT COUNT(*) as total 
        FROM novedades 
        WHERE fecha_fin >= CURDATE() 
        AND fecha_inicio <= CURDATE()
        AND estado = 'activa'
        AND (
            categoria_objetivo = '$categoria'
            OR categoria_objetivo = 'Inicial'
            OR ('$categoria' = 'Premium' AND categoria_objetivo IN ('Inicial', 'Medium', 'Premium'))
            OR ('$categoria' = 'Medium' AND categoria_objetivo IN ('Inicial', 'Medium'))
        )
    ")->fetch_assoc()['total'];

    // obtener las promociones recientes para cliente
    $promociones_recientes = $conn->query("
        SELECT p.*, l.nombre as local_nombre 
        FROM promociones p 
        LEFT JOIN locales l ON p.local_id = l.id 
        WHERE p.estado = 'aprobada' 
        AND p.fecha_fin >= CURDATE()
        AND p.fecha_inicio <= CURDATE()
        AND (
            p.categoria_minima = '$categoria' 
            OR p.categoria_minima = 'Inicial'
            OR ('$categoria' = 'Premium' AND p.categoria_minima IN ('Inicial', 'Medium', 'Premium'))
            OR ('$categoria' = 'Medium' AND p.categoria_minima IN ('Inicial', 'Medium'))
        )
        ORDER BY p.id DESC LIMIT 5
    ");

    // obtener las novedades recientes para cliente
    $novedades_recientes = $conn->query("
        SELECT * FROM novedades 
        WHERE fecha_fin >= CURDATE() 
        AND fecha_inicio <= CURDATE()
        AND estado = 'activa'
        AND (
            categoria_objetivo = '$categoria'
            OR categoria_objetivo = 'Inicial'
            OR ('$categoria' = 'Premium' AND categoria_objetivo IN ('Inicial', 'Medium', 'Premium'))
            OR ('$categoria' = 'Medium' AND categoria_objetivo IN ('Inicial', 'Medium'))
        )
        ORDER BY id DESC LIMIT 5
    ");
} elseif ($rol == 'admin') {

    // estadísticas para administrador
    $total_locales = $conn->query("SELECT COUNT(*) as total FROM locales WHERE estado = 'activo'")->fetch_assoc()['total'];
    $dueños_pendientes = $conn->query("SELECT COUNT(*) as total FROM usuarios WHERE rol = 'dueno' AND estado = 'pendiente'")->fetch_assoc()['total'];
    $promociones_pendientes = $conn->query("SELECT COUNT(*) as total FROM promociones WHERE estado = 'pendiente'")->fetch_assoc()['total'];
    $total_clientes = $conn->query("SELECT COUNT(*) as total FROM usuarios WHERE rol = 'cliente' AND estado = 'aprobado'")->fetch_assoc()['total'];

    // obtener datos para las listas
    $locales = $conn->query("SELECT l.*, u.nombre as dueno_nombre FROM locales l LEFT JOIN usuarios u ON l.dueno_id = u.id WHERE l.estado = 'activo' ORDER BY l.id DESC LIMIT 5");
    $usuarios_pendientes = $conn->query("SELECT * FROM usuarios WHERE estado = 'pendiente' AND rol = 'dueno' ORDER BY fecha_registro DESC LIMIT 5");
    $promociones_recientes = $conn->query("SELECT p.*, l.nombre as local_nombre FROM promociones p LEFT JOIN locales l ON p.local_id = l.id ORDER BY p.id DESC LIMIT 5");
    $novedades_recientes = $conn->query("SELECT * FROM novedades ORDER BY fecha_inicio DESC LIMIT 5");
} elseif ($rol == 'dueno') {

    // estadísticas para dueño
    $dueño_id = $_SESSION['user_id'];

    // CORREGIDO: Obtener TODOS los locales del dueño
    $locales_query = $conn->query("SELECT * FROM locales WHERE dueno_id = $dueño_id AND estado = 'activo'");
    $locales_data = $locales_query->fetch_all(MYSQLI_ASSOC);

    // CORREGIDO: Contar TODOS los locales activos del dueño
    $locales_activas = count($locales_data);

    // Si tiene al menos un local, usar el primero como principal
    if ($locales_activas > 0) {
        $local_principal = $locales_data[0];
        $local_id = $local_principal['id'];
        $nombre_local = $local_principal['nombre'];

        // CORREGIDO: Promociones activas de TODOS los locales del dueño
        $promociones_activas = $conn->query("
            SELECT COUNT(*) as total FROM promociones 
            WHERE local_id IN (SELECT id FROM locales WHERE dueno_id = $dueño_id) 
            AND estado = 'aprobada' 
            AND fecha_inicio <= CURDATE() AND fecha_fin >= CURDATE()
        ")->fetch_assoc()['total'];

        // CORREGIDO: Solicitudes pendientes de TODOS los locales del dueño
        $solicitudes_pendientes = $conn->query("
            SELECT COUNT(*) as total FROM uso_promociones up
            JOIN promociones p ON up.promocion_id = p.id
            WHERE p.local_id IN (SELECT id FROM locales WHERE dueno_id = $dueño_id) 
            AND up.estado = 'pendiente'
        ")->fetch_assoc()['total'];

        // CORREGIDO: Total de usos aprobados de TODOS los locales del dueño
        $usos_totales = $conn->query("
            SELECT COUNT(*) as total FROM uso_promociones up
            JOIN promociones p ON up.promocion_id = p.id
            WHERE p.local_id IN (SELECT id FROM locales WHERE dueno_id = $dueño_id) 
            AND up.estado = 'usada'
        ")->fetch_assoc()['total'];

        // Obtener promociones del dueño (de todos los locales)
        $mis_promociones = $conn->query("
            SELECT p.*, l.nombre as local_nombre 
            FROM promociones p 
            JOIN locales l ON p.local_id = l.id 
            WHERE l.dueno_id = $dueño_id 
            ORDER BY p.id DESC LIMIT 5
        ");

        // Obtener solicitudes recientes del dueño (de todos los locales)
        $solicitudes_recientes = $conn->query("
            SELECT up.*, u.nombre as cliente_nombre, p.titulo as promocion_titulo, l.nombre as local_nombre
            FROM uso_promociones up
            JOIN promociones p ON up.promocion_id = p.id
            JOIN usuarios u ON up.cliente_id = u.id
            JOIN locales l ON p.local_id = l.id
            WHERE l.dueno_id = $dueño_id
            ORDER BY up.fecha_uso DESC LIMIT 5
        ");
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title><?php echo $titulo_dashboard; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .card-stat {
            transition: transform 0.3s;
        }

        .card-stat:hover {
            transform: translateY(-5px);
        }

        .card-hover:hover {
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .navbar-brand {
            font-weight: bold;
        }

        .table-sm td,
        .table-sm th {
            padding: 0.5rem;
        }

        .badge-estado {
            font-size: 0.7rem;
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
                <strong>
                    <?php
                    switch ($rol) {
                        case 'administrador':
                            echo '' . $titulo_dashboard;
                            break;
                        case 'dueno':
                            echo '' . $titulo_dashboard;
                            break;
                        case 'cliente':
                            echo '' . $titulo_dashboard;
                            break;
                        default:
                            echo '' . $titulo_dashboard;
                    }
                    ?>
                </strong>
            </a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text text-white me-3">
                    <i class="fas fa-user"></i> Hola, <?php echo $nombre; ?>
                    <?php if ($rol == 'cliente'): ?>
                        <span class="badge bg-info"><?php echo $categoria; ?></span>
                    <?php endif; ?>
                </span>

                <!-- boton home -->
                <a href="index.php" class="btn btn-outline-light me-2">
                    <i class="fas fa-home"></i> Inicio
                </a>

                <!-- boton logout -->
                <a href="logout.php" class="btn btn-outline-light">
                    <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">

        <!-- estadisticas propias segun el tipo de usuario -->
        <?php if ($rol == 'cliente'): ?>

            <!-- estadisticas para el cliente -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card card-stat text-white bg-primary">
                        <div class="card-body text-center">
                            <h3><?php echo $promociones_disponibles; ?></h3>
                            <p><i class="fas fa-tags"></i> Promociones Disponibles</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card card-stat text-white bg-success">
                        <div class="card-body text-center">
                            <h3><?php echo $novedades_disponibles; ?></h3>
                            <p><i class="fas fa-newspaper"></i> Novedades Activas</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card card-stat text-white bg-info">
                        <div class="card-body text-center">
                            <h3><?php echo $categoria; ?></h3>
                            <p><i class="fas fa-star"></i> Tu Categoría</p>
                        </div>
                    </div>
                </div>
            </div>

        <?php elseif ($rol == 'admin'): ?>


            <!-- estadisticas para admin -->

            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card card-stat text-white bg-primary">
                        <div class="card-body text-center">
                            <h3><?php echo $total_locales; ?></h3>
                            <p><i class="fas fa-store"></i> Locales Activos</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card card-stat text-white bg-warning">
                        <div class="card-body text-center">
                            <h3><?php echo $dueños_pendientes; ?></h3>
                            <p><i class="fas fa-user-clock"></i> Dueños Pendientes</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card card-stat text-white bg-danger">
                        <div class="card-body text-center">
                            <h3><?php echo $promociones_pendientes; ?></h3>
                            <p><i class="fas fa-tags"></i> Promociones Pendientes</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card card-stat text-white bg-success">
                        <div class="card-body text-center">
                            <h3><?php echo $total_clientes; ?></h3>
                            <p><i class="fas fa-users"></i> Total Clientes</p>
                        </div>
                    </div>
                </div>
            </div>

        <?php elseif ($rol == 'dueno' && isset($local_id)): ?>


            <!-- estadisticas para dueño -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card card-stat text-white bg-primary">
                        <div class="card-body text-center">
                            <h3><?php echo $locales_activas; ?></h3>
                            <p>Locales Activos</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card card-stat text-white bg-success">
                        <div class="card-body text-center">
                            <h3><?php echo $promociones_activas; ?></h3>
                            <p>Promociones Activas</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card card-stat text-white bg-warning">
                        <div class="card-body text-center">
                            <h3><?php echo $solicitudes_pendientes; ?></h3>
                            <p>Solicitudes Pendientes</p>
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
                        <?php if ($rol == 'admin'): ?>


                            <!-- panel admin -->


                            <div class="alert alert-info">
                                <h5><i class="fas fa-crown"></i> Panel de Administrador</h5>
                                <p class="mb-3">¡Bienvenido! Gestiona todo el sistema del shopping.</p>

                                <div class="row mt-3">
                                    <div class="col-md-3 mb-3">
                                        <a href="admin/admin_usuarios.php" class="btn btn-primary w-100">
                                            <i class="fas fa-users"></i> Gestionar Usuarios
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <a href="admin/admin_locales.php" class="btn btn-success w-100">
                                            <i class="fas fa-store"></i> Gestionar Locales
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <a href="admin/admin_promociones.php" class="btn btn-warning w-100">
                                            <i class="fas fa-tags"></i> Aprobar Promociones
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <a href="admin/admin_novedades.php" class="btn btn-info w-100">
                                            <i class="fas fa-newspaper"></i> Gestionar Novedades
                                        </a>
                                    </div>
                                </div>
                            </div>

                        <?php elseif ($rol == 'cliente'): ?>

                            <!-- panel cliente -->

                            <div class="alert alert-success">
                                <h5><i class="fas fa-user"></i> Panel de Cliente</h5>
                                <p>¡Bienvenido <strong><?php echo $nombre; ?></strong>! Disfruta de tus beneficios como cliente <strong><?php echo $categoria; ?></strong>.</p>

                                <div class="row mt-4">
                                    <div class="col-md-6 mb-3">
                                        <div class="card card-hover h-100">
                                            <div class="card-body text-center">
                                                <h3><i class="fas fa-tags"></i> Promociones</h3>
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
                                                <h3><i class="fas fa-newspaper"></i> Novedades</h3>
                                                <p>Mantente informado de las últimas novedades del shopping</p>
                                                <a href="cliente/novedades.php" class="btn btn-info w-100">
                                                    Ver Novedades (<?php echo $novedades_disponibles; ?>)
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- progreso de categoria de un cliente -->

                                <div class="card mt-4">
                                    <div class="card-header">
                                        <h6><i class="fas fa-chart-line"></i> Tu Progreso de Categoría</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row text-center">
                                            <div class="col-md-4">
                                                <div class="card <?php echo $categoria == 'Inicial' ? 'bg-primary text-white' : 'bg-light'; ?>">
                                                    <div class="card-body">
                                                        <h5>Inicial</h5>
                                                        <p>Promociones básicas</p>
                                                        <?php echo $categoria == 'Inicial' ? '<span class="badge bg-warning">ACTUAL</span>' : ''; ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="card <?php echo $categoria == 'Medium' ? 'bg-warning text-dark' : 'bg-light'; ?>">
                                                    <div class="card-body">
                                                        <h5>Medium</h5>
                                                        <p>+ Promociones exclusivas</p>
                                                        <?php echo $categoria == 'Medium' ? '<span class="badge bg-warning">ACTUAL</span>' : ''; ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="card <?php echo $categoria == 'Premium' ? 'bg-danger text-white' : 'bg-light'; ?>">
                                                    <div class="card-body">
                                                        <h5>Premium</h5>
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

                        <?php elseif ($rol == 'dueno' && isset($local_id)): ?>

                            <!-- panel dueño (con local) -->

                            <div class="alert alert-warning">
                                <p class="mb-3">¡Bienvenido <strong><?php echo $nombre; ?></strong>!</p>
                                <div class="row mt-3">
                                    <div class="col-md-3 mb-3">
                                        <a href="dueno/locales.php" class="btn btn-primary w-100">
                                            Gestionar Locales
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <a href="dueno/promociones.php" class="btn btn-success w-100">
                                            Gestionar Promociones
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <a href="dueno/solicitudes.php" class="btn btn-warning w-100">
                                            Gestionar Solicitudes
                                            <?php if ($solicitudes_pendientes > 0): ?>
                                                <span class="badge bg-danger"><?php echo $solicitudes_pendientes; ?></span>
                                            <?php endif; ?>
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <a href="dueno/reportes.php" class="btn btn-info w-100">
                                            Ver Reportes
                                        </a>
                                    </div>
                                </div>
                            </div>

                        <?php elseif ($rol == 'dueno'): ?>

                            <!-- panel dueño (sin local) -->

                            <div class="alert alert-danger">
                                <h5><i class="fas fa-exclamation-triangle"></i>Dueño sin Local Asignado</h5>
                                <p>No tienes un local asignado ¡Contacta al administrador del sistema para que te asigne un local!</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>