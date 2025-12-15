<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] != 'admin') {
    header("Location: ../login.php");
    exit;
}

include("../config/db.php");

// Aprobar dueño
if (isset($_GET['aprobar'])) {
    $id = $_GET['aprobar'];
    $conn->query("UPDATE usuarios SET estado='aprobado' WHERE id=$id");
    $success = "Dueño aprobado correctamente";
}

// Rechazar dueño
if (isset($_GET['rechazar'])) {
    $id = $_GET['rechazar'];
    $conn->query("UPDATE usuarios SET estado='rechazado' WHERE id=$id");
    $success = "Dueño rechazado. Puede volver a solicitar si corrige los datos.";
}

// Reactivar solicitud (permite volver a pendiente)
if (isset($_GET['reactivar'])) {
    $id = $_GET['reactivar'];
    $conn->query("UPDATE usuarios SET estado='pendiente' WHERE id=$id");
    $success = "Solicitud reactivada para nueva revisión";
}

// Eliminar usuario permanentemente
if (isset($_GET['eliminar'])) {
    $id = $_GET['eliminar'];
    $conn->query("DELETE FROM usuarios WHERE id=$id AND estado='rechazado'");
    $success = "Usuario eliminado permanentemente";
}

// Obtener usuarios
$usuarios = $conn->query("SELECT * FROM usuarios ORDER BY 
    CASE estado 
        WHEN 'pendiente' THEN 1
        WHEN 'aprobado' THEN 2
        WHEN 'rechazado' THEN 3
    END, fecha_registro DESC");

$pendientes = $conn->query("SELECT COUNT(*) as total FROM usuarios WHERE estado='pendiente' AND rol='dueno'")->fetch_assoc()['total'];
$rechazados = $conn->query("SELECT COUNT(*) as total FROM usuarios WHERE estado='rechazado' AND rol='dueno'")->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Gestión de Usuarios - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

</head>

<body>
    <header>
        <nav class="navbar navbar-dark bg-dark">
            <div class="container-fluid">
                <div class="navbar-brand">
                    <a class="navbar-brand fw-bold" href="../index.php">🛍️
                        <span class="ms-1">Stella Shopping Rosario</span></a>
                    <span class="navbar-text text-light">Gestión de Usuarios</span>
                </div>
                <div class="d-flex">
                    <a href="../dashboard.php" class="btn btn-outline-light">Volver</a>
                </div>
            </div>
        </nav>
    </header>

    <main class="container mt-4">

        <?php if (isset($success)): ?>
            <div class="alert alert-success" role="alert"><?php echo $success; ?></div>
        <?php endif; ?>

        <!-- Resumen -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body text-center">
                        <h2><?php echo $pendientes; ?></h2>
                        <p>Pendientes</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-danger text-white">
                    <div class="card-body text-center">
                        <h2><?php echo $rechazados; ?></h2>
                        <p>Rechazados</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lista de usuarios -->
        <div class="card">
            <div class="card-header">
                <h2>Lista de Usuarios</h2>
            </div>
            <div class="card-body">
                <table class="table table-striped" role="table">
                    <caption class="visually-hidden">
                        Listado de usuarios con rol y estado
                    </caption>
                    <thead role="rowgroup">
                        <tr role="row">
                            <th role="columnheader">ID</th>
                            <th role="columnheader">Nombre</th>
                            <th role="columnheader">Email</th>
                            <th role="columnheader">Rol</th>
                            <th role="columnheader">Estado</th>
                            <th role="columnheader">Fecha Registro</th>
                            <th role="columnheader">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($usuario = $usuarios->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $usuario['id']; ?></td>
                                <td><?php echo $usuario['nombre']; ?></td>
                                <td><?php echo $usuario['email']; ?></td>
                                <td>
                                    <span class="badge bg-secondary"><?php echo ucfirst($usuario['rol']); ?></span>
                                </td>
                                <td>
                                    <span class="badge bg-<?php
                                                            echo $usuario['estado'] == 'aprobado' ? 'success' : ($usuario['estado'] == 'pendiente' ? 'warning' : 'danger');
                                                            ?>">
                                        <?php echo ucfirst($usuario['estado']); ?>
                                    </span>
                                </td>
                                <td>
                                    <time datetime="<?php echo $usuario['fecha_registro']; ?>">
                                        <?php echo $usuario['fecha_registro']; ?>
                                    </time>
                                </td>
                                <td>
                                    <?php if ($usuario['rol'] == 'dueno'): ?>
                                        <?php if ($usuario['estado'] == 'pendiente'): ?>
                                            <!-- Usuario pendiente -->
                                            <div class="btn-group btn-group-sm">
                                                <form method="GET" class="d-inline">
                                                    <input type="hidden" name="aprobar" value="<?php echo $usuario['id']; ?>">
                                                    <button
                                                        type="submit"
                                                        class="btn btn-success btn-sm"
                                                        aria-label="Aprobar dueño">
                                                        <span aria-hidden="true">✅</span>
                                                    </button>
                                                </form>

                                                <form method="GET" class="d-inline">
                                                    <input type="hidden" name="rechazar" value="<?php echo $usuario['id']; ?>">
                                                    <button
                                                        type="submit"
                                                        class="btn btn-danger btn-sm"
                                                        aria-label="Rechazar solicitud del dueño">
                                                        <span aria-hidden="true">❌</span>
                                                    </button>
                                                </form>

                                            </div>
                                        <?php elseif ($usuario['estado'] == 'rechazado'): ?>
                                            <!-- Usuario rechazado - opciones -->
                                            <div class="btn-group btn-group-sm">
                                                <form method="GET" class="d-inline">
                                                    <input type="hidden" name="reactivar" value="<?php echo $usuario['id']; ?>">
                                                    <button
                                                        type="submit"
                                                        class="btn btn-warning btn-sm"
                                                        aria-label="Reactivar solicitud del dueño">
                                                        <span aria-hidden="true">🔄</span>
                                                    </button>
                                                </form>

                                                <form method="GET" class="d-inline">
                                                    <input type="hidden" name="eliminar" value="<?php echo $usuario['id']; ?>">
                                                    <button
                                                        type="submit"
                                                        class="btn btn-outline-danger btn-sm"
                                                        aria-label="Eliminar usuario permanentemente"
                                                        onclick="return confirm('¿Eliminar permanentemente a <?php echo htmlspecialchars($usuario['nombre']); ?>? Esta acción no se puede deshacer.')">
                                                        <span aria-hidden="true">🗑️</span>
                                                    </button>
                                                </form>

                                            </div>
                                        <?php else: ?>
                                            <!-- Usuario aprobado -->
                                            <span class="text-success">✅ Aprobado</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Leyenda de acciones (por si no se llegan a enteder los botones de reactivar y de eliminar)-->
        <div class="card mt-4">
            <div class="card-header bg-light">
                <h2 class="mb-0">📋 Leyenda de Acciones</h2>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <strong>Para usuarios pendientes:</strong>
                        <ul class="mb-0">
                            <li>✅ <strong>Aprobar:</strong> Convierte en dueño activo</li>
                            <li>❌ <strong>Rechazar:</strong> Marca como rechazado (puede reactivarse)</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <strong>Para usuarios rechazados:</strong>
                        <ul class="mb-0">
                            <li>🔄 <strong>Reactivar:</strong> Vuelve a estado pendiente para nueva revisión</li>
                            <li>🗑️ <strong>Eliminar:</strong> Borra permanentemente del sistema</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <!-- footer -->
    <footer>
        <?php include('../footer.php'); ?>
    </footer>
</body>

</html>