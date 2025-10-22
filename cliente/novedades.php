<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] != 'cliente') {
    header("Location: ../login.php");
    exit;
}

$categoria = $_SESSION['categoria'];
$nombre = $_SESSION['nombre'];

include("../config/db.php");

// Obtener novedades según categoría
$query = "
    SELECT * FROM novedades 
    WHERE fecha_fin >= CURDATE() 
    AND fecha_inicio <= CURDATE()
    AND estado = 'activa'
    AND (
        categoria_objetivo = '$categoria'
        OR categoria_objetivo = 'Inicial'
        OR ('$categoria' = 'Premium' AND categoria_objetivo IN ('Inicial', 'Medium', 'Premium'))
        OR ('$categoria' = 'Medium' AND categoria_objetivo IN ('Inicial', 'Medium'))
    )
    ORDER BY fecha_inicio DESC
";

$novedades = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Novedades - Cliente</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand">
                <strong>Shopping Rosario - Novedades</strong>
            </a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text text-white me-3">
                    <?php echo $nombre; ?>
                    <span class="badge bg-<?php
                                            echo $categoria == 'Premium' ? 'danger' : ($categoria == 'Medium' ? 'warning' : 'primary');
                                            ?>">
                        <?php echo $categoria; ?>
                    </span>
                </span>
                <a href="../index.php" class="btn btn-outline-light me-2">Volver</a>
                <a href="../logout.php" class="btn btn-outline-light">Cerrar Sesión</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h4>Novedades del Shopping</h4>
                        <p class="mb-0">Mantente informado de las últimas novedades</p>
                    </div>
                    <div class="card-body">
                        <?php if ($novedades->num_rows > 0): ?>
                            <div class="row">
                                <?php while ($novedad = $novedades->fetch_assoc()): ?>
                                    <div class="col-md-6 mb-4">
                                        <div class="card h-100">
                                            <div class="card-header d-flex justify-content-between align-items-center">
                                                <h5 class="mb-0">📢 <?php echo $novedad['titulo']; ?></h5>
                                                <span class="badge bg-<?php
                                                                        echo $novedad['categoria_objetivo'] == 'Premium' ? 'danger' : ($novedad['categoria_objetivo'] == 'Medium' ? 'warning' : 'primary');
                                                                        ?>">
                                                    Para: <?php echo $novedad['categoria_objetivo']; ?>
                                                </span>
                                            </div>
                                            <div class="card-body">
                                                <p class="card-text"><?php echo $novedad['descripcion']; ?></p>
                                            </div>
                                            <div class="card-footer text-muted">
                                                <small>
                                                    📅 Válida hasta: <?php echo date('d/m/Y', strtotime($novedad['fecha_fin'])); ?>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info text-center">
                                <h5>No hay novedades disponibles</h5>
                                <p>No hay novedades activas para tu categoría en este momento.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>