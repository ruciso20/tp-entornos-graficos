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

// SINCRONIZAR CATEGORÍA PRIMERO
$cat_query = $conn->prepare("SELECT categoria_cliente FROM usuarios WHERE id = ?");
$cat_query->bind_param("i", $_SESSION['user_id']);
$cat_query->execute();
$cat_result = $cat_query->get_result();
$usuario_data = $cat_result->fetch_assoc();

if ($usuario_data) {
    $_SESSION['categoria_cliente'] = $usuario_data['categoria_cliente'];
    $_SESSION['categoria'] = $usuario_data['categoria_cliente']; // ACTUALIZAR AMBAS
}


// estadisticas específicas por tipo de usuario

// estadisticas específicas por tipo de usuario

if ($rol == 'cliente') {
    // contar promociones disponibles para el cliente segun su categoria (CORREGIDO)
    $promociones_disponibles = $conn->query("
        SELECT COUNT(*) as total 
        FROM promociones 
        WHERE estado = 'aprobada' 
        AND fecha_fin >= CURDATE()
        AND fecha_inicio <= CURDATE()
        AND (
            categoria_minima = 'Inicial'
            OR (categoria_minima = 'Medium' AND '$categoria' IN ('Medium', 'Premium'))
            OR (categoria_minima = 'Premium' AND '$categoria' = 'Premium')
        )
    ")->fetch_assoc()['total'];

    // contar las novedades disponibles para cliente (CORREGIDO)
    $novedades_disponibles = $conn->query("
        SELECT COUNT(*) as total 
        FROM novedades 
        WHERE fecha_fin >= CURDATE() 
        AND fecha_inicio <= CURDATE()
        AND estado = 'activa'
        AND (
            categoria_objetivo = 'Inicial'
            OR (categoria_objetivo = 'Medium' AND '$categoria' IN ('Medium', 'Premium'))
            OR (categoria_objetivo = 'Premium' AND '$categoria' = 'Premium')
        )
    ")->fetch_assoc()['total'];

    // obtener las promociones recientes para cliente (¡CORREGIR ESTA!)
    $promociones_recientes = $conn->query("
        SELECT p.*, l.nombre as local_nombre 
        FROM promociones p 
        LEFT JOIN locales l ON p.local_id = l.id 
        WHERE p.estado = 'aprobada' 
        AND p.fecha_fin >= CURDATE()
        AND p.fecha_inicio <= CURDATE()
        AND (
            p.categoria_minima = 'Inicial'
            OR (p.categoria_minima = 'Medium' AND '$categoria' IN ('Medium', 'Premium'))
            OR (p.categoria_minima = 'Premium' AND '$categoria' = 'Premium')
        )
        ORDER BY p.id DESC LIMIT 5
    ");

    // obtener las novedades recientes para cliente (¡CORREGIR ESTA!)
    $novedades_recientes = $conn->query("
        SELECT * FROM novedades 
        WHERE fecha_fin >= CURDATE() 
        AND fecha_inicio <= CURDATE()
        AND estado = 'activa'
        AND (
            categoria_objetivo = 'Inicial'
            OR (categoria_objetivo = 'Medium' AND '$categoria' IN ('Medium', 'Premium'))
            OR (categoria_objetivo = 'Premium' AND '$categoria' = 'Premium')
        )
        ORDER BY id DESC LIMIT 5
    ");

    //contar promociones usadas por el cliente (ESTA ESTÁ BIEN)
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
            <a class="navbar-brand" href="index.php">
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

            <?php include('dashboard/statscliente.php'); ?>

        <?php elseif ($rol == 'admin'): ?>

            <?php include('dashboard/statsadmin.php'); ?>

        <?php elseif ($rol == 'dueno'): ?>

            <?php include('dashboard/statsdueno.php'); ?>

        <?php endif; ?>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h2>Panel de Control</h2>
                        <?php if ($rol == 'admin'): ?>

                            <?php include('dashboard/admin.php'); ?>

                        <?php elseif ($rol == 'cliente'): ?>

                            <?php include('dashboard/cliente.php'); ?>

                        <?php elseif ($rol == 'dueno'): ?>

                            <?php include('dashboard/dueno.php'); ?>

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