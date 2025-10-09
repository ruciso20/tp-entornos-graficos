<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] != 'admin') {
    header("Location: ../login.php");
    exit;
}

include("../config/db.php");

// Obtener dueños aprobados
$duenos_aprobados = $conn->query("SELECT * FROM usuarios WHERE rol='dueno' AND estado='aprobado'");

// Crear local
if (isset($_POST['crear_local'])) {
    $nombre = trim($_POST['nombre']);
    $descripcion = trim($_POST['descripcion']);
    $codigo_local = trim($_POST['codigo_local']);
    $dueno_id = $_POST['dueno_id'];
    
    // Validaciones
    if (!is_numeric($codigo_local)) {
        $error = "❌ El código debe ser un número";
    } elseif (empty($dueno_id)) {
        $error = "❌ Debes seleccionar un dueño";
    } else {
        // INSERT con dueño_id válido
        $sql = "INSERT INTO locales (nombre, descripcion, codigo_local, dueno_id, estado) 
                VALUES ('$nombre', '$descripcion', $codigo_local, $dueno_id, 'activo')";
        
        if ($conn->query($sql) === TRUE) {
            $success = "✅ Local '$nombre' creado y asignado al dueño exitosamente";
            $_POST['nombre'] = $_POST['descripcion'] = $_POST['codigo_local'] = '';
        } else {
            $error = "❌ Error: " . $conn->error;
        }
    }
}

// Cambiar estado del local
if (isset($_GET['cambiar_estado'])) {
    $id = $_GET['cambiar_estado'];
    $estado = $_GET['estado'];
    $conn->query("UPDATE locales SET estado='$estado' WHERE id=$id");
    $success = "Estado del local actualizado";
}

// Obtener locales con información del dueño
$locales = $conn->query("
    SELECT l.*, u.nombre as nombre_dueno 
    FROM locales l 
    LEFT JOIN usuarios u ON l.dueno_id = u.id 
    ORDER BY l.nombre
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
            <a class="navbar-brand" href="../dashboard.php">🛍️ Admin - Locales</a>
            <a href="../dashboard.php" class="btn btn-outline-light">← Volver</a>
        </div>
    </nav>

    <div class="container mt-4">
        <h2>Gestión de Locales</h2>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- Formulario crear local -->
        <div class="card mb-4">
            <div class="card-header">
                <h5>Crear Nuevo Local</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="row">
                        <div class="col-md-3">
                            <label>Nombre del Local *</label>
                            <input type="text" name="nombre" class="form-control" placeholder="Ej: Ropa Fashion" 
                                   value="<?php echo isset($_POST['nombre']) ? htmlspecialchars($_POST['nombre']) : ''; ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label>Descripción</label>
                            <input type="text" name="descripcion" class="form-control" placeholder="Descripción del local"
                                   value="<?php echo isset($_POST['descripcion']) ? htmlspecialchars($_POST['descripcion']) : ''; ?>">
                        </div>
                        <div class="col-md-2">
                            <label>Código *</label>
                            <input type="number" name="codigo_local" class="form-control" placeholder="Ej: 1001" 
                                   value="<?php echo isset($_POST['codigo_local']) ? htmlspecialchars($_POST['codigo_local']) : ''; ?>" 
                                   min="1" max="9999" required>
                        </div>
                        <div class="col-md-3">
                            <label>Dueño *</label>
                            <select name="dueno_id" class="form-control" required>
                                <option value="">Seleccionar dueño...</option>
                                <?php while($dueno = $duenos_aprobados->fetch_assoc()): ?>
                                    <option value="<?php echo $dueno['id']; ?>">
                                        <?php echo $dueno['nombre'] . ' (' . $dueno['email'] . ')'; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-1">
                            <label>&nbsp;</label>
                            <button type="submit" name="crear_local" class="btn btn-primary w-100">Crear</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Lista de locales -->
        <div class="card">
            <div class="card-header">
                <h5>Locales Existentes</h5>
            </div>
            <div class="card-body">
                <?php if ($locales->num_rows == 0): ?>
                    <div class="alert alert-info">
                        No hay locales registrados. Primero aprueba dueños y luego crea locales.
                    </div>
                <?php else: ?>
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Descripción</th>
                                <th>Código</th>
                                <th>Dueño</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($local = $locales->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $local['id']; ?></td>
                                <td><strong><?php echo $local['nombre']; ?></strong></td>
                                <td><?php echo $local['descripcion'] ?: '-'; ?></td>
                                <td><code>#<?php echo $local['codigo_local']; ?></code></td>
                                <td>
                                    <?php if ($local['nombre_dueno']): ?>
                                        <span class="badge bg-success"><?php echo $local['nombre_dueno']; ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Dueño eliminado</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $local['estado'] == 'activo' ? 'success' : 'secondary'; ?>">
                                        <?php echo $local['estado']; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($local['estado'] == 'activo'): ?>
                                        <a href="?cambiar_estado=<?php echo $local['id']; ?>&estado=inactivo" class="btn btn-warning btn-sm">Desactivar</a>
                                    <?php else: ?>
                                        <a href="?cambiar_estado=<?php echo $local['id']; ?>&estado=activo" class="btn btn-success btn-sm">Activar</a>
                                    <?php endif; ?>
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