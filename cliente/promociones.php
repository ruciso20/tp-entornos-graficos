<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] != 'cliente') {
    header("Location: ../login.php");
    exit;
}

include("../config/db.php");

// Obtener promociones disponibles para el cliente según su categoría
$categoria_cliente = $_SESSION['categoria'];
$usuario_id = $_SESSION['user_id'];

// CORREGIDO: Usar los nombres correctos de las columnas según tu estructura
$promociones_query = $conn->prepare("
    SELECT p.*, l.nombre as local_nombre, l.codigo_local
    FROM promociones p 
    JOIN locales l ON p.local_id = l.id 
    WHERE p.estado = 'aprobada' 
    AND p.fecha_fin >= CURDATE()
    AND p.fecha_inicio <= CURDATE()
    AND (
        p.categoria_minima = ? 
        OR p.categoria_minima = 'inicial'
        OR (? = 'premium' AND p.categoria_minima IN ('inicial', 'medium', 'premium'))
        OR (? = 'medium' AND p.categoria_minima IN ('inicial', 'medium'))
    )
    ORDER BY p.fecha_inicio DESC
");

$promociones_query->bind_param("sss", $categoria_cliente, $categoria_cliente, $categoria_cliente);
$promociones_query->execute();
$promociones = $promociones_query->get_result();
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Promociones - Cliente</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .promo-card {
            transition: transform 0.2s;
            border: none;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .promo-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
        }

        .exclusiva {
            border-left: 4px solid #ffc107 !important;
        }

        .categoria-badge {
            font-size: 0.8em;
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="../dashboard.php">🛍️ Cliente - Promociones</a>
            <div>
                <a href="../dashboard.php" class="btn btn-outline-light">← Volver</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h2>🤑 Promociones Disponibles</h2>
        <p class="text-muted">Estas son las promociones a las que tienes acceso según tu categoría <strong><?php echo ucfirst($categoria_cliente); ?></strong></p>

        <div class="row">
            <?php if ($promociones->num_rows > 0): ?>
                <?php while ($promo = $promociones->fetch_assoc()):
                    $es_exclusiva = $promo['categoria_minima'] == $categoria_cliente && $categoria_cliente != 'inicial';
                    $dias_array = explode(",", $promo['dias_validos']);
                    $dias_semana = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo'];
                    $dias_display = array_map(function ($dia) use ($dias_semana) {
                        return isset($dias_semana[$dia]) ? substr($dias_semana[$dia], 0, 3) : $dia;
                    }, $dias_array);
                ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card h-100 promo-card <?php echo $es_exclusiva ? 'exclusiva' : ''; ?>">
                            <div class="card-body d-flex flex-column">
                                <?php if ($es_exclusiva): ?>
                                    <span class="badge bg-warning mb-2">⭐ Exclusiva</span>
                                <?php endif; ?>

                                <h5 class="card-title"><?php echo htmlspecialchars($promo['titulo']); ?></h5>
                                <p class="card-text flex-grow-1">
                                    <small class="text-muted"><?php echo htmlspecialchars($promo['descripcion']); ?></small>
                                </p>

                                <div class="mt-auto">
                                    <p class="mb-1">
                                        <strong>🏪 Local:</strong> <?php echo htmlspecialchars($promo['local_nombre']); ?>
                                    </p>
                                    <p class="mb-1">
                                        <strong>📅 Válida hasta:</strong> <?php echo date('d/m/Y', strtotime($promo['fecha_fin'])); ?>
                                    </p>
                                    <p class="mb-2">
                                        <strong>📆 Días:</strong>
                                        <span class="badge bg-light text-dark"><?php echo implode(", ", $dias_display); ?></span>
                                    </p>

                                    <div class="mb-3">
                                        <span class="badge categoria-badge bg-<?php
                                                                                echo $promo['categoria_minima'] == 'premium' ? 'danger' : ($promo['categoria_minima'] == 'medium' ? 'warning' : 'info');
                                                                                ?>">
                                            Para: <?php echo ucfirst($promo['categoria_minima']); ?>
                                        </span>
                                        <?php if ($promo['categoria_minima'] == $categoria_cliente): ?>
                                            <span class="badge categoria-badge bg-success">✅ Disponible</span>
                                        <?php endif; ?>
                                    </div>

                                    <button class="btn btn-primary w-100" onclick="usarPromocion(<?php echo $promo['id']; ?>, '<?php echo htmlspecialchars($promo['local_nombre']); ?>')">
                                        🎯 Usar Promoción
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="alert alert-info text-center py-5">
                        <h4>😔 No hay promociones disponibles</h4>
                        <p class="mb-0">Vuelve más tarde para descubrir nuevas ofertas exclusivas.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function usarPromocion(promoId, localNombre) {
            if (confirm(`¿Quieres usar esta promoción en ${localNombre}?`)) {
                // Redirigir a la página de uso de promoción
                window.location.href = `usar_promocion.php?promo_id=${promoId}`;
            }
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>