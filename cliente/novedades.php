<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] != 'cliente') {
    header("Location: ../login.php");
    exit;
}

$categoria = $_SESSION['categoria'];
$nombre = $_SESSION['nombre'];

include("../config/db.php");

// Mostrar novedades que empiecen hoy o en el futuro
$query = "
    SELECT * FROM novedades 
    WHERE estado = 'activa'
    AND fecha_fin >= CURDATE()
    ORDER BY 
        CASE 
            WHEN categoria_objetivo = ? THEN 1
            ELSE 2
        END,
        fecha_inicio ASC
";

$stmt = $conn->prepare($query);
$stmt->bind_param("s", $categoria);
$stmt->execute();
$novedades = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Novedades - Cliente</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .novedad-exclusiva {
            border: 2px solid #ffc107;
            box-shadow: 0 4px 15px rgba(255, 193, 7, 0.3);
            background: linear-gradient(135deg, #fff9e6 0%, #ffffff 100%);
        }

        .badge-exclusiva {
            background: linear-gradient(45deg, #ffc107, #ff8c00);
            color: white;
            font-weight: bold;
        }

        .badge-proximamente {
            background: linear-gradient(45deg, #6c757d, #495057);
            color: white;
            font-weight: bold;
        }

        .card:hover {
            transform: translateY(-3px);
            transition: transform 0.2s ease-in-out;
        }

        .novedad-futura {
            opacity: 0.8;
            border-left: 4px solid #6c757d;
        }

        .user-category-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 0.7em;
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-dark bg-dark">
        <div class="container-fluid">
            <div class="navbar-brand">
                <a class="navbar-brand fw-bold" href="../index.php">🛍️
                    <span class="ms-1">Stella Shopping Rosario</span></a>
                <span class="navbar-text text-light">Novedades</span>
            </div>
            <div class="d-flex">
                <a href="../dashboard.php" class="btn btn-outline-light">Volver</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header text-dark">
                        <h4>Novedades del Shopping</h4>
                        <p class="mb-0">Mantente informado de las últimas novedades</p>
                    </div>
                    <div class="card-body">
                        <?php if ($novedades->num_rows > 0): ?>
                            <div class="row">
                                <?php while ($novedad = $novedades->fetch_assoc()):
                                    $es_exclusiva = $novedad['categoria_objetivo'] === $categoria;
                                    $es_futura = strtotime($novedad['fecha_inicio']) > time();
                                ?>
                                    <div class="col-md-6 mb-4">
                                        <div class="card h-100 <?php echo $es_exclusiva ? 'novedad-exclusiva' : ''; ?> <?php echo $es_futura ? 'novedad-futura' : ''; ?>">
                                            <div class="card-header d-flex justify-content-between align-items-center pt-4">
                                                <h5 class="mb-0">📢 <?php echo htmlspecialchars($novedad['titulo']); ?></h5>
                                                <div>
                                                    <?php if ($es_futura): ?>
                                                        <span class="badge badge-proximamente me-1">
                                                            🔜 Próximamente
                                                        </span>
                                                    <?php endif; ?>
                                                    <?php if ($es_exclusiva): ?>
                                                        <span class="badge badge-exclusiva me-1">
                                                            ⭐ Para ti
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="card-body">
                                                <p class="card-text"><?php echo htmlspecialchars($novedad['descripcion']); ?></p>

                                                <!-- Información de categoría de la novedad -->
                                                <div class="mt-3">
                                                    <span class="badge bg-<?php
                                                                            echo $novedad['categoria_objetivo'] == 'Premium' ? 'danger' : ($novedad['categoria_objetivo'] == 'Medium' ? 'warning' : 'primary');
                                                                            ?>">
                                                        🎯 Dirigido para categoria <?php echo $novedad['categoria_objetivo']; ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="card-footer text-muted">
                                                <small>
                                                    <?php if ($es_futura): ?>
                                                        🗓️ <strong>Inicia:</strong> <?php echo date('d/m/Y', strtotime($novedad['fecha_inicio'])); ?>
                                                    <?php else: ?>
                                                        📅 <strong>Activa desde:</strong> <?php echo date('d/m/Y', strtotime($novedad['fecha_inicio'])); ?>
                                                    <?php endif; ?>
                                                    <br>
                                                    📅 <strong>Válida hasta:</strong> <?php echo date('d/m/Y', strtotime($novedad['fecha_fin'])); ?>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info text-center">
                                <h5>No hay novedades disponibles</h5>
                                <p>No hay novedades activas o próximas en este momento.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- footer -->
    <?php include('../footer.php'); ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>