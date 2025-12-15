<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] != 'cliente') {
    header("Location: ../login.php");
    exit;
}

include("../config/db.php");
$user_id = $_SESSION['user_id'];

// Obtener categoría actual directamente de la base de datos
$cat_query = $conn->prepare("SELECT categoria_cliente FROM usuarios WHERE id = ?");
$cat_query->bind_param("i", $user_id);
$cat_query->execute();
$cat_result = $cat_query->get_result();
$usuario_data = $cat_result->fetch_assoc();

if ($usuario_data && isset($usuario_data['categoria_cliente'])) {
    $_SESSION['categoria_cliente'] = $usuario_data['categoria_cliente'];
    $categoriaActual = $usuario_data['categoria_cliente'];
} else {
    $categoriaActual = 'inicial';
    $_SESSION['categoria_cliente'] = 'inicial';
}

// Obtener estadísticas del último semestre (6 meses)
$stats_query = $conn->prepare("
    SELECT 
        COUNT(*) as total_usadas_6meses,
        (SELECT promocionesRequeridas FROM config_ascensos WHERE LOWER(categoriaDestino) = 'medium') as requeridas_medium,
        (SELECT promocionesRequeridas FROM config_ascensos WHERE LOWER(categoriaDestino) = 'premium') as requeridas_premium
    FROM uso_promociones 
    WHERE cliente_id = ? AND estado = 'usada'
    AND fecha_uso >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
");
$stats_query->bind_param("i", $user_id);
$stats_query->execute();
$stats_result = $stats_query->get_result();
$stats = $stats_result->fetch_assoc();

// verificamos si tiene datos de la query reciete
if ($stats) {
    $total_usadas = $stats['total_usadas_6meses'];
    $requeridas_medium = $stats['requeridas_medium'];
    $requeridas_premium = $stats['requeridas_premium'];
} else {
    $total_usadas = 0;
    // Obtener valores por defecto de la base de datos
    $default_query = $conn->query("SELECT categoriaDestino, promocionesRequeridas FROM config_ascensos");
    $defaults = $default_query->fetch_all(MYSQLI_ASSOC);

    $requeridas_medium = 5;
    $requeridas_premium = 15;
    foreach ($defaults as $config) {
        if (strtolower($config['categoriaDestino']) == 'medium') {
            $requeridas_medium = $config['promocionesRequeridas'];
        }
        if (strtolower($config['categoriaDestino']) == 'premium') {
            $requeridas_premium = $config['promocionesRequeridas'];
        }
    }
}

// Calculamos el progreso
$progreso_medium = ($requeridas_medium > 0) ? min(100, ($total_usadas / $requeridas_medium) * 100) : 0;
$progreso_premium = ($requeridas_premium > 0) ? min(100, ($total_usadas / $requeridas_premium) * 100) : 0;

// Función para verificar ascenso automático 
function verificarAscensoAutomatico($user_id, $total_usadas, $conn)
{
    // Obtener categoría actual
    $cat_query = $conn->prepare("SELECT categoria_cliente FROM usuarios WHERE id = ?");
    $cat_query->bind_param("i", $user_id);
    $cat_query->execute();
    $cat_result = $cat_query->get_result();
    $usuario_data = $cat_result->fetch_assoc();

    if ($usuario_data && isset($usuario_data['categoria_cliente'])) {
        $categoria_actual = $usuario_data['categoria_cliente'];

        // Obtener requisitos de la base de datos
        $req_query = $conn->query("SELECT categoriaDestino, promocionesRequeridas FROM config_ascensos");
        $requisitos = [];
        while ($row = $req_query->fetch_assoc()) {
            // Convertir a minúscula para hacerlo case-insensitive por las dudas
            $categoria_key = strtolower($row['categoriaDestino']);
            $requisitos[$categoria_key] = $row['promocionesRequeridas'];
        }

        $nueva_categoria = $categoria_actual;

        // Verificar ascenso a Premium 
        if (strtolower($categoria_actual) == 'medium' && isset($requisitos['premium']) && $total_usadas >= $requisitos['premium']) {
            $nueva_categoria = 'premium';
        }
        // Verificar ascenso a Medium 
        elseif (strtolower($categoria_actual) == 'inicial' && isset($requisitos['medium']) && $total_usadas >= $requisitos['medium']) {
            $nueva_categoria = 'medium';
        }

        // Si hay cambio de categoría, actualizar
        if ($nueva_categoria != $categoria_actual) {
            $update_query = $conn->prepare("UPDATE usuarios SET categoria_cliente = ? WHERE id = ?");
            $update_query->bind_param("si", $nueva_categoria, $user_id);

            if ($update_query->execute()) {
                // actualizamos la sesion inmediatamente
                $_SESSION['categoria_cliente'] = strtolower($nueva_categoria);
                $_SESSION['success'] = "🎉 ¡Felicidades! Has ascendido automáticamente a categoría " . ucfirst($nueva_categoria);

                // Registramos en el historial de ascensos esto
                $conn->query("INSERT INTO historial_ascensos (usuario_id, categoriaAnterior, categoriaNueva, fechaAscenso) 
                             VALUES ($user_id, '$categoria_actual', '$nueva_categoria', NOW())");

                return true;
            }
        }
    }
    return false;
}

// Llamar a la función de verificación
$hubo_cambio = verificarAscensoAutomatico($user_id, $total_usadas, $conn);

// si existió un cambio, actualizamos la var. $categoriaActual
if ($hubo_cambio) {
    $categoriaActual = $_SESSION['categoria_cliente'];
}

// Obtener historial completo para mostrar
$historial_query = $conn->prepare("
    SELECT up.*, p.titulo, p.descripcion, l.nombre as local_nombre, 
           DATE(up.fecha_uso) as fecha, TIME(up.fecha_uso) as hora
    FROM uso_promociones up
    JOIN promociones p ON up.promocion_id = p.id
    JOIN locales l ON up.local_id = l.id
    WHERE up.cliente_id = ?
    ORDER BY up.fecha_uso DESC
");
$historial_query->bind_param("i", $user_id);
$historial_query->execute();
$historial = $historial_query->get_result();
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Mi Progreso - Cliente</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .progress {
            height: 25px;
        }

        .categoria-card {
            border-left: 4px solid;
            transition: transform 0.2s;
        }

        .categoria-card:hover {
            transform: translateY(-5px);
        }

        .categoria-inicial {
            border-left-color: #17a2b8;
        }

        .categoria-medium {
            border-left-color: #ffc107;
        }

        .categoria-premium {
            border-left-color: #dc3545;
        }

        .logro-alcanzado {
            background: linear-gradient(45deg, #d4edda, #c3e6cb);
        }
    </style>
</head>

<body>
    <header>
        <nav class="navbar navbar-dark bg-dark">
            <div class="container-fluid">
                <div class="navbar-brand">
                    <a class="navbar-brand fw-bold" href="../index.php">🛍️
                        <span class="ms-1">Stella Shopping Rosario</span></a>
                    <span class="navbar-text text-light">Mi Progreso</span>
                </div>
                <div class="d-flex">
                    <a href="../dashboard.php" class="btn btn-outline-light">Volver</a>
                </div>
            </div>
        </nav>
    </header>

    <main class="container mt-4">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success" role="alert"><?php echo $_SESSION['success'];
                                                            unset($_SESSION['success']); ?></div>
        <?php endif; ?>

        <!-- Card del Estado Actual -->
        <div class="card mb-4">
            <div class="card-header">
                <h2 class="mb-0">Progreso de Categoría</h2>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h3>Categoría Actual:
                            <span class="badge bg-<?php
                                                    echo ($categoriaActual == 'Premium') ? 'danger' : (($categoriaActual == 'Medium') ? 'warning' : 'info');
                                                    ?>">
                                <?php echo ucfirst($categoriaActual); ?>
                            </span>
                        </h3>
                        <p class="text-muted">Promociones utilizadas (últimos 6 meses): <strong><?php echo $total_usadas; ?></strong></p>
                    </div>
                    <div class="col-md-6">
                        <div class="alert alert-info" role="alert">
                            <small>
                                <!--explicacion de funcionamiento del sistema-->
                                <strong>💡 Sistema Automático:</strong><br>
                                Tu categoría se actualiza automáticamente según el uso de promociones <br> en el plazo de los últimos 6 meses
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- barras de Progreso -->
        <div class="row mb-5">
            <div class="col-md-6 mb-4">
                <div class="card categoria-card categoria-medium <?php echo $total_usadas >= $requeridas_medium ? 'logro-alcanzado' : ''; ?>">
                    <div class="card-body">
                        <h3 class="card-title">
                            Categoría Medium
                            <?php if ($categoriaActual == 'Medium' || $categoriaActual == 'Premium'): ?>
                                <span class="badge bg-success">
                                    <span aria-hidden="true">✅</span> Alcanzada
                                </span>
                            <?php endif; ?>
                        </h3>

                        <div class="progress mb-2">
                            <div
                                class="progress-bar bg-warning text-dark"
                                role="progressbar"
                                style="width: <?php echo $progreso_medium; ?>%"
                                aria-valuenow="<?php echo $total_usadas; ?>"
                                aria-valuemin="0"
                                aria-valuemax="<?php echo $requeridas_medium; ?>">
                            </div>
                        </div>

                        <p class="card-text">
                            <small>
                                <strong>Requerido:</strong> <?php echo $requeridas_medium; ?> promociones (6 meses)<br>
                                <strong>Beneficios:</strong> Acceso a promociones Medium + Inicial
                            </small>
                        </p>
                    </div>
                </div>
            </div>

            <div class="col-md-6 mb-4">
                <div class="card categoria-card categoria-premium <?php echo $total_usadas >= $requeridas_premium ? 'logro-alcanzado' : ''; ?>">
                    <div class="card-body">
                        <h3 class="card-title">
                            Categoría Premium
                            <?php if ($categoriaActual == 'Premium'): ?>
                                <span class="badge bg-success">✅ Alcanzada</span>
                            <?php endif; ?>
                        </h3>

                        <div class="progress mb-2">
                            <div
                                class="progress-bar bg-warning text-dark"
                                role="progressbar"
                                style="width: <?php echo $progreso_premium; ?>%"
                                aria-valuenow="<?php echo $total_usadas; ?>"
                                aria-valuemin="0"
                                aria-valuemax="<?php echo $requeridas_premium; ?>">
                            </div>
                        </div>

                        <p class="card-text">
                            <small>
                                <strong>Requerido:</strong> <?php echo $requeridas_premium; ?> promociones (6 meses)<br>
                                <strong>Beneficios:</strong> Acceso a todas las promociones + beneficios exclusivos
                            </small>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- historial de las Promociones Usadas -->
        <div class="card">
            <div class="card-header">
                <h2 class="mb-0">Historial de Promociones Utilizadas</h2>
            </div>
            <div class="card-body">
                <?php if ($historial->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped" role="table">
                            <caption class="visually-hidden">
                                Historial de promociones utilizadas
                            </caption>
                            <thead role="rowgroup">
                                <tr role="row">
                                    <th scope="col">Fecha y Hora</th>
                                    <th scope="col">Promoción</th>
                                    <th scope="col">Local</th>
                                    <th scope="col">Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($uso = $historial->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <time datetime="<?php echo $uso['fecha']; ?>">
                                                <strong><?php echo date('d/m/Y', strtotime($uso['fecha'])); ?></strong>
                                            </time>
                                            <br>
                                            <small class="text-muted"><?php echo $uso['hora']; ?></small>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($uso['titulo']); ?></strong><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($uso['descripcion']); ?></small>
                                        </td>
                                        <td><?php echo htmlspecialchars($uso['local_nombre']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php
                                                                    echo $uso['estado'] == 'usada' ? 'success' : ($uso['estado'] == 'pendiente' ? 'warning' : 'danger');
                                                                    ?>">
                                                <?php echo ucfirst($uso['estado']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <!-- si no tiene promociones usadas ...-->
                    <div class="text-center py-5">
                        <i class="fas fa-history fa-3x text-muted mb-3"></i>
                        <h3 class="text-muted">Aún no has utilizado promociones</h3>
                        <p class="text-muted">¡Descubre las promociones disponibles y comienza a disfrutar de los beneficios!</p>
                        <a href="promociones.php" class="btn btn-primary">Ver Promociones Disponibles</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
    <footer>
        <?php include('../footer.php'); ?>
    </footer>
</body>

</html>