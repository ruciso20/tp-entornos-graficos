<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] != 'admin') {
    header("Location: login.php");
    exit;
}

include("../config/db.php");

// creamos la novedad
if (isset($_POST['crear_novedad'])) {
    $titulo = trim($_POST['titulo']);
    $descripcion = trim($_POST['descripcion']);
    $fecha_desde = $_POST['fecha_desde'];
    $fecha_hasta = $_POST['fecha_hasta'];
    $categoria = $_POST['categoria'];

    // validamos que los campos no esten vacios
    if (empty($titulo) || empty($descripcion) || empty($fecha_desde) || empty($fecha_hasta)) {
        $error = "Todos los campos son obligatorios";
    } else {
        $sql = "INSERT INTO novedades (titulo, descripcion, fecha_inicio, fecha_fin, categoria_objetivo, estado) 
                VALUES (?, ?, ?, ?, ?, 'activa')";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssss", $titulo, $descripcion, $fecha_desde, $fecha_hasta, $categoria);

        if ($stmt->execute()) {
            $success = "Novedad creada exitosamente";
            $_POST['titulo'] = $_POST['descripcion'] = '';
        } else {
            $error = "Error creando novedad: " . $conn->error;
        }
    }
}

// eliminar una novedad
if (isset($_GET['eliminar'])) {
    $id = $_GET['eliminar'];
    if ($conn->query("DELETE FROM novedades WHERE id=$id")) {
        $success = "Novedad eliminada correctamente";
    } else {
        $error = "Error eliminando novedad: " . $conn->error;
    }
}

// obtener las novedades
$novedades = $conn->query("
    SELECT 
        id,
        titulo,
        descripcion,
        fecha_inicio,
        fecha_fin,
        categoria_objetivo,
        estado
    FROM novedades 
    ORDER BY fecha_inicio DESC
");
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Gestión de Novedades - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .table th {
            white-space: nowrap;
        }

        .titulo-col {
            width: 180px;
        }

        .categoria-col {
            width: 120px;
        }

        .estado-col {
            width: 100px;
        }

        .acciones-col {
            width: 100px;
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-dark bg-dark">
        <div class="container-fluid">
            <div class="navbar-brand">
                <a class="navbar-brand fw-bold" href="../index.php">🛍️
                    <span class="ms-1">Stella Shopping Rosario</span></a>
                <span class="navbar-text text-light">Gestión de Novedades</span>
            </div>
            <div class="d-flex">
                <a href="../dashboard.php" class="btn btn-outline-light">Volver</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">

        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- formulario para crear la Novedad -->

        <div class="card mb-4">
            <div class="card-header">
                <h5>Crear Nueva Novedad</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="row">
                        <div class="col-md-3">
                            <label class="form-label">Título *</label>
                            <input type="text" name="titulo" class="form-control"
                                placeholder="Título de la Novedad" required
                                value="<?php echo isset($_POST['titulo']) ? htmlspecialchars($_POST['titulo']) : ''; ?>"
                                maxlength="50">
                            <div class="form-text">Máx. 50 caracteres</div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Descripción *</label>
                            <textarea name="descripcion" class="form-control"
                                placeholder="Detalles de la novedad..."
                                required rows="3"><?php echo isset($_POST['descripcion']) ? htmlspecialchars($_POST['descripcion']) : ''; ?></textarea>
                            <div class="form-text">Texto completo de la Novedad</div>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Fecha Desde *</label>
                            <input type="date" name="fecha_desde" class="form-control" required
                                value="<?php echo isset($_POST['fecha_desde']) ? $_POST['fecha_desde'] : date('Y-m-d'); ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Fecha Hasta *</label>
                            <input type="date" name="fecha_hasta" class="form-control" required
                                value="<?php echo isset($_POST['fecha_hasta']) ? $_POST['fecha_hasta'] : date('Y-m-d', strtotime('+7 days')); ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Categoría *</label>
                            <select name="categoria" class="form-select" required>
                                <option value="Inicial" <?php echo (isset($_POST['categoria']) && $_POST['categoria'] == 'Inicial') ? 'selected' : ''; ?>>Inicial</option>
                                <option value="Medium" <?php echo (isset($_POST['categoria']) && $_POST['categoria'] == 'Medium') ? 'selected' : ''; ?>>Medium</option>
                                <option value="Premium" <?php echo (isset($_POST['categoria']) && $_POST['categoria'] == 'Premium') ? 'selected' : ''; ?>>Premium</option>
                            </select>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-12 text-end">
                            <button type="submit" name="crear_novedad" class="btn btn-primary">Crear Novedad</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- lista de las novedades  -->

        <div class="card">
            <div class="card-header">
                <h5>Novedades Existentes</h5>
            </div>
            <div class="card-body">
                <?php if ($novedades->num_rows == 0): ?>
                    <div class="alert alert-info text-center py-4">
                        <h5>No hay novedades creadas</h5>
                        <p class="mb-0">Crea tu primera novedad usando el formulario de arriba.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>ID</th>
                                    <th class="titulo-col">Título</th>
                                    <th>Descripción</th>
                                    <th>Vigencia</th>
                                    <th class="categoria-col">Categoría</th>
                                    <th class="estado-col">Estado</th>
                                    <th class="acciones-col">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($novedad = $novedades->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $novedad['id']; ?></td>
                                        <td class="titulo-col">
                                            <strong><?php echo htmlspecialchars($novedad['titulo']); ?></strong>
                                        </td>
                                        <td>
                                            <small><?php echo htmlspecialchars($novedad['descripcion']); ?></small>
                                        </td>
                                        <td>
                                            <small>
                                                <strong>Desde:</strong> <?php echo date('d/m/Y', strtotime($novedad['fecha_inicio'])); ?><br>
                                                <strong>Hasta:</strong> <?php echo date('d/m/Y', strtotime($novedad['fecha_fin'])); ?>
                                            </small>
                                        </td>
                                        <td class="categoria-col">
                                            <span class="badge bg-<?php
                                                                    echo $novedad['categoria_objetivo'] == 'Premium' ? 'danger' : ($novedad['categoria_objetivo'] == 'Medium' ? 'warning' : 'info');
                                                                    ?> w-100">
                                                <?php echo $novedad['categoria_objetivo']; ?>
                                            </span>
                                        </td>
                                        <td class="estado-col">
                                            <span class="badge bg-<?php echo $novedad['estado'] == 'activa' ? 'success' : 'secondary'; ?> w-100">
                                                <?php echo ucfirst($novedad['estado']); ?>
                                            </span>
                                        </td>
                                        <td class="acciones-col">
                                            <a href="?eliminar=<?php echo $novedad['id']; ?>"
                                                class="btn btn-danger btn-sm w-100"
                                                onclick="return confirm('¿Estás seguro de eliminar esta novedad?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
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
    <!-- footer -->
    <?php include('../footer.php'); ?>
</body>

</html>