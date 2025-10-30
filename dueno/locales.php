<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] != 'dueno') {
  header("Location: ../login.php");
  exit;
}

include("../config/db.php");
$dueno_id = $_SESSION['user_id'];
$mensaje = "";

//Eliminar Local
$valorEliminar = null;
if (isset($_POST['eliminar'])) {
  $valorEliminar = filter_input(INPUT_POST, 'eliminar', FILTER_VALIDATE_INT);
} elseif (isset($_GET['eliminar'])) {
  $valorEliminar = filter_input(INPUT_GET, 'eliminar', FILTER_VALIDATE_INT);
}

if ($valorEliminar !== null) {
  if ($valorEliminar === false) {
    $mensaje = "ID de local inválido.";
  } else {
    // Solo permitimos eliminar locales en estados específicos
    $stmt = $conn->prepare("DELETE FROM locales WHERE id = ? AND dueno_id = ? AND estado IN ('rechazado', 'inactivo')");
    $stmt->bind_param("ii", $valorEliminar, $dueno_id);

    if (!$stmt->execute()) {
      $mensaje = "Error al eliminar: " . $conn->error;
    } else {
      if ($stmt->affected_rows > 0) {
        header("Location: locales.php?msg=eliminado");
        exit;
      } else {
        $mensaje = "No se pudo eliminar: no existe, no te pertenece o no está en estado eliminable.";
      }
    }
    $stmt->close();
  }
}

// Mensaje por querystring (tras redirect)
if (isset($_GET['msg']) && $_GET['msg'] === 'eliminado') {
  $mensaje = "Local eliminado correctamente.";
}

// Crear local - ESTADO INICIAL: 'pendiente'
if (isset($_POST['crear_local'])) {
  $nombre = trim($_POST['nombre']);
  $descripcion = trim($_POST['descripcion']);
  $dueno_id = (int)$_SESSION['user_id'];

  // Validaciones simplificadas
  if (empty($nombre)) {
    $error = "El nombre es obligatorio";
  } else {
    $sql = "INSERT INTO locales (nombre, descripcion, dueno_id, estado) 
            VALUES ('$nombre', '$descripcion', $dueno_id, 'pendiente')";

    if ($conn->query($sql) === TRUE) {
      $success = "Local '$nombre' creado exitosamente. Espera la aprobación del administrador.";
      $_POST['nombre'] = $_POST['descripcion'] = '';
    } else {
      $error = "Error: " . $conn->error;
    }
  }
}

// Obtener todos los locales del dueño (mostrar todos los estados)
$locales = $conn->query("SELECT id, nombre, descripcion, estado FROM locales WHERE dueno_id = $dueno_id ORDER BY estado, nombre");
?>

<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <title>Gestión de Locales - Dueño</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>
  <nav class="navbar navbar-dark bg-dark">
    <div class="container-fluid">
      <div class="navbar-brand">
        <a class="navbar-brand fw-bold" href="../index.php">🛍️
          <span class="ms-1">Stella Shopping Rosario</span></a>
        <span class="navbar-text text-light">Gestion de Locales</span>
      </div>
      <div class="d-flex">
        <a href="../dashboard.php" class="btn btn-outline-light">Volver</a>
      </div>
    </div>
  </nav>

  <div class="container mt-4">

    <?php if (isset($error)): ?>
      <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <?php if (isset($success)): ?>
      <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>

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
            <div class="col-md-4">
              <label>Nombre del Local *</label>
              <input type="text" name="nombre" class="form-control" placeholder="Ej: Ropa Fashion"
                value="<?php echo isset($_POST['nombre']) ? htmlspecialchars($_POST['nombre']) : ''; ?>" required>
            </div>
            <div class="col-md-5">
              <label>Descripción</label>
              <input type="text" name="descripcion" class="form-control" placeholder="Descripción del local"
                value="<?php echo isset($_POST['descripcion']) ? htmlspecialchars($_POST['descripcion']) : ''; ?>">
            </div>
            <div class="col-md-3">
              <label>&nbsp;</label>
              <button type="submit" name="crear_local" class="btn btn-primary w-100">Crear Local</button>
            </div>
          </div>
        </form>
      </div>
    </div>

    <!-- Lista de locales existentes -->
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0">Mis Locales</h5>
      </div>
      <div class="card-body">
        <?php if ($locales->num_rows == 0): ?>
          <div class="alert alert-info">
            No tienes ningún local creado aún.
          </div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-striped">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Nombre</th>
                  <th>Descripción</th>
                  <th>Estado</th>
                </tr>
              </thead>
              <tbody>
                <?php while ($local = $locales->fetch_assoc()): ?>
                  <tr>
                    <td><strong><?php echo htmlspecialchars($local['nombre']); ?></strong></td>
                    <td><?php echo htmlspecialchars($local['descripcion']); ?></td>
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
                                                echo 'light';
                                            }
                                            ?>">
                        <?php echo ucfirst($local['estado']); ?>
                      </span>
                    </td>
                    <td>
                      <?php if ($local['estado'] == 'rechazado' || $local['estado'] == 'inactivo'): ?>
                        <a href="locales.php?eliminar=<?php echo $local['id']; ?>"
                          class="btn btn-sm btn-danger"
                          onclick="return confirm('¿Estás seguro de eliminar este local?')">
                          Eliminar
                        </a>
                      <?php else: ?>
                        <span class="text-muted small">Esperando aprobación / Activo</span>
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
  <!-- footer -->
  <?php include('../footer.php'); ?>
  <!-- bootstrap -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>