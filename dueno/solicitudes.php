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
$locales_query = $conn->prepare("SELECT id, nombre FROM locales WHERE dueno_id = ? AND estado = 'activo'");
$locales_query->bind_param("i", $dueno_id);
$locales_query->execute();
$locales_result = $locales_query->get_result();
$locales = $locales_result->fetch_all(MYSQLI_ASSOC);

if (count($locales) == 0) {
  die("No tienes locales asignados o activos.");
}

// Determinar el local actual (por defecto el primero, o el seleccionado)
$local_actual_id = $locales[0]['id'];
if (isset($_GET['local_id']) && is_numeric($_GET['local_id'])) {
  $local_actual_id = $_GET['local_id'];

  // Verificar que el local pertenece al dueño
  $local_pertenece = false;
  foreach ($locales as $local) {
    if ($local['id'] == $local_actual_id) {
      $local_pertenece = true;
      break;
    }
  }

  if (!$local_pertenece) {
    $local_actual_id = $locales[0]['id'];
  }
}

// Obtener datos del local actual
$local_actual = null;
foreach ($locales as $local) {
  if ($local['id'] == $local_actual_id) {
    $local_actual = $local;
    break;
  }
}

// Procesar aceptar/rechazar solicitud
if (isset($_POST['accion_solicitud'])) {
  $solicitud_id = $_POST['solicitud_id'];
  $accion = $_POST['accion'];
  $nuevo_estado = ($accion == 'aceptar') ? 'aceptada' : 'rechazada';

  // Verificar que la solicitud pertenece a una promoción del dueño
  $verificar = $conn->prepare("
        SELECT up.id 
        FROM uso_promociones up 
        JOIN promociones p ON up.promocion_id = p.id 
        WHERE up.id = ? AND p.local_id IN (
            SELECT id FROM locales WHERE dueno_id = ?
        )
    ");
  $verificar->bind_param("ii", $solicitud_id, $dueno_id);
  $verificar->execute();

  if ($verificar->get_result()->num_rows > 0) {
    $update = $conn->prepare("UPDATE uso_promociones SET estado = ? WHERE id = ?");
    $update->bind_param("si", $nuevo_estado, $solicitud_id);

    if ($update->execute()) {
      $mensaje = "✅ Solicitud " . $nuevo_estado . " exitosamente.";
    } else {
      $mensaje = "❌ Error al procesar la solicitud.";
    }
  } else {
    $mensaje = "❌ No tienes permisos para modificar esta solicitud.";
  }
}

// Obtener solicitudes pendientes del local actual
$solicitudes_query = $conn->prepare("
    SELECT up.id, up.fecha_uso, up.estado, 
           p.titulo as promocion_titulo,
           u.nombre as cliente_nombre,
           u.categoria_cliente as cliente_categoria
    FROM uso_promociones up
    JOIN promociones p ON up.promocion_id = p.id
    JOIN usuarios u ON up.cliente_id = u.id
    WHERE p.local_id = ? AND up.estado = 'enviada'
    ORDER BY up.fecha_uso DESC
");
$solicitudes_query->bind_param("i", $local_actual_id);
$solicitudes_query->execute();
$solicitudes = $solicitudes_query->get_result();

// Contar solicitudes por local para el badge
$contador_query = $conn->prepare("
    SELECT l.id, l.nombre, COUNT(up.id) as pendientes
    FROM locales l
    LEFT JOIN promociones p ON l.id = p.local_id
    LEFT JOIN uso_promociones up ON p.id = up.promocion_id AND up.estado = 'enviada'
    WHERE l.dueno_id = ?
    GROUP BY l.id, l.nombre
    ORDER BY l.nombre
");
$contador_query->bind_param("i", $dueno_id);
$contador_query->execute();
$contadores = $contador_query->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Solicitudes de Descuento - Dueño</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
  <nav class="navbar navbar-dark bg-dark">
    <div class="container">
      <a class="navbar-brand" href="../dashboard.php">🛍️ Dueño - Solicitudes</a>
      <a href="../dashboard.php" class="btn btn-outline-light">Volver</a>
    </div>
  </nav>

  <div class="container mt-4">
    <!-- Selector de Local -->
    <div class="card mb-4">
      <div class="card-header">
        <h5>Seleccionar Local</h5>
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-8">
            <form method="GET" class="d-flex">
              <select name="local_id" class="form-select me-2" onchange="this.form.submit()">
                <?php foreach ($contadores as $local_contador): ?>
                  <option value="<?php echo $local_contador['id']; ?>"
                    <?php echo $local_contador['id'] == $local_actual_id ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($local_contador['nombre']); ?>
                    <?php if ($local_contador['pendientes'] > 0): ?>
                      (<?php echo $local_contador['pendientes']; ?> pendiente<?php echo $local_contador['pendientes'] > 1 ? 's' : ''; ?>)
                    <?php endif; ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </form>
          </div>
        </div>
      </div>
    </div>

    <h2>Solicitudes de Descuento - <?php echo htmlspecialchars($local_actual['nombre']); ?></h2>

    <?php if ($mensaje): ?>
      <div class="alert alert-info"><?php echo $mensaje; ?></div>
    <?php endif; ?>

    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5>Solicitudes Pendientes</h5>
        <span class="badge bg-warning"><?php echo $solicitudes->num_rows; ?> pendiente<?php echo $solicitudes->num_rows != 1 ? 's' : ''; ?></span>
      </div>
      <div class="card-body">
        <?php if ($solicitudes->num_rows > 0): ?>
          <div class="table-responsive">
            <table class="table table-striped">
              <thead>
                <tr>
                  <th>Cliente</th>
                  <th>Categoría</th>
                  <th>Promoción</th>
                  <th>Fecha Solicitud</th>
                  <th>Estado</th>
                  <th>Acciones</th>
                </tr>
              </thead>
              <tbody>
                <?php while ($solicitud = $solicitudes->fetch_assoc()): ?>
                  <tr>
                    <td><strong><?php echo htmlspecialchars($solicitud['cliente_nombre']); ?></strong></td>
                    <td>
                      <span class="badge bg-<?php
                                            switch ($solicitud['cliente_categoria']) {
                                              case 'premium':
                                                echo 'success';
                                                break;
                                              case 'medium':
                                                echo 'warning';
                                                break;
                                              default:
                                                echo 'secondary';
                                            }
                                            ?>">
                        <?php echo ucfirst($solicitud['cliente_categoria']); ?>
                      </span>
                    </td>
                    <td><?php echo htmlspecialchars($solicitud['promocion_titulo']); ?></td>
                    <td><?php echo date('d/m/Y H:i', strtotime($solicitud['fecha_uso'])); ?></td>
                    <td>
                      <span class="badge bg-warning"><?php echo ucfirst($solicitud['estado']); ?></span>
                    </td>
                    <td>
                      <form method="POST" class="d-inline">
                        <input type="hidden" name="solicitud_id" value="<?php echo $solicitud['id']; ?>">
                        <button type="submit" name="accion_solicitud" value="aceptar" class="btn btn-sm btn-success">✅ Aceptar</button>
                        <button type="submit" name="accion_solicitud" value="rechazar" class="btn btn-sm btn-danger">❌ Rechazar</button>
                      </form>
                    </td>
                  </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <div class="alert alert-info">
            No hay solicitudes pendientes para <strong><?php echo htmlspecialchars($local_actual['nombre']); ?></strong> en este momento.
          </div>
        <?php endif; ?>
      </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>