<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] != 'admin') {
    header("Location: ../login.php");
    exit;
}

include("../config/db.php");
$mensaje = "";

// Procesar aprobación/rechazo de local - CORREGIDO
if (isset($_POST['aprobar']) || isset($_POST['rechazar'])) {
    $local_id = $_POST['local_id'];

    if (isset($_POST['aprobar'])) {
        $nuevo_estado = 'aprobado';
        $mensaje = "✅ Local aprobado exitosamente";
        $mensaje_tipo = 'success';
    } else {
        $nuevo_estado = 'rechazado';
        $mensaje = "❌ Local rechazado";
        $mensaje_tipo = 'danger';
    }

    $update_query = $conn->prepare("UPDATE locales SET estado = ? WHERE id = ?");
    $update_query->bind_param("si", $nuevo_estado, $local_id);

    if (!$update_query->execute()) {
        $mensaje = "❌ Error al procesar la solicitud";
        $mensaje_tipo = 'danger';
    }
}

// Procesar activar/desactivar local - SIMPLIFICADO
if (isset($_GET['toggle_estado'])) {
    $local_id = $_GET['toggle_estado'];

    // Obtener estado actual
    $current_state = $conn->query("SELECT estado FROM locales WHERE id = $local_id")->fetch_assoc()['estado'];

    // Alternar entre aprobado e inactivo
    $nuevo_estado = ($current_state == 'aprobado') ? 'inactivo' : 'aprobado';

    $update_query = $conn->prepare("UPDATE locales SET estado = ? WHERE id = ?");
    $update_query->bind_param("si", $nuevo_estado, $local_id);

    if ($update_query->execute()) {
        $mensaje = "✅ Estado del local actualizado a " . $nuevo_estado;
        $mensaje_tipo = 'success';
    } else {
        $mensaje = "❌ Error al cambiar estado";
        $mensaje_tipo = 'danger';
    }
}

// Obtener locales con información del dueño
$locales_query = $conn->query("
    SELECT l.*, u.nombre as dueno_nombre, u.email as dueno_email 
    FROM locales l 
    LEFT JOIN usuarios u ON l.dueno_id = u.id 
    ORDER BY 
        CASE 
            WHEN l.estado = 'pendiente' THEN 1
            WHEN l.estado = 'aprobado' THEN 2
            WHEN l.estado = 'inactivo' THEN 3
            WHEN l.estado = 'rechazado' THEN 4
            ELSE 5
        END,
        l.nombre
");
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Gestión de Locales - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <nav class="navbar navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="../dashboard.php">🛍️ Admin - Gestión de Locales</a>
            <a href="../dashboard.php" class="btn btn-outline-light">Volver</a>
        </div>
    </nav>

    <div class="container mt-4">
        <h2>Gestión de Locales</h2>

        <?php if ($mensaje): ?>
            <div class="alert alert-<?php echo $mensaje_tipo ?? 'info'; ?>"><?php echo $mensaje; ?></div>
        <?php endif; ?>

        <!-- Resumen de estados -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-white bg-warning">
                    <div class="card-body text-center">
                        <h5>Pendientes</h5>
                        <h2><?php echo $conn->query("SELECT COUNT(*) as total FROM locales WHERE estado = 'pendiente'")->fetch_assoc()['total']; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-success">
                    <div class="card-body text-center">
                        <h5>Aprobados</h5>
                        <h2><?php echo $conn->query("SELECT COUNT(*) as total FROM locales WHERE estado = 'aprobado'")->fetch_assoc()['total']; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-danger">
                    <div class="card-body text-center">
                        <h5>Rechazados</h5>
                        <h2><?php echo $conn->query("SELECT COUNT(*) as total FROM locales WHERE estado = 'rechazado'")->fetch_assoc()['total']; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-info">
                    <div class="card-body text-center">
                        <h5>Total</h5>
                        <h2><?php echo $conn->query("SELECT COUNT(*) as total FROM locales")->fetch_assoc()['total']; ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lista de locales -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Todos los Locales</h5>
            </div>
            <div class="card-body">
                <?php if ($locales_query->num_rows == 0): ?>
                    <div class="alert alert-info">
                        No hay locales registrados en el sistema.
                    </div>
                <?php else: ?>
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Dueño</th>
                                <th>Descripción</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($local = $locales_query->fetch_assoc()): ?>
                                <tr>
                                    <td><strong>#<?php echo $local['id']; ?></strong></td>
                                    <td><?php echo $local['nombre']; ?></td>
                                    <td>
                                        <?php if ($local['dueno_nombre']): ?>
                                            <span class="badge bg-success"><?php echo $local['dueno_nombre']; ?></span>
                                            <br><small><?php echo $local['dueno_email']; ?></small>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Dueño eliminado</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo ucfirst($local['descripcion']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php
                                                                switch ($local['estado']) {
                                                                    case 'aprobado':
                                                                        echo 'success';
                                                                        break;
                                                                    case 'pendiente':
                                                                        echo 'warning';
                                                                        break;
                                                                    case 'rechazado':
                                                                        echo 'danger';
                                                                        break;
                                                                    case 'inactivo':
                                                                        echo 'secondary';
                                                                        break;
                                                                    default:
                                                                        echo 'secondary';
                                                                }
                                                                ?>">
                                            <?php echo ucfirst($local['estado']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <!-- Acciones según estado -->
                                        <?php if ($local['estado'] == 'pendiente'): ?>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="local_id" value="<?php echo $local['id']; ?>">
                                                <button type="submit" name="aprobar" class="btn btn-sm btn-success">✅ Aprobar</button>
                                                <button type="submit" name="rechazar" class="btn btn-sm btn-danger">❌ Rechazar</button>
                                            </form>
                                        <?php elseif ($local['estado'] == 'aprobado'): ?>
                                            <a href="?toggle_estado=<?php echo $local['id']; ?>" class="btn btn-sm btn-warning">⏸️ Desactivar</a>
                                        <?php elseif ($local['estado'] == 'inactivo'): ?>
                                            <a href="?toggle_estado=<?php echo $local['id']; ?>" class="btn btn-sm btn-success">▶️ Activar</a>
                                        <?php elseif ($local['estado'] == 'rechazado'): ?>
                                            <small class="text-muted">Sin acciones disponibles</small>
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
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>