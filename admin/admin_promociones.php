<?php
session_start();
// Verificar sesión - USANDO LOS NOMBRES CORRECTOS DE SESIÓN
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] != 'admin') {
    header("Location: ../login.php"); // Sale de admin/ hacia login.php
    exit;
}

include("../config/db.php"); // Incluye la conexión a la base de datos

// Acciones sobre promociones
if (isset($_GET['accion'])) {
    $id = $_GET['id'];
    $accion = $_GET['accion'];

    if (in_array($accion, ['aprobada', 'denegada'])) {
        $conn->query("UPDATE promociones SET estado='$accion' WHERE id=$id");
        $success = "Promoción " . $accion;
    }
}

// Obtener promociones con información del local
$promociones = $conn->query("
    SELECT p.*, l.nombre as local_nombre 
    FROM promociones p 
    LEFT JOIN locales l ON p.local_id = l.id 
    ORDER BY p.fecha_inicio DESC
");
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Gestión de Promociones</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>

<body>
    <nav class="navbar navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand">Admin - Gestión de Promociones</a>
            <div>
                <a href="../index.php" class="btn btn-outline-light">Inicio</a>
                <a href="../logout.php" class="btn btn-outline-light">
                    <i class="fas fa-sign-out-alt"></i> Salir</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h2><i class="fas fa-tags"></i> Gestión de Promociones</h2>

        <?php if (isset($success)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Menú de navegación entre secciones del admin -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-4 mb-3">
                                <a href="admin_locales.php" class="btn btn-outline-secondary w-100">
                                    <i class="fas fa-store"></i> Locales
                                </a>
                            </div>
                            <div class="col-md-4 mb-3">
                                <a href="admin_usuarios.php" class="btn btn-outline-info w-100">
                                    <i class="fas fa-users"></i> Usuarios
                                </a>
                            </div>
                            <div class="col-md-4 mb-3">
                                <a href="admin_novedades.php" class="btn btn-outline-warning w-100">
                                    <i class="fas fa-newspaper"></i> Novedades
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="fas fa-list"></i> Lista de Promociones
                </h5>
            </div>
            <div class="card-body">
                <?php if ($promociones->num_rows == 0): ?>
                    <div class="alert alert-info text-center">
                        <i class="fas fa-info-circle fa-2x mb-3"></i><br>
                        No hay promociones registradas en el sistema.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>Local</th>
                                    <th>Promoción</th>
                                    <th>Vigencia</th>
                                    <th>Categoría Mínima</th>
                                    <th>Días Válidos</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($promo = $promociones->fetch_assoc()): ?>
                                    <tr>
                                        <td><strong><?php echo $promo['id']; ?></strong></td>
                                        <td>
                                            <?php if ($promo['local_nombre']): ?>
                                                <span class="badge bg-secondary"><?php echo htmlspecialchars($promo['local_nombre']); ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">N/A</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($promo['titulo']); ?></strong><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($promo['descripcion']); ?></small>
                                        </td>
                                        <td>
                                            <small>
                                                <strong>Desde:</strong> <?php echo date('d/m/Y', strtotime($promo['fecha_inicio'])); ?><br>
                                                <strong>Hasta:</strong> <?php echo date('d/m/Y', strtotime($promo['fecha_fin'])); ?>
                                            </small>
                                        </td>
                                        <td>
                                            <span class="badge bg-info"><?php echo htmlspecialchars($promo['categoria_minima']); ?></span>
                                        </td>
                                        <td>
                                            <small><?php echo htmlspecialchars($promo['dias_validos']); ?></small>
                                        </td>
                                        <td>
                                            <?php
                                            $estado = $promo['estado'];
                                            $badge_class = '';
                                            $estado_text = '';

                                            switch ($estado) {
                                                case 'aprobada':
                                                    $badge_class = 'bg-success';
                                                    $estado_text = 'Aprobada';
                                                    break;
                                                case 'pendiente':
                                                    $badge_class = 'bg-warning';
                                                    $estado_text = 'Pendiente';
                                                    break;
                                                case 'denegada':
                                                    $badge_class = 'bg-danger';
                                                    $estado_text = 'Denegada';
                                                    break;
                                                default:
                                                    $badge_class = 'bg-secondary';
                                                    $estado_text = $estado;
                                            }
                                            ?>
                                            <span class="badge <?php echo $badge_class; ?>">
                                                <?php echo $estado_text; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($promo['estado'] == 'pendiente'): ?>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="?accion=aprobada&id=<?php echo $promo['id']; ?>"
                                                        class="btn btn-success"
                                                        title="Aprobar promoción">
                                                        <i class="fas fa-check"></i>
                                                    </a>
                                                    <a href="?accion=denegada&id=<?php echo $promo['id']; ?>"
                                                        class="btn btn-danger"
                                                        title="Denegar promoción">
                                                        <i class="fas fa-times"></i>
                                                    </a>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-muted small">Acción completada</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Estadísticas rápidas -->
        <div class="row mt-4">
            <div class="col-md-4">
                <div class="card text-white bg-warning">
                    <div class="card-body text-center">
                        <h4>
                            <?php
                            $pendientes = $conn->query("SELECT COUNT(*) as total FROM promociones WHERE estado = 'pendiente'")->fetch_assoc()['total'];
                            echo $pendientes;
                            ?>
                        </h4>
                        <p>Promociones Pendientes</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-white bg-success">
                    <div class="card-body text-center">
                        <h4>
                            <?php
                            $aprobadas = $conn->query("SELECT COUNT(*) as total FROM promociones WHERE estado = 'aprobada'")->fetch_assoc()['total'];
                            echo $aprobadas;
                            ?>
                        </h4>
                        <p>Promociones Aprobadas</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-white bg-danger">
                    <div class="card-body text-center">
                        <h4>
                            <?php
                            $denegadas = $conn->query("SELECT COUNT(*) as total FROM promociones WHERE estado = 'denegada'")->fetch_assoc()['total'];
                            echo $denegadas;
                            ?>
                        </h4>
                        <p>Promociones Denegadas</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>