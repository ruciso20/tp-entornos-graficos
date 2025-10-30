<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] != 'cliente') {
    header("Location: ../login.php");
    exit;
}

include("../config/db.php");
$user_id = $_SESSION['user_id'];

// Obtener historial de promociones usadas
$historial_query = $conn->prepare("
    SELECT up.*, p.titulo, p.descripcion, l.nombre as local_nombre, 
           DATE(up.fecha_uso) as fecha, TIME(up.fecha_uso) as hora
    FROM uso_promociones up
    JOIN promociones p ON up.promocion_id = p.id
    JOIN locales l ON p.local_id = l.id
    WHERE up.cliente_id = ?
    ORDER BY up.fecha_uso DESC
");
$historial_query->bind_param("i", $user_id);
$historial_query->execute();
$historial = $historial_query->get_result();

// Contar estadísticas
$total_usadas = $conn->query("
    SELECT COUNT(*) as total FROM uso_promociones 
    WHERE cliente_id = $user_id AND estado = 'usada'
")->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Mi Historial - Cliente</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>
    <nav class="navbar navbar-dark bg-dark">
        <div class="container-fluid">
            <div class="navbar-brand">
                <a class="navbar-brand fw-bold" href="../index.php">🛍️
                    <span class="ms-1">Stella Shopping Rosario</span></a>
                <span class="navbar-text text-light">Historial</span>
            </div>
            <div class="d-flex">
                <a href="../dashboard.php" class="btn btn-outline-light">Volver</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row mb-4">
            <div class="col-md-8">
                <h2>Historial de Promociones</h2>
                <p class="text-muted">Revisa todas las promociones que has utilizado</p>
            </div>
        </div>

        <div class="card">
            <div class="card-header text-dark">
                <h5 class="mb-0">Historial de Uso</h5>
            </div>
            <div class="card-body">
                <?php if ($historial->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Fecha y Hora</th>
                                    <th>Promoción</th>
                                    <th>Local</th>
                                    <th>Estado</th>
                                    <th>Código</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($uso = $historial->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo date('d/m/Y', strtotime($uso['fecha'])); ?></strong><br>
                                            <small class="text-muted"><?php echo $uso['hora']; ?></small>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($uso['titulo']); ?></strong><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($uso['descripcion']); ?></small>
                                        </td>
                                        <td><?php echo htmlspecialchars($uso['local_nombre']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php
                                                                    echo $uso['estado'] == 'usada' ? 'success' : ($uso['estado'] == 'pendiente' ? 'warning' : 'danger');
                                                                    ?>">
                                                <?php echo ucfirst($uso['estado']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <code>PROMO-<?php echo $uso['promocion_id']; ?></code>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-history fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Aún no has utilizado promociones</h5>
                        <p class="text-muted">¡Descubre las promociones disponibles y comienza a disfrutar de los beneficios!</p>
                        <a href="promociones.php" class="btn btn-primary">Ver Promociones Disponibles</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <!-- footer -->
    <?php include('../footer.php'); ?>
</body>

</html>