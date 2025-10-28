<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] != 'dueno') {
  header("Location: ../login.php");
  exit;
}

include("../config/db.php");

$dueno_idd = $_SESSION['user_id'];
$mensaje = "";

// Obtener todos los locales del dueño
$locales_query = $conn->query("SELECT id, nombre FROM locales WHERE dueno_id = $dueno_idd AND estado = 'activo'");
$locales = $locales_query->fetch_all(MYSQLI_ASSOC);

// Obtener nombre dueño
$dueno_query = $conn->query("SELECT id, nombre FROM usuarios WHERE id = $dueno_idd AND rol = 'dueno'");
$dueno = $dueno_query->fetch_all(MYSQLI_ASSOC);

if (count($locales) == 0) {
  die("No tienes locales asignados o activos.");
}



// Crear local
if (isset($_POST['crear_local'])) {
    $nombre = trim($_POST['nombre']);
    $descripcion = trim($_POST['descripcion']);
    $codigo_local = trim($_POST['codigo_local']);
    $dueno_id = $_POST['dueno_id'];

    // Validaciones
    if (!is_numeric($codigo_local)) {
        $error = "El código debe ser un número";
    } elseif (empty($dueno_id)) {
        $error = "Debes seleccionar un dueño";
    } else {
        // INSERT con dueño_id válido
        $sql = "INSERT INTO locales (nombre, descripcion, codigo_local, dueno_id, estado) 
                VALUES ('$nombre', '$descripcion', $codigo_local, $dueno_id, 'inactivo')";

        if ($conn->query($sql) === TRUE) {
            $success = "Local '$nombre' creado, pendiente de aprobación del administrador";
            $_POST['nombre'] = $_POST['descripcion'] = $_POST['codigo_local'] = '';
        } else {
            $error = "Error: " . $conn->error;
        }
    }
}


// Obtener locales con información del dueño
$locales = $conn->query("
    SELECT l.*, u.nombre as nombre_dueno 
    FROM locales l 
    LEFT JOIN usuarios u ON l.dueno_id = u.id
    WHERE u.id = $dueno_idd
");
?>

<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <title>Gestión de Locales - Dueño</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
  <nav class="navbar navbar-dark bg-dark">
    <div class="container">
      <a class="navbar-brand" href="../dashboard.php">🛍 Dueño - Locales</a>
      <a href="../dashboard.php" class="btn btn-outline-light">Volver</a>
    </div>
  </nav>

  <div class="container mt-4">
    <h2>Gestión de Locales</h2>

    <?php if ($mensaje): ?>
      <div class="alert alert-info"><?php echo $mensaje; ?></div>
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
            
                        <div class="col-md-1">
                            <label>&nbsp;</label>
                            <button type="submit" name="crear_local" class="btn btn-primary w-100">Crear</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

    <!-- Lista de promociones existentes -->
    <div class="card">
     
      <div class="card-body">
        <?php if ($locales->num_rows == 0): ?>
          <div class="alert alert-info">
            No hay locales creados aún.
          </div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-striped">
              <thead>
                <tr>
                  <th>NOMBRE</th>
                  <th>Descripción</th>
                  <th>Estado</th>
                  <th>Acciones</th>
                </tr>
              </thead>
              <tbody>
                <?php while ($local = $locales->fetch_assoc()):
           
                ?>
                  <tr>
                    <td><strong><?php echo htmlspecialchars($local['nombre']); ?></strong></td>
                    <td><?php echo htmlspecialchars($local['descripcion']); ?></td>
            
                    <td>
                      <span class="badge bg-<?php
                                            switch ($local['estado']) {
                                              case 'activo':
                                                echo 'success';
                                                break;
                                              case 'inactivo':
                                                echo 'danger';
                                                break;
                                              default:
                                                echo 'warning';
                                            }
                                            ?>">
                        <?php echo ucfirst($local['estado']); ?>
                        </span>
                    </td>
                    <td>
                      <?php if ($local['estado'] == 'inactivo'): ?>
                        <a href="locales.php?eliminar=<?php echo $local['id']; ?>"
                          class="btn btn-sm btn-danger"
                          onclick="return confirm('¿Estás seguro de eliminar este local?')">
                          Eliminar
                        </a>
                      <?php else: ?>
                        <span class="text-muted small">No editable</span>
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


</body>

</html>