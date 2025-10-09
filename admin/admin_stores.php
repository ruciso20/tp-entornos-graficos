<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] != 'admin') {
    header("Location: login.php");
    exit;
}

include("../config/db.php");

// Crear local
if (isset($_POST['crear_local'])) {
    $nombre = $_POST['nombre'];
    $ubicacion = $_POST['ubicacion'];
    $rubro = $_POST['rubro'];
    
    $sql = "INSERT INTO locales (nombreLocal, ubicacionLocal, rubroLocal, estado) 
            VALUES ('$nombre', '$ubicacion', '$rubro', 'activo')";
    
    if ($conn->query($sql)) {
        $success = "Local creado exitosamente";
    } else {
        $error = "Error creando local: " . $conn->error;
    }
}

// Cambiar estado del local
if (isset($_GET['cambiar_estado'])) {
    $id = $_GET['cambiar_estado'];
    $nuevo_estado = $_GET['estado'];
    $conn->query("UPDATE locales SET estado='$nuevo_estado' WHERE codLocal=$id");
    $success = "Estado del local actualizado";
}

// Obtener locales
$locales = $conn->query("SELECT * FROM locales ORDER BY nombreLocal");
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
            <a class="navbar-brand" href="dashboard.php">🛍️ Admin - Locales</a>
            <div>
                <a href="dashboard.php" class="btn btn-outline-light">← Dashboard</a>
            </div>
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

        <!-- Formulario para crear local -->
        <div class="card mb-4">
            <div class="card-header">
                <h5>Crear Nuevo Local</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="row">
                        <div class="col-md-4">
                            <input type="text" name="nombre" class="form-control" placeholder="Nombre del local" required>
                        </div>
                        <div class="col-md-3">
                            <input type="text" name="ubicacion" class="form-control" placeholder="Ubicación (Ej: Planta Alta)" required>
                        </div>
                        <div class="col-md-3">
                            <select name="rubro" class="form-control" required>
                                <option value="">Seleccionar rubro</option>
                                <option value="indumentaria">Indumentaria</option>
                                <option value="perfumeria">Perfumería</option>
                                <option value="optica">Óptica</option>
                                <option value="electronica">Electrónica</option>
                                <option value="comida">Comida</option>
                                <option value="deportes">Deportes</option>
                                <option value="hogar">Hogar</option>
                                <option value="otros">Otros</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" name="crear_local" class="btn btn-primary w-100">Crear</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Lista de locales -->
        <div class="card">
            <div class="card-header">
                <h5>Locales Existentes (<?php echo $locales->num_rows; ?>)</h5>
            </div>
            <div class="card-body">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Ubicación</th>
                            <th>Rubro</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($local = $locales->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $local['codLocal']; ?></td>
                            <td><strong><?php echo $local['nombreLocal']; ?></strong></td>
                            <td><?php echo $local['ubicacionLocal']; ?></td>
                            <td>
                                <span class="badge bg-info"><?php echo ucfirst($local['rubroLocal']); ?></span>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo $local['estado'] == 'activo' ? 'success' : 'secondary'; ?>">
                                    <?php echo ucfirst($local['estado']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($local['estado'] == 'activo'): ?>
                                    <a href="?cambiar_estado=<?php echo $local['codLocal']; ?>&estado=inactivo" 
                                       class="btn btn-warning btn-sm">Desactivar</a>
                                <?php else: ?>
                                    <a href="?cambiar_estado=<?php echo $local['codLocal']; ?>&estado=activo" 
                                       class="btn btn-success btn-sm">Activar</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>