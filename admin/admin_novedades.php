<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] != 'admin') {
    header("Location: login.php");
    exit;
}

include("../config/db.php");

// Crear novedad
if (isset($_POST['crear_novedad'])) {
    $texto = $_POST['texto'];
    $fecha_desde = $_POST['fecha_desde'];
    $fecha_hasta = $_POST['fecha_hasta'];
    $categoria = $_POST['categoria'];

    $sql = "INSERT INTO novedades (titulo, descripcion, fecha_inicio, fecha_fin, categoria_objetivo, estado) 
        VALUES ('$titulo', '$descripcion', '$fecha_inicio', '$fecha_fin', '$categoria', 'activa')";

    if ($conn->query($sql)) {
        $success = "Novedad creada exitosamente";
    } else {
        $error = "Error creando novedad: " . $conn->error;
    }
}

// Eliminar novedad
if (isset($_GET['eliminar'])) {
    $id = $_GET['eliminar'];
    $conn->query("DELETE FROM novedades WHERE codNovedad=$id");
    $success = "Novedad eliminada correctamente";
}

// Obtener novedades
$novedades = $conn->query("SELECT * FROM novedades ORDER BY fecha_inicio DESC")
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Gestión de Novedades - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <nav class="navbar navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand">Admin - Novedades</a>
            <div>
                <a href="../index.php" class="btn btn-outline-light">Inicio</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h2>Gestión de Novedades</h2>

        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- Formulario para crear novedad -->
        <div class="card mb-4">
            <div class="card-header">
                <h5>Crear Nueva Novedad</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="row">
                        <div class="col-md-5">
                            <textarea name="texto" class="form-control" placeholder="Texto de la novedad..." required rows="3"></textarea>
                        </div>
                        <div class="col-md-2">
                            <label>Fecha Desde</label>
                            <input type="date" name="fecha_desde" class="form-control" required value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="col-md-2">
                            <label>Fecha Hasta</label>
                            <input type="date" name="fecha_hasta" class="form-control" required value="<?php echo date('Y-m-d', strtotime('+7 days')); ?>">
                        </div>
                        <div class="col-md-2">
                            <label>Categoría</label>
                            <select name="categoria" class="form-control" required>
                                <option value="Inicial">Inicial</option>
                                <option value="Medium">Medium</option>
                                <option value="Premium">Premium</option>
                            </select>
                        </div>
                        <div class="col-md-1">
                            <label>&nbsp;</label>
                            <button type="submit" name="crear_novedad" class="btn btn-primary w-100">Crear</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Lista de novedades -->
        <div class="card">
            <div class="card-header">
                <h5>Novedades Existentes (<?php echo $novedades->num_rows; ?>)</h5>
            </div>
            <div class="card-body">
                <?php if ($novedades->num_rows == 0): ?>
                    <div class="alert alert-info">
                        No hay novedades creadas aún
                    </div>
                <?php else: ?>
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Texto</th>
                                <th>Vigencia</th>
                                <th>Categoría</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($novedad = $novedades->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $novedad['codNovedad']; ?></td>
                                    <td><?php echo $novedad['textoNovedad']; ?></td>
                                    <td>
                                        <?php echo date('d/m/Y', strtotime($novedad['fechaDesdeNovedad'])); ?> -<br>
                                        <?php echo date('d/m/Y', strtotime($novedad['fechaHastaNovedad'])); ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php
                                                                echo $novedad['categoriaCliente'] == 'Premium' ? 'danger' : ($novedad['categoriaCliente'] == 'Medium' ? 'warning' : 'info');
                                                                ?>">
                                            <?php echo $novedad['categoriaCliente']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="?eliminar=<?php echo $novedad['codNovedad']; ?>"
                                            class="btn btn-danger btn-sm"
                                            onclick="return confirm('¿Eliminar esta novedad?')">Eliminar</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>

</html>