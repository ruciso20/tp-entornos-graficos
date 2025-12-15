<?php
require_once "config/db.php";

$mensaje = "";

if (isset($_GET['token'])) {
    $token = $_GET['token'];

    // Buscar dueño de local con estado pendiente y token válido
    $stmt = $conn->prepare("
        SELECT id, nombre, rol, estado 
        FROM usuarios 
        WHERE token = ? 
        AND rol = 'dueno' 
        AND estado = 'pendiente'
        LIMIT 1
    ");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $mensaje = "❌ Token inválido, cuenta ya aprobada o usuario no encontrado.";
    } else {
        $usuario = $result->fetch_assoc();
        $stmt->close();

        // Aprobar la cuenta
        $upd = $conn->prepare("UPDATE usuarios SET estado = 'aprobado', token = NULL WHERE id = ?");
        $upd->bind_param("i", $usuario['id']);

        if ($upd->execute()) {
            $mensaje = "✅ La cuenta del dueño <strong>" . htmlspecialchars($usuario['nombre']) . "</strong> fue aprobada correctamente.";
        } else {
            error_log("Error UPDATE validation.php: " . $conn->error);
            $mensaje = "⚠️ Ocurrió un error al aprobar la cuenta. Intentá nuevamente.";
        }

        $upd->close();
    }
} else {
    $mensaje = "Token no proporcionado.";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aprobación de Cuenta - Shopping Rosario</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light text-center p-5">
    <main class="container">
        <div class="card mx-auto shadow-sm" style="max-width: 500px;">
            <div class="card-body">
                <h3 class="card-title mb-3">Aprobación de Cuenta</h3>
                <p class="lead"><?php echo $mensaje; ?></p>
                <div class="mt-3">
                    <a href="login.php" class="btn btn-primary">Ir al Login</a>
                </div>
            </div>
        </div>
</main>
</body>
</html>