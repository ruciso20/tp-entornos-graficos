<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] != 'admin') {
    header("Location: ../login.php");
    exit;
}

include("../config/db.php");

// Acciones sobre promociones
if (isset($_GET['accion'])) {
    $id = $_GET['id'];
    $accion = $_GET['accion'];
    
    if (in_array($accion, ['aprobada', 'denegada'])) {
        $conn->query("UPDATE promociones SET estadoPromo='$accion' WHERE codPromo=$id");
        $success = "Promoción " . $accion;
    }
}

// Obtener promociones - CONSULTA SIMPLIFICADA
$promociones = $conn->query("SELECT * FROM promociones ORDER BY fechaCreacion DESC");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Promociones</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="../dashboard.php">🛍️ Admin - Promociones</a>
            <a href="../dashboard.php" class="btn btn-outline-light">← Volver</a>
        </div>
    </nav>

    <div class="container mt-4">
        <h2>Gestión de Promociones</h2>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h5>Promociones</h5>
            </div>
            <div class="card-body">
                <?php if ($promociones->num_rows == 0): ?>
                    <div class="alert alert-info">No hay promociones</div>
                <?php else: ?>
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Promoción</th>
                                <th>Vigencia</th>
                                <th>Categoría</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($promo = $promociones->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $promo['codPromo']; ?></td>
                                <td><?php echo $promo['textoPromo']; ?></td>
                                <td>
                                    <?php echo date('d/m/Y', strtotime($promo['fechaDesdePromo'])); ?> -<br>
                                    <?php echo date('d/m/Y', strtotime($promo['fechaHastaPromo'])); ?>
                                </td>
                                <td><?php echo $promo['categoriaCliente']; ?></td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $promo['estadoPromo'] == 'aprobada' ? 'success' : 
                                             ($promo['estadoPromo'] == 'pendiente' ? 'warning' : 'danger'); 
                                    ?>">
                                        <?php echo $promo['estadoPromo']; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($promo['estadoPromo'] == 'pendiente'): ?>
                                        <a href="?accion=aprobada&id=<?php echo $promo['codPromo']; ?>" class="btn btn-success btn-sm">Aprobar</a>
                                        <a href="?accion=denegada&id=<?php echo $promo['codPromo']; ?>" class="btn btn-danger btn-sm">Denegar</a>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>