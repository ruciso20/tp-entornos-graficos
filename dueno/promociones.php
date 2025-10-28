<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] != 'dueno') {
  header("Location: ../login.php");
  exit;
}

include("../config/db.php");

$dueno_id = $_SESSION['user_id'];
$mensaje = "";

// Obtener todos los locales del dueño
$locales_query = $conn->query("SELECT id, nombre FROM locales WHERE dueno_id = $dueno_id AND estado = 'activo'");
$locales = $locales_query->fetch_all(MYSQLI_ASSOC);

if (count($locales) == 0) {
  die("No tienes locales asignados o activos.");
}

// Determinar el local actual (por defecto el primero, o el seleccionado)
$local_actual_id = $locales[0]['id'];
if (isset($_POST['local_id']) && is_numeric($_POST['local_id'])) {
  $local_actual_id = $_POST['local_id'];
}

// Obtener datos del local actual
$local_actual = null;
foreach ($locales as $local) {
  if ($local['id'] == $local_actual_id) {
    $local_actual = $local;
    break;
  }
}

// Crear nueva promoción
if (isset($_POST['crear_promocion'])) {
  $titulo = trim($_POST['titulo']);
  $descripcion = trim($_POST['descripcion']);
  $fecha_inicio = $_POST['fecha_inicio'];
  $fecha_fin = $_POST['fecha_fin'];
  $dias_validos = implode(",", $_POST['dias_validos'] ?? []);
  $categoria_minima = $_POST['categoria_minima'];
  $local_id = $_POST['local_id'];

  // Validar que el local pertenece al dueño
  $local_valido = false;
  foreach ($locales as $local) {
    if ($local['id'] == $local_id) {
      $local_valido = true;
      break;
    }
  }

  if (!$local_valido) {
    $mensaje = "Error: Local no válido";
  } else {
    $sql = "INSERT INTO promociones (local_id, titulo, descripcion, fecha_inicio, fecha_fin, dias_validos, categoria_minima, estado) 
                VALUES ($local_id, '$titulo', '$descripcion', '$fecha_inicio', '$fecha_fin', '$dias_validos', '$categoria_minima', 'pendiente')";

    if ($conn->query($sql) === TRUE) {
      $mensaje = "Promoción creada exitosamente. Esperando aprobación del administrador.";
      // Actualizar local actual al seleccionado
      $local_actual_id = $local_id;
      foreach ($locales as $local) {
        if ($local['id'] == $local_actual_id) {
          $local_actual = $local;
          break;
        }
      }
    } else {
      $mensaje = "Error al crear la promoción: " . $conn->error;
    }
  }
}

