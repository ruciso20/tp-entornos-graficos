<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] != 'cliente') {
    header("Location: ../login.php");
    exit;
}

include("../config/db.php");
$user_id = $_SESSION['user_id'];
$mensaje = "";

// Obtener datos actuales del cliente
$cliente_query = $conn->prepare("SELECT * FROM usuarios WHERE id = ?");
$cliente_query->bind_param("i", $user_id);
$cliente_query->execute();
$cliente = $cliente_query->get_result()->fetch_assoc();

// Procesar actualización del perfil
if ($_POST && isset($_POST['actualizar_perfil'])) {
    $nombre = trim($_POST['nombre']);
    $telefono = trim($_POST['telefono']);
    $direccion = trim($_POST['direccion']);
    $preferencias = trim($_POST['preferencias']);

    $update_query = $conn->prepare("
        UPDATE usuarios 
        SET nombre = ?, telefono = ?, direccion = ?, preferencias = ? 
        WHERE id = ?
    ");
    $update_query->bind_param("ssssi", $nombre, $telefono, $direccion, $preferencias, $user_id);

    if ($update_query->execute()) {
        $_SESSION['nombre'] = $nombre;
        $mensaje = "✅ Perfil actualizado correctamente";
        // Recargar datos
        $cliente_query->execute();
        $cliente = $cliente_query->get_result()->fetch_assoc();
    } else {
        $mensaje = "❌ Error al actualizar el perfil";
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Mi Perfil - Cliente</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>
    <nav class="navbar navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="../dashboard.php">🛍️ Cliente - Mi Perfil</a>
            <a href="../dashboard.php" class="btn btn-outline-light">Volver</a>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header text-dark">
                        <h4 class="mb-0">Mi Perfil</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($mensaje): ?>
                            <div class="alert alert-info"><?php echo $mensaje; ?></div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Nombre Completo *</label>
                                        <input type="text" class="form-control" name="nombre"
                                            value="<?php echo htmlspecialchars($cliente['nombre']); ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Email</label>
                                        <input type="email" class="form-control"
                                            value="<?php echo htmlspecialchars($cliente['email']); ?>" readonly>
                                        <small class="text-muted">El email no se puede modificar</small>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Teléfono</label>
                                        <input type="tel" class="form-control" name="telefono"
                                            value="<?php echo htmlspecialchars($cliente['telefono'] ?? ''); ?>"
                                            placeholder="+54 341 123-4567">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Dirección</label>
                                        <textarea class="form-control" name="direccion" rows="3"
                                            placeholder="Calle, número, ciudad"><?php echo htmlspecialchars($cliente['direccion'] ?? ''); ?></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Preferencias de Compras</label>
                                        <textarea class="form-control" name="preferencias" rows="3"
                                            placeholder="Ej: Ropa, tecnología, comida, etc."><?php echo htmlspecialchars($cliente['preferencias'] ?? ''); ?></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Categoría Actual</label>
                                        <input type="text" class="form-control"
                                            value="<?php echo ucfirst($cliente['categoria_cliente']); ?>" readonly>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" name="actualizar_perfil" class="btn btn-success w-100">
                                Guardar Cambios
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>