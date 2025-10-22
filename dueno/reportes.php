<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] != 'dueno') {
  header("Location: ../login.php");
  exit;
}

include("../config/db.php");

$dueno_id = $_SESSION['user_id'];

// Obtener el local del dueño
$local_query = $conn->prepare("SELECT id, nombre FROM locales WHERE dueno_id = ?");
$local_query->bind_param("i", $dueno_id);
$local_query->execute();
$local_result = $local_query->get_result();
$local = $local_result->fetch_assoc();

if (!$local) {
  die("No tienes un local asignado.");
}

// Obtener estadísticas de uso
$estadisticas_query = $conn->prepare("
    SELECT 
        p.titulo,
        p.categoria_minima,
        COUNT(up.id) as total_usos,
        SUM(CASE WHEN up.estado = 'aceptada' THEN 1 ELSE 0 END) as usos_aceptados,
        SUM(CASE WHEN up.estado = 'rechazada' THEN 1 ELSE 0 END) as usos_rechazados,
        SUM(CASE WHEN up.estado = 'enviada' THEN 1 ELSE 0 END) as usos_pendientes
    FROM promociones p
    LEFT JOIN uso_promociones up ON p.id = up.promocion_id
    WHERE p.local_id = ?
    GROUP BY p.id
    ORDER BY total_usos DESC
");
$estadisticas_query->bind_param("i", $local['id']);
$estadisticas_query->execute();
$estadisticas = $estadisticas_query->get_result();

// Obtener totales generales
$totales_query = $conn->prepare("
    SELECT 
        COUNT(up.id) as total_solicitudes,
        SUM(CASE WHEN up.estado = 'aceptada' THEN 1 ELSE 0 END) as total_aceptadas,
        SUM(CASE WHEN up.estado = 'rechazada' THEN 1 ELSE 0 END) as total_rechazadas
    FROM uso_promociones up
    JOIN promociones p ON up.promocion_id = p.id
    WHERE p.local_id = ?
");
$totales_query->bind_param("i", $local['id']);
$totales_query->execute();
$totales = $totales_query->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reportes de Promociones</title>
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
          <h1 class="h2">Reportes de Uso - <?php echo $local['nombre']; ?></h1>
        </div>

        <!-- Resumen General -->
        <div class="row mb-4">
          <div class="col-md-4">
            <div class="card text-white bg-primary">
              <div class="card-body">
                <h5 class="card-title">Total Solicitudes</h5>
                <h2 class="card-text"><?php echo $totales['total_solicitudes']; ?></h2>
              </div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="card text-white bg-success">
              <div class="card-body">
                <h5 class="card-title">Aceptadas</h5>
                <h2 class="card-text"><?php echo $totales['total_aceptadas']; ?></h2>
              </div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="card text-white bg-danger">
              <div class="card-body">
                <h5 class="card-title">Rechazadas</h5>
                <h2 class="card-text"><?php echo $totales['total_rechazadas']; ?></h2>
              </div>
            </div>
          </div>
        </div>

        <!-- Detalle por promoción -->
        <div class="card">
          <div class="card-header">
            <h5>Detalle por Promoción</h5>
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-striped">
                <thead>
                  <tr>
                    <th>Promoción</th>
                    <th>Categoría</th>
                    <th>Total Usos</th>
                    <th>Aceptadas</th>
                    <th>Rechazadas</th>
                    <th>Pendientes</th>
                    <th>Tasa Aceptación</th>
                  </tr>
                </thead>
                <tbody>
                  <?php while ($estadistica = $estadisticas->fetch_assoc()):
                    $tasa_aceptacion = $estadistica['total_usos'] > 0 ?
                      round(($estadistica['usos_aceptados'] / $estadistica['total_usos']) * 100, 2) : 0;
                  ?>
                    <tr>
                      <td><?php echo htmlspecialchars($estadistica['titulo']); ?></td>
                      <td>
                        <span class="badge bg-secondary">
                          <?php echo ucfirst($estadistica['categoria_minima']); ?>
                        </span>
                      </td>
                      <td><?php echo $estadistica['total_usos']; ?></td>
                      <td>
                        <span class="badge bg-success"><?php echo $estadistica['usos_aceptados']; ?></span>
                      </td>
                      <td>
                        <span class="badge bg-danger"><?php echo $estadistica['usos_rechazados']; ?></span>
                      </td>
                      <td>
                        <span class="badge bg-warning"><?php echo $estadistica['usos_pendientes']; ?></span>
                      </td>
                      <td>
                        <div class="progress">
                          <div class="progress-bar bg-success" role="progressbar"
                            style="width: <?php echo $tasa_aceptacion; ?>%"
                            aria-valuenow="<?php echo $tasa_aceptacion; ?>"
                            aria-valuemin="0" aria-valuemax="100">
                            <?php echo $tasa_aceptacion; ?>%
                          </div>
                        </div>
                      </td>
                    </tr>
                  <?php endwhile; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </main>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>