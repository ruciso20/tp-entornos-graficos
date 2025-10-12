<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] != 'cliente') {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$nombre = $_SESSION['nombre'];
$categoria = $_SESSION['categoria'];

include("../config/db.php");

// Obtener historial de promociones
$query = "
    SELECT up.*, p.titulo, p.descripcion, p.categoria_minima, l.nombreLocal, l.rubroLocal
    FROM uso_promociones up
    JOIN promociones p ON up.codPromo = p.id
    JOIN locales l ON p.localid = l.codLocal
    WHERE up.codCliente = $user_id
    ORDER BY up.fechaUsoPromo DESC
";

$historial = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mi Historial - Cliente</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="../dashboard.php">
                <strong>🛍️ Shopping Rosario - Mi Historial</strong>
            </a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text text-white me-3">
                    <?php echo $nombre; ?> 
                    <span class="badge bg-<?php 
                        echo $categoria == 'Premium' ? 'danger' : 
                             ($categoria == 'Medium' ? 'warning' : 'primary'); 
                    ?>">
                        <?php echo $categoria; ?>
                    </span>
                </span>
                <a href="../dashboard.php" class="btn btn-outline-light me-2">← Volver</a>
                <a href="../logout.php" class="btn btn-outline-light">Cerrar Sesión</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-secondary text-white">
                        <h4>📊 Mi Historial de Promociones</h4>
                        <p class="mb-0">Todas las promociones que has utilizado</p>
                    </div>
                    <div class="card-body">
                        <?php if ($historial->num_rows > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Fecha</th>
                                            <th>Local</th>
                                            <th>Promoción</th>
                                            <th>Categoría</th>
                                            <th>Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($item = $historial->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo date('d/m/Y', strtotime($item['fechaUsoPromo']));