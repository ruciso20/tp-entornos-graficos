<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] != 'dueno') {
  header("Location: ../login.php");
  exit;
}

include("../config/db.php");
$dueno_id = $_SESSION['user_id'];
$mensaje = "";

// Eliminar Local
$valorEliminar = null;
if (isset($_POST['eliminar'])) {
  $valorEliminar = filter_input(INPUT_POST, 'eliminar', FILTER_VALIDATE_INT);
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
  $imagen_url = null;

  // Procesar imagen si se subió
  if (isset($_FILES['imagen_local']) && $_FILES['imagen_local']['error'] === UPLOAD_ERR_OK) {
    $directorio_imagenes = "../uploads/locales/";

    // Crear directorio si no existe
    if (!is_dir($directorio_imagenes)) {
      mkdir($directorio_imagenes, 0755, true);
    }

    $extension = strtolower(pathinfo($_FILES['imagen_local']['name'], PATHINFO_EXTENSION));
    $tipos_permitidos = ['jpg', 'jpeg', 'png', 'gif'];

    if (in_array($extension, $tipos_permitidos)) {
      // Validar tamaño (2MB máximo)
      if ($_FILES['imagen_local']['size'] <= 2 * 1024 * 1024) {
        $nombre_archivo = uniqid() . '_' . time() . '.' . $extension;
        $ruta_completa = $directorio_imagenes . $nombre_archivo;

        if (move_uploaded_file($_FILES['imagen_local']['tmp_name'], $ruta_completa)) {
          $imagen_url = "uploads/locales/" . $nombre_archivo;
        } else {
          $error = "Error al subir la imagen";
        }
      } else {
        $error = "La imagen es demasiado grande (máximo 2MB)";
      }
    } else {
      $error = "Formato de imagen no permitido. Use JPG, PNG o GIF";
    }
  }

  // Validaciones del formulario
  if (empty($nombre)) {
    $error = "El nombre es obligatorio";
  } else {
    // Insertar en la base de datos CON la imagen
    if ($imagen_url) {
      $sql = "INSERT INTO locales (nombre, descripcion, dueno_id, imagen_url, estado) 
              VALUES ('$nombre', '$descripcion', $dueno_id, '$imagen_url', 'pendiente')";
    } else {
      $sql = "INSERT INTO locales (nombre, descripcion, dueno_id, estado) 
              VALUES ('$nombre', '$descripcion', $dueno_id, 'pendiente')";
    }

    if ($conn->query($sql) === TRUE) {
      $success = "Local '$nombre' creado exitosamente. Espera la aprobación del administrador.";
      $_POST['nombre'] = $_POST['descripcion'] = '';
    } else {
      $error = "Error: " . $conn->error;
    }
  }
}

// Obtener todos los locales del dueño (mostrar todos los estados)
$locales = $conn->query("SELECT id, nombre, descripcion, estado, imagen_url FROM locales WHERE dueno_id = $dueno_id ORDER BY estado, nombre");
?>

<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <title>Gestión de Locales - Dueño</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    .local-imagen {
      width: 60px;
      height: 60px;
      object-fit: cover;
      border-radius: 8px;
    }

    .imagen-placeholder {
      width: 60px;
      height: 60px;
      background: #f8f9fa;
      border-radius: 8px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #6c757d;
    }
  </style>
</head>

<body>
  <nav class="navbar navbar-dark bg-dark">
    <div class="container-fluid">
      <div class="navbar-brand">
        <a class="navbar-brand fw-bold" href="../index.php">🛍️
          <span class="ms-1">Stella Shopping Rosario</span></a>
        <span class="navbar-text text-light">Gestión de Locales</span>
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

    <div class="card mb-4">
      <div class="card-header">
        <h5>Crear Nuevo Local</h5>
      </div>
      <div class="card-body">
        <!-- Formulario crear local -->
        <form method="POST" enctype="multipart/form-data">
          <div class="row">
            <div class="col-md-3 mb-3">
              <label for="nombre" class="form-label">Nombre del Local *</label>
              <input type="text" id="nombre" name="nombre" class="form-control" placeholder="Ej: Ropa Fashion"
                value="<?php echo isset($_POST['nombre']) ? htmlspecialchars($_POST['nombre']) : ''; ?>" required>
            </div>
            <div class="col-md-4 mb-3">
              <label for="descripcion" class="form-label">Descripción</label>
              <input type="text" id="descripcion" name="descripcion" class="form-control" placeholder="Descripción del local"
                value="<?php echo isset($_POST['descripcion']) ? htmlspecialchars($_POST['descripcion']) : ''; ?>">
            </div>
            <div class="col-md-3 mb-3">
              <label for="imagen_local" class="form-label">Imagen del Local</label>
              <input type="file" class="form-control" id="imagen_local" name="imagen_local" accept="image/*">
              <div class="form-text">Formatos: JPG, PNG, GIF. Máx: 2MB</div>
            </div>
            <div class="col-md-2 mb-3">
              <label class="form-label">&nbsp;</label>
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
            <table class="table table-striped" role="table">
              <caption class="visually-hidden">
                Listado de locales del dueño
              </caption>
              <thead role="rowgroup">
                <tr role="row">
                  <th scope="col">ID</th>
                  <th scope="col">Nombre</th>
                  <th scope="col">Descripción</th>
                  <th scope="col">Estado</th>
                  <th scope="col">Acciones</th>
                </tr>
              </thead>
              <tbody>
                <?php while ($local = $locales->fetch_assoc()): ?>
                  <tr>
                    <td><strong>#<?php echo $local['id']; ?></strong></td>
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
                        <form method="POST" action="locales.php" class="d-inline">
                          <input type="hidden" name="eliminar" value="<?php echo $local['id']; ?>">
                          <button type="submit"
                            class="btn btn-sm btn-danger"
                            onclick="return confirm('¿Estás seguro de eliminar este local?')">
                            Eliminar
                          </button>
                        </form>
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