// Eliminar promoción
if (isset($_GET['eliminar'])) {
  $promocion_id = $_GET['eliminar'];

  // Verificar que la promoción pertenece a un local del dueño
  $verificar = $conn->query("
        SELECT p.id 
        FROM promociones p 
        WHERE p.id = $promocion_id AND p.local_id IN (
            SELECT id FROM locales WHERE dueno_id = $dueno_id
        )
    ");

  if ($verificar->num_rows > 0) {
    if ($conn->query("DELETE FROM promociones WHERE id = $promocion_id")) {
      $mensaje = "Promoción eliminada exitosamente.";
    } else {
      $mensaje = "Error al eliminar la promoción.";
    }
  } else {
    $mensaje = "No tienes permisos para eliminar esta promoción.";
  }
}

// Obtener promociones del local actual
$promociones = $conn->query("
    SELECT * FROM promociones 
    WHERE local_id = $local_actual_id 
    ORDER BY fecha_inicio DESC
");
?>

<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <title>Gestión de Promociones - Dueño</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
  <nav class="navbar navbar-dark bg-dark">
    <div class="container">
      <a class="navbar-brand" href="../dashboard.php">🛍 Dueño - Promociones</a>
      <a href="../dashboard.php" class="btn btn-outline-light">Volver</a>
    </div>
  </nav>

  <div class="container mt-4">
    <h2>Gestión de Promociones</h2>

    <?php if ($mensaje): ?>
      <div class="alert alert-info"><?php echo $mensaje; ?></div>
    <?php endif; ?>

    <!-- Formulario para crear promoción -->
    <div class="card mb-4">
      <div class="card-header">
        <h5>Crear Nueva Promoción</h5>
      </div>
      <div class="card-body">
        <form method="POST">
          <div class="row">
            <div class="col-md-6">
              <div class="mb-3">
                <label class="form-label">Local *</label>
                <select class="form-select" name="local_id" required onchange="this.form.submit()">
                  <?php foreach ($locales as $local): ?>
                    <option value="<?php echo $local['id']; ?>"
                      <?php echo $local['id'] == $local_actual_id ? 'selected' : ''; ?>>
                      <?php echo htmlspecialchars($local['nombre']); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
                <small class="text-muted">Selecciona el local para el que crearás la promoción</small>
              </div>
              <div class="mb-3">
                <label class="form-label">Título *</label>
                <input type="text" class="form-control" name="titulo" required
                  placeholder="Ej: 20% de descuento en toda la tienda">
              </div>
              <div class="mb-3">
                <label class="form-label">Descripción *</label>
                <textarea class="form-control" name="descripcion" rows="3" required
                  placeholder="Describe los detalles de la promoción..."></textarea>
              </div>
            </div>
            <div class="col-md-6">
              <div class="mb-3">
                <label class="form-label">Categoría Mínima *</label>
                <select class="form-select" name="categoria_minima" required>
                  <option value="inicial">Inicial</option>
                  <option value="medium">Medium</option>
                  <option value="premium">Premium</option>
                </select>
              </div>
              <div class="mb-3">
                <label class="form-label">Fecha Inicio *</label>
                <input type="date" class="form-control" name="fecha_inicio" required
                  min="<?php echo date('Y-m-d'); ?>">
              </div>
              <div class="mb-3">
                <label class="form-label">Fecha Fin *</label>
                <input type="date" class="form-control" name="fecha_fin" required
                  min="<?php echo date('Y-m-d'); ?>">
              </div>
              <div class="mb-3">
                <label class="form-label">Días Válidos *</label>
                <div class="border p-3 rounded">
                  <?php
                  $dias_semana = [
                    'lunes' => 'Lunes',
                    'martes' => 'Martes',
                    'miercoles' => 'Miércoles',
                    'jueves' => 'Jueves',
                    'viernes' => 'Viernes',
                    'sabado' => 'Sábado',
                    'domingo' => 'Domingo'
                  ];
                  foreach ($dias_semana as $key => $dia): ?>
                    <div class="form-check">
                      <input class="form-check-input" type="checkbox"
                        name="dias_validos[]" value="<?php echo $key; ?>"
                        id="dia_<?php echo $key; ?>">
                      <label class="form-check-label" for="dia_<?php echo $key; ?>">
                        <?php echo $dia; ?>
                      </label>
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>
            </div>
          </div>
          <button type="submit" name="crear_promocion" class="btn btn-primary">
            Crear Promoción
          </button>
          <small class="text-muted ms-3">* La promoción será enviada para aprobación del administrador</small>
        </form>
      </div>
    </div>

    <!-- Lista de promociones existentes -->
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5>Promociones de <?php echo htmlspecialchars($local_actual['nombre']); ?></h5>
      </div>
      <div class="card-body">
        <?php if ($promociones->num_rows == 0): ?>
          <div class="alert alert-info">
            No hay promociones creadas para este local. Crea tu primera promoción.
          </div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-striped">
              <thead>
                <tr>
                  <th>Título</th>
                  <th>Descripción</th>
                  <th>Fechas</th>
                  <th>Categoría</th>
                  <th>Días</th>
                  <th>Estado</th>
                  <th>Acciones</th>
                </tr>
              </thead>
              <tbody>
                <?php while ($promo = $promociones->fetch_assoc()):
                  $dias_array = explode(",", $promo['dias_validos']);
                  $dias_display = array_map(function ($dia) use ($dias_semana) {
                    return isset($dias_semana[$dia]) ? substr($dias_semana[$dia], 0, 3) : $dia;
                  }, $dias_array);
                ?>
                  <tr>
                    <td><strong><?php echo htmlspecialchars($promo['titulo']); ?></strong></td>
                    <td><?php echo htmlspecialchars($promo['descripcion']); ?></td>
                    <td>
                      <small>
                        <?php echo date('d/m/Y', strtotime($promo['fecha_inicio'])); ?> -<br>
                        <?php echo date('d/m/Y', strtotime($promo['fecha_fin'])); ?>
                      </small>
                    </td>
                    <td>
                      <span class="badge bg-secondary"><?php echo ucfirst($promo['categoria_minima']); ?></span>
                    </td>
                    <td>
                      <span class="badge bg-light text-dark"><?php echo implode(", ", $dias_display); ?></span>
                    </td>
                    <td>
                      <span class="badge bg-<?php
                                            switch ($promo['estado']) {
                                              case 'aprobada':
                                                echo 'success';
                                                break;
                                              case 'denegada':
                                                echo 'danger';
                                                break;
                                              default:
                                                echo 'warning';
                                            }
                                            ?>">
                        <?php echo ucfirst($promo['estado']); ?>
                      </span>
                    </td>
                    <td>
                      <?php if ($promo['estado'] == 'pendiente'): ?>
                        <a href="promociones.php?eliminar=<?php echo $promo['id']; ?>"
                          class="btn btn-sm btn-danger"
                          onclick="return confirm('¿Estás seguro de eliminar esta promoción?')">
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

  <script>
    // Validación de fechas
    document.addEventListener('DOMContentLoaded', function() {
      const fechaInicio = document.querySelector('input[name="fecha_inicio"]');
      const fechaFin = document.querySelector('input[name="fecha_fin"]');

      fechaInicio.addEventListener('change', function() {
        fechaFin.min = this.value;
      });
    });
  </script>
</body>

</html>