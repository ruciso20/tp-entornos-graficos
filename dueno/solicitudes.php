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
$locales_query = $conn->prepare("SELECT id, nombre FROM locales WHERE dueno_id = ? AND estado = 'aprobado'");
$locales_query->bind_param("i", $dueno_id);
$locales_query->execute();
$locales_result = $locales_query->get_result();
$locales = $locales_result->fetch_all(MYSQLI_ASSOC);

// Determinar el local actual (por defecto el primero, o el seleccionado)
$local_actual_id = $locales[0]['id'];
if (empty($locales)) {
  die("No tienes locales aprobados para gestionar solicitudes.");
}
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
if (isset($_POST['aceptar']) || isset($_POST['rechazar'])) {
  $solicitud_id = $_POST['solicitud_id'];

  // Determinar la acción basada en qué botón se presionó
  if (isset($_POST['aceptar'])) {
    $nuevo_estado = 'usada';
    $mensaje = "✅ Solicitud aceptada correctamente";
  } else {
    $nuevo_estado = 'rechazada';
    $mensaje = "❌ Solicitud rechazada";
  }

  // Verificar que la solicitud pertenece a una promoción del dueño
  $verificar = $conn->prepare("
        SELECT up.id 
        FROM uso_promociones up 
        WHERE up.id = ? AND up.local_id IN (
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
           p.descripcion as promocion_descripcion,
           u.nombre as cliente_nombre,
           u.email as cliente_email,
           u.categoria_cliente as cliente_categoria
    FROM uso_promociones up
    JOIN promociones p ON up.promocion_id = p.id
    JOIN usuarios u ON up.cliente_id = u.id
    WHERE up.local_id = ? AND up.estado = 'pendiente'
    ORDER BY up.fecha_uso DESC
");
$solicitudes_query->bind_param("i", $local_actual_id);
$solicitudes_query->execute();
$solicitudes = $solicitudes_query->get_result();

// Contar solicitudes por local para el badge
$contador_query = $conn->prepare("
    SELECT l.id, l.nombre, COUNT(up.id) as pendientes
    FROM locales l
    LEFT JOIN uso_promociones up ON l.id = up.local_id AND up.estado = 'pendiente'
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
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    .solicitud-card {
      border-left: 4px solid #ffc107;
      transition: all 0.3s ease;
    }

    .solicitud-card:hover {
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }
  </style>
</head>

<body>
  <nav class="navbar navbar-dark bg-dark">
    <div class="container-fluid">
      <div class="navbar-brand">
        <a class="navbar-brand fw-bold" href="../index.php">
          <span aria-hidden="true">🛍️</span>
          <span class="ms-1">Stella Shopping Rosario</span>
        </a>
        <span class="navbar-text text-light">Gestionar Solicitudes de Descuento</span>
      </div>
      <div class="d-flex">
        <a href="../dashboard.php" class="btn btn-outline-light">Volver</a>
      </div>
    </div>
  </nav>

  <div class="container mt-4">
    <!-- selector de Local -->
    <div class="card mb-4">
      <div class="card-header">
        <h5>Seleccionar Local</h5>
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-8">
            <form method="GET" class="d-flex">
              <label for="local_id" class="visually-hidden">Seleccionar local</label>
              <select id="local_id" name="local_id" class="form-select me-2" onchange="this.form.submit()">
                <?php foreach ($contadores as $local_contador): ?>
                  <option value="<?php echo $local_contador['id']; ?>">
                    <?php echo htmlspecialchars($local_contador['nombre']); ?>
                    <?php if ($local_contador['pendientes'] > 0): ?>
                      (<?php echo $local_contador['pendientes']; ?> pendientes)
                    <?php endif; ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </form>
          </div>
        </div>
      </div>
    </div>

    <h1 class="h2">Solicitudes de Descuento - <?php echo htmlspecialchars($local_actual['nombre']); ?></h1>


    <?php if ($mensaje): ?>
      <div class="alert alert-info"><?php echo $mensaje; ?></div>
    <?php endif; ?>

    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5>Solicitudes Pendientes</h5>
      </div>
      <div class="card-body">
        <?php if ($solicitudes->num_rows > 0): ?>
          <div class="row">
            <?php while ($solicitud = $solicitudes->fetch_assoc()): ?>
              <div class="col-md-6 mb-3">
                <div class="card solicitud-card h-100">
                  <div class="card-body">
                    <h5 class="card-title"><?php echo htmlspecialchars($solicitud['promocion_titulo']); ?></h5>
                    <p class="card-text text-muted"><?php echo htmlspecialchars($solicitud['promocion_descripcion']); ?></p>

                    <div class="mb-2">
                      <strong>Cliente:</strong> <?php echo htmlspecialchars($solicitud['cliente_nombre']); ?>
                    </div>
                    <div class="mb-2">
                      <strong>Email:</strong> <?php echo htmlspecialchars($solicitud['cliente_email']); ?>
                    </div>
                    <div class="mb-2">
                      <strong>Categoría:</strong>
                      <span class="badge bg-<?php
                                            echo $solicitud['cliente_categoria'] == 'premium' ? 'danger' : ($solicitud['cliente_categoria'] == 'medium' ? 'warning' : 'info');
                                            ?>">
                        <?php echo ucfirst($solicitud['cliente_categoria']); ?>
                      </span>
                    </div>
                    <div class="mb-3">
                      <strong>Solicitado:</strong>
                      <?php echo date('d/m/Y H:i', strtotime($solicitud['fecha_uso'])); ?>
                    </div>

                    <!-- formulario -->
                    <form method="POST" class="text-end">
                      <fieldset>
                        <legend class="visually-hidden">Acciones sobre la solicitud</legend>
                        <input type="hidden" name="solicitud_id" value="<?php echo $solicitud['id']; ?>">
                        <button type="submit" name="aceptar" value="1"
                          class="btn btn-success btn-sm"
                          aria-label="Aceptar solicitud de descuento"
                          onclick="return confirm('¿Aceptar esta solicitud?')">
                          <span aria-hidden="true">✅</span> Aceptar
                        </button>

                        <button type="submit" name="rechazar" value="1"
                          class="btn btn-danger btn-sm"
                          aria-label="Rechazar solicitud de descuento"
                          onclick="return confirm('¿Rechazar esta solicitud?')">
                          <span aria-hidden="true">❌</span> Rechazar
                        </button>
                      </fieldset>
                    </form>
                  </div>
                </div>
              </div>
            <?php endwhile; ?>
          </div>
        <?php else: ?>
          <div class="alert alert-info text-center py-4">
            <h5>No hay solicitudes pendientes</h5>
            <p class="text-muted mb-0">Cuando los clientes usen promociones de <strong><?php echo htmlspecialchars($local_actual['nombre']); ?></strong>, aparecerán aquí.</p>
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