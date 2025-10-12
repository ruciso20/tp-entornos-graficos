<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] != 'cliente') {
    header("Location: ../login.php");
    exit;
}

include("../config/db.php");

$categoria_cliente = $_SESSION['categoria'];

// Obtener novedades según categoría del cliente
$novedades = $conn->query("
    SELECT * FROM novedades 
    WHERE fecha_fin >= CURDATE() 
    AND estado = 'activa'
    AND (
        categoria_objetivo = '$categoria_cliente'
        OR categoria_objetivo = 'Inicial'
        OR ('$categoria_cliente' = 'Premium' AND categoria_objetivo IN ('Inicial', 'Medium', 'Premium'))
        OR ('$categoria_cliente' = 'Medium' AND categoria_objetivo IN ('Inicial', 'Medium'))
    )
    ORDER BY fecha_inicio DESC
");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Novedades - Cliente</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="../dashboard.php">🛍️ Novedades</a>
            <div>
                <span class="navbar-text text-white me-3">
                    Categoría: <span class="badge bg-warning"><?php echo $categoria_cliente; ?></span>
                </span>
                <a href="../dashboard.php" class="btn btn-outline-light">← Volver</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h2>📢 Novedades para Ti</h2>
        <p class="text-muted">Estas son las novedades disponibles para tu categoría <strong><?php echo $categoria_cliente; ?></strong></p>

        <div class="row">
            <?php if ($novedades->num_rows > 0): ?>
                <?php while($novedad = $novedades->fetch_assoc()): 
                    $es_exclusiva = $novedad['categoria_objetivo'] == $categoria_cliente && $categoria_cliente != 'Inicial';
                ?>
                <div class="col-md-6 mb-4">
                    <div class="card h-100 <?php echo $es_exclusiva ? 'border-warning' : ''; ?>">
                        <div class="card-body">
                            <?php if ($es_exclusiva): ?>
                                <span class="badge bg-warning float-end">⭐ Exclusiva</span>
                            <?php endif; ?>
                            
                            <h5 class="card-title"><?php echo $novedad['titulo']; ?></h5>
                            
                            <?php if (!empty($novedad['descripcion'])): ?>
                                <p class="card-text"><?php echo $novedad['descripcion']; ?></p>
                            <?php endif; ?>
                            
                            <div class="mt-3">
                                <p class="mb-1">
                                    <strong>📅 Vigente:</strong> 
                                    <?php echo date('d/m/Y', strtotime($novedad['fecha_inicio'])); ?> 
                                    al <?php echo date('d/m/Y', strtotime($novedad['fecha_fin'])); ?>
                                </p>
                                
                                <span class="badge bg-<?php 
                                    echo $novedad['categoria_objetivo'] == 'Premium' ? 'danger' : 
                                         ($novedad['categoria_objetivo'] == 'Medium' ? 'warning' : 'info'); 
                                ?>">
                                    Para: <?php echo $novedad['categoria_objetivo']; ?>
                                </span>
                                
                                <?php if ($novedad['categoria_objetivo'] == $categoria_cliente): ?>
                                    <span class="badge bg-success ms-2">✅ Para tu categoría</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="alert alert-info text-center">
                        <h5>No hay novedades en este momento</h5>
                        <p>Vuelve más tarde para descubrir las últimas novedades del shopping.</p>
                        <a href="../dashboard.php" class="btn btn-primary">Volver al Dashboard</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>