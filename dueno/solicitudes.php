<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] != 'dueno') {
  header("Location: ../login.php");
  exit;
}

include("../config/db.php");

$dueno_id = $_SESSION['user_id'];
$mensaje = "";

// Obtener el local del dueño
$local_query = $conn->prepare("SELECT id, nombre FROM locales WHERE dueno_id = ?");
$local_query->bind_param("i", $dueno_id);
$local_query->execute();
$local_result = $local_query->get_result();
$local = $local_result->fetch_assoc();

if (!$local) {
  die("No tienes un local asignado.");
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
        WHERE up.id = ? AND p.local_id = ?
    ");
  $verificar->bind_param("ii", $solicitud_id, $local['id']);
  $verificar->execute();

  if ($verificar->get_result()->num_rows > 0) {
    $update = $conn->prepare("UPDATE uso_promociones SET estado = ? WHERE id = ?");
    $update->bind_param("si", $nuevo_estado, $solicitud_id);

    if ($update->execute()) {
      $mensaje = "Solicitud " . $nuevo_estado . " exitosamente.";
    } else {
      $mensaje = "Error al procesar la solicitud.";
    }
  } else {
    $mensaje = "No tienes permisos para modificar esta solicitud.";
  }
}

// Obtener solicitudes pendientes del local
$solicitudes_query = $conn->prepare("
    SELECT up.id, up.fecha_uso, up.estado, 
           p.titulo as promocion_titulo,
           u.nombre as cliente_nombre,
           u.categoria as cliente_categoria
    FROM uso_promociones up
    JOIN promociones p ON up.promocion_id = p.id
    JOIN usuarios u ON up.cliente_id = u.id
    WHERE p.local_id = ? AND up.estado = 'enviada'
    ORDER BY up.fecha_uso DESC
");
$solicitudes_query->bind_param("i", $local['id']);
$solicitudes_query->execute();
$solicitudes = $solicitudes_query->get_result();
?>

<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Solicitudes de Descuento</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
  <div class="container-fluid">
    <div class="row">
      <!-- Sidebar -->
      <?php include("../layouts/sidebar_dueno.php"); ?>

      <!-- Main Content -->
      <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
          <h1 class="h2">Solicitudes de Descuento - <?php echo $local['nombre']; ?></h1>
        </div>

        <?php if ($mensaje): ?>
          <div class="alert alert-info"><?php echo $mensaje; ?></div>
        <?php endif; ?>

        <div class="card">
          <div class="card-header">
            <h5>Solicitudes Pendientes</h5>
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
                        <td><?php echo htmlspecialchars($solicitud['cliente_nombre']); ?></td>
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
                            <button type="submit" name="accion_solicitud" value="aceptar" class="btn btn-sm btn-success">Aceptar</button>
                            <button type="submit" name="accion_solicitud" value="rechazar" class="btn btn-sm btn-danger">Rechazar</button>
                          </form>
                        </td>
                      </tr>
                    <?php endwhile; ?>
                  </tbody>
                </table>
              </div>
            <?php else: ?>
              <div class="alert alert-info">
                No hay solicitudes pendientes en este momento.
              </div>
            <?php endif; ?>
          </div>
        </div>
      </main>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>