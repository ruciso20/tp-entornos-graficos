<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] != 'dueno') {
  header("Location: ../login.php");
  exit;
}

include("../config/db.php");

$dueno_id = $_SESSION['user_id'];

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

// Obtener estadísticas de uso del local actual
$estadisticas_query = $conn->prepare("
    SELECT 
        p.titulo,
        p.categoria_minima,
        COUNT(up.id) as total_usos,
        SUM(CASE WHEN up.estado = 'usada' THEN 1 ELSE 0 END) as usos_aceptados,
        SUM(CASE WHEN up.estado = 'rechazada' THEN 1 ELSE 0 END) as usos_rechazados,
        SUM(CASE WHEN up.estado = 'pendiente' THEN 1 ELSE 0 END) as usos_pendientes
    FROM promociones p
    LEFT JOIN uso_promociones up ON p.id = up.promocion_id
    WHERE p.local_id = ?
    GROUP BY p.id
    ORDER BY total_usos DESC
");
$estadisticas_query->bind_param("i", $local_actual_id);
$estadisticas_query->execute();
$estadisticas = $estadisticas_query->get_result();

// Obtener totales generales del local actual
$totales_query = $conn->prepare("
    SELECT 
        COUNT(up.id) as total_solicitudes,
        SUM(CASE WHEN up.estado = 'usada' THEN 1 ELSE 0 END) as total_aceptadas,
        SUM(CASE WHEN up.estado = 'rechazada' THEN 1 ELSE 0 END) as total_rechazadas,
        SUM(CASE WHEN up.estado = 'pendiente' THEN 1 ELSE 0 END) as total_pendientes
    FROM uso_promociones up
    JOIN promociones p ON up.promocion_id = p.id
    WHERE p.local_id = ?
");
$totales_query->bind_param("i", $local_actual_id);
$totales_query->execute();
$totales = $totales_query->get_result()->fetch_assoc();

// Obtener resumen de todos los locales para el selector de locales
$resumen_locales_query = $conn->prepare("
    SELECT 
        l.id,
        l.nombre,
        COUNT(up.id) as total_solicitudes,
        SUM(CASE WHEN up.estado = 'usada' THEN 1 ELSE 0 END) as aceptadas,
        SUM(CASE WHEN up.estado = 'rechazada' THEN 1 ELSE 0 END) as rechazadas,
        SUM(CASE WHEN up.estado = 'pendiente' THEN 1 ELSE 0 END) as pendientes
    FROM locales l
    LEFT JOIN promociones p ON l.id = p.local_id
    LEFT JOIN uso_promociones up ON p.id = up.promocion_id
    WHERE l.dueno_id = ?
    GROUP BY l.id, l.nombre
    ORDER BY l.nombre
");
$resumen_locales_query->bind_param("i", $dueno_id);
$resumen_locales_query->execute();
$resumen_locales = $resumen_locales_query->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reportes de Promociones - Dueño</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .stats-card {
      border: none;
      border-radius: 10px;
      transition: transform 0.2s;
    }

    .stats-card:hover {
      transform: translateY(-5px);
    }

    .progress {
      height: 25px;
    }

    .progress-bar {
      border-radius: 5px;
    }

    .local-card {
      cursor: pointer;
      transition: all 0.3s;
    }

    .local-card:hover {
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }

    .local-actual {
      border: 2px solid #007bff;
      background-color: #f8f9fa;
    }
  </style>
</head>

<body>
  <!-- Navbar -->
  <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
      <a class="navbar-brand" href="../dashboard.php">🛍️ Dueño - Reportes</a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ms-auto">
          <a href="../dashboard.php" class="btn btn-outline-light">Volver</a>
        </ul>
      </div>
    </div>
  </nav>

  <div class="container mt-4">
    <!-- Selector de Local -->
    <div class="card mb-4">
      <div class="card-header  bg-light text-dark">
        <h5 class="mb-0">Seleccionar Local</h5>
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-6">
            <form method="GET" class="d-flex">
              <select name="local_id" class="form-select me-2" onchange="this.form.submit()">
                <?php foreach ($resumen_locales as $local_resumen): ?>
                  <option value="<?php echo $local_resumen['id']; ?>"
                    <?php echo $local_resumen['id'] == $local_actual_id ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($local_resumen['nombre']); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </form>
          </div>
        </div>
      </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2>📊 Reportes de Uso - <?php echo htmlspecialchars($local_actual['nombre']); ?></h2>
    </div>

    <!-- Resumen General -->
    <div class="row mb-4">
      <div class="col-md-3 mb-3">
        <div class="card stats-card text-white bg-primary">
          <div class="card-body text-center">
            <h5 class="card-title">Total Solicitudes</h5>
            <h2 class="card-text"><?php echo $totales['total_solicitudes']; ?></h2>
          </div>
        </div>
      </div>
      <div class="col-md-3 mb-3">
        <div class="card stats-card text-white bg-success">
          <div class="card-body text-center">
            <h5 class="card-title">Aceptadas</h5>
            <h2 class="card-text"><?php echo $totales['total_aceptadas']; ?></h2>
          </div>
        </div>
      </div>
      <div class="col-md-3 mb-3">
        <div class="card stats-card text-white bg-danger">
          <div class="card-body text-center">
            <h5 class="card-title">Rechazadas</h5>
            <h2 class="card-text"><?php echo $totales['total_rechazadas']; ?></h2>
          </div>
        </div>
      </div>
      <div class="col-md-3 mb-3">
        <div class="card stats-card text-white bg-warning">
          <div class="card-body text-center">
            <h5 class="card-title">Pendientes</h5>
            <h2 class="card-text"><?php echo $totales['total_pendientes']; ?></h2>
          </div>
        </div>
      </div>
    </div>

    <!-- Detalle por promoción -->
    <div class="card border shadow-sm">
      <div class="card-header bg-light text-dark border-bottom">
        <div class="d-flex justify-content-between align-items-center">
          <h5 class="mb-0">Detalle por Promoción - <?php echo htmlspecialchars($local_actual['nombre']); ?></h5>
        </div>
      </div>
      <div class="card-body p-0">
        <?php if ($estadisticas->num_rows > 0): ?>
          <div class="table-responsive">
            <table class="table table-hover mb-0">
              <thead class="table-light">
                <tr>
                  <th class="border-end">Promoción</th>
                  <th class="border-end">Categoría</th>
                  <th class="border-end text-center">Total Usos</th>
                  <th class="border-end text-center">Aceptadas</th>
                  <th class="border-end text-center">Rechazadas</th>
                  <th class="border-end text-center">Pendientes</th>
                  <th class="text-center">Tasa Aceptación</th>
                </tr>
              </thead>
              <tbody>
                <?php while ($estadistica = $estadisticas->fetch_assoc()):
                  $tasa_aceptacion = $estadistica['total_usos'] > 0 ?
                    round(($estadistica['usos_aceptados'] / $estadistica['total_usos']) * 100, 2) : 0;
                ?>
                  <tr>
                    <td class="border-end"><strong><?php echo htmlspecialchars($estadistica['titulo']); ?></strong></td>
                    <td class="border-end">
                      <span class="badge bg-<?php
                                            echo $estadistica['categoria_minima'] == 'premium' ? 'danger' : ($estadistica['categoria_minima'] == 'medium' ? 'warning' : 'info');
                                            ?>">
                        <?php echo ucfirst($estadistica['categoria_minima']); ?>
                      </span>
                    </td>
                    <td class="border-end text-center"><strong><?php echo $estadistica['total_usos']; ?></strong></td>
                    <td class="border-end text-center">
                      <span class="badge bg-success"><?php echo $estadistica['usos_aceptados']; ?></span>
                    </td>
                    <td class="border-end text-center">
                      <span class="badge bg-danger"><?php echo $estadistica['usos_rechazados']; ?></span>
                    </td>
                    <td class="border-end text-center">
                      <span class="badge bg-warning"><?php echo $estadistica['usos_pendientes']; ?></span>
                    </td>
                    <td class="text-center">
                      <div class="progress mx-2" style="height: 20px;">
                        <div class="progress-bar" role="progressbar"
                          style="width: <?php echo $tasa_aceptacion; ?>%"
                          aria-valuenow="<?php echo $tasa_aceptacion; ?>"
                          aria-valuemin="0" aria-valuemax="100">
                          <span class="fw-bold"><?php echo $tasa_aceptacion; ?>%</span>
                        </div>
                      </div>
                    </td>
                  </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <div class="text-center py-5"> <!-- Centrado vertical y horizontal -->
            <div class="py-4">
              <h5 class="text-muted">No hay datos de reportes</h5>
              <p class="text-muted mb-0">Aún no hay solicitudes para las promociones de <strong><?php echo htmlspecialchars($local_actual['nombre']); ?></strong>.</p>
            </div>
          </div>
        <?php endif; ?>
      </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>