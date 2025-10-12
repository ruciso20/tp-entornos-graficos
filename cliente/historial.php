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
    SELECT up.*, p.textoPromo, p.categoriaCliente, l.nombreLocal, l.rubroLocal
    FROM uso_promociones up
    JOIN promociones p ON up.codPromo = p.codPromo
    JOIN locales l ON p.codLocal = l.codLocal
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
                                                <td><?php echo date('d/m/Y', strtotime($item['fechaUsoPromo'])); ?></td>
                                                <td>
                                                    <strong><?php echo $item['nombreLocal']; ?></strong><br>
                                                    <small class="text-muted"><?php echo $item['rubroLocal']; ?></small>
                                                </td>
                                                <td><?php echo $item['textoPromo']; ?></td>
                                                <td>
                                                    <span class="badge bg-<?php 
                                                        echo $item['categoriaCliente'] == 'Premium' ? 'danger' : 
                                                             ($item['categoriaCliente'] == 'Medium' ? 'warning' : 'primary'); 
                                                    ?>">
                                                        <?php echo $item['categoriaCliente']; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php 
                                                        echo $item['estado'] == 'aceptada' ? 'success' : 
                                                             ($item['estado'] == 'rechazada' ? 'danger' : 'warning'); 
                                                    ?>">
                                                        <?php 
                                                        $estados = [
                                                            'enviada' => '🕒 Pendiente',
                                                            'aceptada' => '✅ Aceptada', 
                                                            'rechazada' => '❌ Rechazada'
                                                        ];
                                                        echo $estados[$item['estado']];
                                                        ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info text-center">
                                <h5>No hay historial de promociones</h5>
                                <p>No has utilizado ninguna promoción todavía.</p>
                                <a href="promociones.php" class="btn btn-primary">Ver Promociones Disponibles</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>