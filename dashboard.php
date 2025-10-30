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
$titulo_dashboard = "Stella Shopping Rosario - ";
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

    //contar promociones usadas por el cliente
    $promociones_usadas = $conn->query("
        SELECT COUNT(*) as total 
        FROM uso_promociones 
        WHERE cliente_id = {$_SESSION['user_id']} 
        AND estado = 'usada'
    ")->fetch_assoc()['total'];
} elseif ($rol == 'admin') {

    // estadísticas para administrador
    $total_locales = $conn->query("SELECT COUNT(*) as total FROM locales WHERE estado = 'aprobado'")->fetch_assoc()['total'];
    $dueños_pendientes = $conn->query("SELECT COUNT(*) as total FROM usuarios WHERE rol = 'dueno' AND estado = 'pendiente'")->fetch_assoc()['total'];
    $promociones_pendientes = $conn->query("SELECT COUNT(*) as total FROM promociones WHERE estado = 'pendiente'")->fetch_assoc()['total'];
    $total_clientes = $conn->query("SELECT COUNT(*) as total FROM usuarios WHERE rol = 'cliente' AND estado = 'aprobado'")->fetch_assoc()['total'];

    // obtener datos para las listas
    $locales = $conn->query("SELECT l.*, u.nombre as dueno_nombre FROM locales l LEFT JOIN usuarios u ON l.dueno_id = u.id WHERE l.estado = 'aprobado' ORDER BY l.id DESC LIMIT 5");
    $usuarios_pendientes = $conn->query("SELECT * FROM usuarios WHERE estado = 'pendiente' AND rol = 'dueno' ORDER BY fecha_registro DESC LIMIT 5");
    $promociones_recientes = $conn->query("SELECT p.*, l.nombre as local_nombre FROM promociones p LEFT JOIN locales l ON p.local_id = l.id ORDER BY p.id DESC LIMIT 5");
    $novedades_recientes = $conn->query("SELECT * FROM novedades ORDER BY fecha_inicio DESC LIMIT 5");
} elseif ($rol == 'dueno') {

    // estadísticas para dueño
    $dueño_id = $_SESSION['user_id'];

    // Obtener TODOS los locales del dueño con sus estados
    $locales_query = $conn->query("
        SELECT *, 
            CASE 
                WHEN estado = 'aprobado' THEN 1
                WHEN estado = 'pendiente' THEN 2 
                WHEN estado = 'rechazado' THEN 3
                ELSE 4
            END as orden
        FROM locales 
        WHERE dueno_id = $dueño_id 
        ORDER BY orden, nombre
    ");
    $locales_data = $locales_query->fetch_all(MYSQLI_ASSOC);

    // Contar locales por estado
    $locales_aprobados = 0;
    $locales_pendientes = 0;
    $locales_rechazados = 0;

    foreach ($locales_data as $local) {
        switch ($local['estado']) {
            case 'aprobado':
                $locales_aprobados++;
                break;
            case 'pendiente':
                $locales_pendientes++;
                break;
            case 'rechazado':
                $locales_rechazados++;
                break;
        }
    }

    $total_locales = count($locales_data);

    // Si tiene locales aprobados, calcular estadísticas
    if ($locales_aprobados > 0) {
        // Promociones activas de TODOS los locales aprobados del dueño
        $promociones_activas = $conn->query("
            SELECT COUNT(*) as total FROM promociones 
            WHERE local_id IN (
                SELECT id FROM locales WHERE dueno_id = $dueño_id AND estado = 'aprobado'
            ) 
            AND estado = 'aprobada' 
            AND fecha_inicio <= CURDATE() AND fecha_fin >= CURDATE()
        ")->fetch_assoc()['total'];

        // Solicitudes pendientes de TODOS los locales aprobados del dueño
        $solicitudes_pendientes = $conn->query("
            SELECT COUNT(*) as total FROM uso_promociones up
            JOIN promociones p ON up.promocion_id = p.id
            WHERE p.local_id IN (
                SELECT id FROM locales WHERE dueno_id = $dueño_id AND estado = 'aprobado'
            ) 
            AND up.estado = 'pendiente'
        ")->fetch_assoc()['total'];

        // Total de usos aprobados de TODOS los locales aprobados del dueño
        $usos_totales = $conn->query("
            SELECT COUNT(*) as total FROM uso_promociones up
            JOIN promociones p ON up.promocion_id = p.id
            WHERE p.local_id IN (
                SELECT id FROM locales WHERE dueno_id = $dueño_id AND estado = 'aprobado'
            ) 
            AND up.estado = 'usada'
        ")->fetch_assoc()['total'];
    } else {
        // Si no tiene locales aprobados, las estadísticas son 0
        $promociones_activas = 0;
        $solicitudes_pendientes = 0;
        $usos_totales = 0;
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
                <!-- mostrar nombre del usuario que ingresó -->
                <span class="navbar-text text-white me-3">Hola, <?php echo $nombre; ?></span>

                <!-- boton home -->
                <a href="index.php" class="btn btn-outline-light me-2">Inicio</a>

                <!-- boton logout -->
                <a href="logout.php" class="btn btn-outline-light">Cerrar Sesión</a>
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
                            <p>Promociones Disponibles</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card card-stat text-white bg-info">
                        <div class="card-body text-center">
                            <h3><?php echo $novedades_disponibles; ?></h3>
                            <p>Novedades Activas</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card card-stat text-white bg-success">
                        <div class="card-body text-center">
                            <h3><?php echo $categoria; ?></h3>
                            <p>Tu Categoría</p>
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
                            <p>Locales Activos</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card card-stat text-white bg-success">
                        <div class="card-body text-center">
                            <h3><?php echo $total_clientes; ?></h3>
                            <p>Total Clientes</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card card-stat text-white bg-warning">
                        <div class="card-body text-center">
                            <h3><?php echo $dueños_pendientes; ?></h3>
                            <p>Dueños Pendientes</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card card-stat text-white bg-danger">
                        <div class="card-body text-center">
                            <h3><?php echo $promociones_pendientes; ?></h3>
                            <p>Promociones Pendientes</p>
                        </div>
                    </div>
                </div>
            </div>

        <?php elseif ($rol == 'dueno'): ?>


            <!-- estadisticas para dueño -->

            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card card-stat text-white bg-primary">
                        <div class="card-body text-center">
                            <h3><?php echo $locales_aprobados; ?></h3>
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
                                <h5>Panel de Administrador</h5>
                                <p class="mb-3">¡Bienvenido! Gestiona todo el sistema del shopping.</p>

                                <div class="row mt-3">
                                    <div class="col-md-3 mb-3">
                                        <a href="admin/admin_locales.php" class="btn btn-primary w-100">
                                            Gestionar Locales
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <a href="admin/admin_usuarios.php" class="btn btn-success w-100">
                                            Gestionar Usuarios
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <a href="admin/admin_promociones.php" class="btn btn-danger w-100">
                                            Aprobar Promociones
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <a href="admin/admin_novedades.php" class="btn btn-info w-100">
                                            Gestionar Novedades
                                        </a>
                                    </div>
                                </div>
                            </div>

                        <?php elseif ($rol == 'cliente'): ?>

                            <!-- panel cliente -->
                            <div class="alert alert-success">
                                <h5>Panel de Cliente</h5>
                                <p>¡Bienvenido <strong><?php echo $nombre; ?></strong>! Disfruta de tus beneficios</p>

                                <!-- Fila 1 con las funcionalidades principales -->
                                <div class="row mt-4 justify-content-center">
                                    <div class="col-md-5 mb-3">
                                        <div class="card card-hover h-100">
                                            <div class="card-body text-center">
                                                <h3>Promociones</h3>
                                                <p>Descubre y utiliza promociones exclusivas según tu categoría</p>
                                                <a href="cliente/promociones.php" class="btn btn-primary w-100">
                                                    Ver Promociones
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-5 mb-3">
                                        <div class="card card-hover h-100">
                                            <div class="card-body text-center">
                                                <h3>Novedades</h3>
                                                <p>Mantente informado de las últimas novedades del shopping</p>
                                                <a href="cliente/novedades.php" class="btn btn-info w-100">
                                                    Ver Novedades
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Fila 2 con funcionalidades secundarias -->
                                <div class="row mt-4 justify-content-center">
                                    <div class="col-md-5 mb-3">
                                        <div class="card card-hover h-100">
                                            <div class="card-body text-center">
                                                <h3>Historial</h3>
                                                <p>Revisa todas las promociones que has utilizado</p>
                                                <a href="cliente/historial.php" class="btn btn-warning w-100">
                                                    Ver Historial
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-5 mb-3">
                                        <div class="card card-hover h-100">
                                            <div class="card-body text-center">
                                                <h3>Mi Perfil</h3>
                                                <p>Gestiona tu información personal y preferencias</p>
                                                <a href="cliente/perfil.php" class="btn btn-success w-100">
                                                    Editar Perfil
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Progreso de categoría de un cliente -->


                                <div class="row justify-content-center">
                                    <div class="col-md-10"> <!-- Mismo ancho que las cards de arriba -->
                                        <div class="card mt-4">
                                            <div class="card-header text-center">
                                                <h6 class="mb-0">Tu Progreso de Categoría</h6>
                                            </div>
                                            <div class="card-body">
                                                <div class="row justify-content-center">
                                                    <div class="col-md-4 mb-3">
                                                        <div class="card <?php echo $categoria == 'Inicial' ? 'bg-primary text-white' : 'bg-light'; ?> h-100">
                                                            <div class="card-body text-center">
                                                                <h5>Inicial</h5>
                                                                <p class="small">Promociones básicas</p>
                                                                <?php echo $categoria == 'Inicial' ? '<span class="badge bg-warning">ACTUAL</span>' : ''; ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4 mb-3">
                                                        <div class="card <?php echo $categoria == 'Medium' ? 'bg-warning text-dark' : 'bg-light'; ?> h-100">
                                                            <div class="card-body text-center">
                                                                <h5>Medium</h5>
                                                                <p class="small">+ Promociones exclusivas</p>
                                                                <?php echo $categoria == 'Medium' ? '<span class="badge bg-warning">ACTUAL</span>' : ''; ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4 mb-3">
                                                        <div class="card <?php echo $categoria == 'Premium' ? 'bg-danger text-white' : 'bg-light'; ?> h-100">
                                                            <div class="card-body text-center">
                                                                <h5>Premium</h5>
                                                                <p class="small">Todas las promociones + beneficios VIP</p>
                                                                <?php echo $categoria == 'Premium' ? '<span class="badge bg-warning">ACTUAL</span>' : ''; ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="text-center mt-3">
                                                    <p class="text-muted mb-0">
                                                        <small>💡 <strong>Consejo:</strong> Usa más promociones para subir de categoría y desbloquear beneficios exclusivos.</small>
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- panel dueño  -->

                            <?php elseif ($rol == 'dueno'): ?>
                                <div class="alert alert-warning">
                                    <h5>Panel de Dueño de Locales</h5>
                                    <p class="mb-3">¡Bienvenido <strong><?php echo $nombre; ?></strong>! Gestiona tus locales y promociones.</p>

                                    <div class="row mt-3">
                                        <div class="col-md-3 mb-3">
                                            <a href="dueno/locales.php" class="btn btn-primary w-100">
                                                Gestionar Locales
                                            </a>
                                        </div>

                                        <div class="col-md-3 mb-3">
                                            <?php if ($locales_aprobados > 0): ?>
                                                <a href="dueno/promociones.php" class="btn btn-success w-100">
                                                    Gestionar Promociones
                                                </a>
                                            <?php else: ?>
                                                <button class="btn btn-secondary w-100" disabled>
                                                    Gestionar Promociones
                                                </button>
                                            <?php endif; ?>
                                        </div>

                                        <div class="col-md-3 mb-3">
                                            <?php if ($locales_aprobados > 0): ?>
                                                <a href="dueno/solicitudes.php" class="btn btn-warning w-100">
                                                    Gestionar Solicitudes
                                                    <?php if ($solicitudes_pendientes > 0): ?>
                                                        <span class="badge bg-danger"><?php echo $solicitudes_pendientes; ?></span>
                                                    <?php endif; ?>
                                                </a>
                                            <?php else: ?>
                                                <button class="btn btn-secondary w-100" disabled>
                                                    Gestionar Solicitudes
                                                </button>
                                            <?php endif; ?>
                                        </div>

                                        <div class="col-md-3 mb-3">
                                            <?php if ($locales_aprobados > 0): ?>
                                                <a href="dueno/reportes.php" class="btn btn-dark w-100">
                                                    Reportes
                                                </a>
                                            <?php else: ?>
                                                <button class="btn btn-secondary w-100" disabled>
                                                    Reportes
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                            </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- footer -->
    <?php include('footer.php'); ?>
    <!-- bootstrap -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>