<?php
require_once "config/db.php";

$mensaje = '';
$estado = ''; // success, error, info los 3 estados posibles

if (isset($_GET['token']) && !empty($_GET['token'])) {
    $token = $_GET['token'];


    $stmt = $conn->prepare("SELECT id, email_verificado FROM usuarios WHERE token_verificacion = ? LIMIT 1");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $mensaje = "Enlace de verificación inválido o expirado.";
        $estado = "error";
    } else {
        $usuario = $result->fetch_assoc();

        if ((int)$usuario['email_verificado'] === 1) {
            $mensaje = "Tu email ya fue verificado. Ya podés iniciar sesión.";
            $estado = "info";
        } else {
            $update = $conn->prepare("UPDATE usuarios SET email_verificado = 1, token_verificacion = NULL WHERE id = ?");
            $update->bind_param("i", $usuario['id']);

            if ($update->execute()) {
                $mensaje = "¡Listo! Tu email fue verificado. Ya podés iniciar sesión.";
                $estado = "success";
            } else {
                $mensaje = "Ocurrió un error al verificar tu email. Intentá de nuevo.";
                $estado = "error";
            }
        }
    }
} else {
    $mensaje = "No se proporcionó un token de verificación válido.";
    $estado = "error";
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificación de Email</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
    <nav class="navbar navbar-dark bg-dark">
        <div class="container-fluid">
            <div class="navbar-brand">
                <a class="navbar-brand fw-bold" href="index.php">🛍️
                    <span class="ms-1">Stella Shopping Rosario</span></a>
                <span class="navbar-text text-light">Verificacion Correo</span>
            </div>
        </div>
    </nav>
    <main class="container py-5 text-center">
        <?php if ($estado === 'success'): ?>
            <div class="alert alert-success" role="alert">
                ✅ <?php echo htmlspecialchars($mensaje); ?>
            </div>
        <?php elseif ($estado === 'info'): ?>
            <div class="alert alert-info" role="alert">
                ℹ️ <?php echo htmlspecialchars($mensaje); ?>
            </div>
        <?php else: ?>
            <div class="alert alert-danger" role="alert">
                ❌ <?php echo htmlspecialchars($mensaje); ?>
            </div>
        <?php endif; ?>

        <a href="login.php" class="btn btn-primary mt-3">Ir al inicio de sesión</a>
    </main>
    <?php include('footer.php'); ?>
</body>

</html>