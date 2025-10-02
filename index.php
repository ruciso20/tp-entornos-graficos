<?php
session_start();
if (!isset($_SESSION['rol'])) {
    header("Location: login.php");
    exit;
}

$rol    = $_SESSION['rol'];
$nombre = $_SESSION['nombre'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Shopping Rosario - Panel</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container-fluid">
    <a class="navbar-brand" href="index.php">Shopping Rosario</a>
    <div class="collapse navbar-collapse">
      <ul class="navbar-nav me-auto">
        <?php if ($rol == 'admin'): ?>
          <li class="nav-item"><a class="nav-link" href="admin_locales.php">Locales</a></li>
          <li class="nav-item"><a class="nav-link" href="admin_usuarios.php">Usuarios</a></li>
          <li class="nav-item"><a class="nav-link" href="admin_promos.php">Promociones</a></li>
          <li class="nav-item"><a class="nav-link" href="admin_novedades.php">Novedades</a></li>
        <?php elseif ($rol == 'dueno'): ?>
          <li class="nav-item"><a class="nav-link" href="dueno_promos.php">Mis Promos</a></li>
          <li class="nav-item"><a class="nav-link" href="dueno_reportes.php">Reportes</a></li>
        <?php elseif ($rol == 'cliente'): ?>
          <li class="nav-item"><a class="nav-link" href="cliente_promos.php">Ver Promos</a></li>
          <li class="nav-item"><a class="nav-link" href="cliente_novedades.php">Novedades</a></li>
        <?php endif; ?>
      </ul>
      <span class="navbar-text text-white me-3">
        Hola, <?php echo $nombre; ?> (<?php echo ucfirst($rol); ?>)
      </span>
      <a href="logout.php" class="btn btn-outline-light">Salir</a>
    </div>
  </div>
</nav>

<div class="container mt-4">
  <div class="card p-4 shadow">
    <h2>Bienvenido al sistema de promociones del Shopping</h2>
    <p>
      Aquí podrás acceder a las funciones según tu rol:
      <strong><?php echo ucfirst($rol); ?></strong>
    </p>
  </div>
</div>

</body>
</html>
