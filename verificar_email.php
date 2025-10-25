<?php
session_start();
include("config/db.php");

$mensaje = "";

if (isset($_GET['token'])) {
  if (!isset($_GET['token']) || trim($_GET['token']) === '') {
    die("Token inválido o faltante.");
  }
  $token = $_GET['token'];

  $stmt = $conn->prepare("SELECT id, email_verificado FROM usuarios WHERE token_verificacion = ? LIMIT 1");
  $stmt->bind_param("s", $token);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows === 0) {
      // Token inexistente o ya usado
      die("Enlace de verificación inválido o expirado.");
  }

  $usuario = $result->fetch_assoc();

  if ((int)$usuario['email_verificado'] === 1) {
      // Ya estaba verificado
      echo "Tu email ya está verificado. Ya podés iniciar sesión.";
      exit;
  }
}
  if ($result->num_rows > 0) {
    $usuario = $result->fetch_assoc();

    // Actualizar usuario como verificado
    $update_stmt = $conn->prepare("UPDATE usuarios SET email_verificado = 1, token_verificacion = NULL WHERE id = ?");
    $update_stmt->bind_param("i", $usuario['id']);

    if ($update_stmt->execute()) {
        echo "✅ ¡Listo! Tu email fue verificado. Ya podés iniciar sesión.";
    } else {
        error_log("Error UPDATE verificar_email: " . $conn->error);
        echo "Ocurrió un error al verificar tu email. Intentá de nuevo más tarde.";
    }
} else {
  $mensaje = "notoken";
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <title>Verificación de Email - Shopping Rosario</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
  <div class="container mt-5">
    <div class="row justify-content-center">
      <div class="col-md-6">
        <div class="card shadow">
          <div class="card-header bg-primary text-white text-center">
            <h4 class="mb-0">Verificación de Email</h4>
          </div>
          <div class="card-body text-center">
            <?php if ($mensaje == "success"): ?>
              <div class="alert alert-success">
                <h5>✅ ¡Email verificado exitosamente!</h5>
                <p>Tu cuenta ha sido activada correctamente.</p>
                <?php if ($tipo_usuario == 'dueno'): ?>
                  <p>Tu cuenta está <strong>pendiente de aprobación del administrador</strong>. Te notificaremos cuando sea activada.</p>
                  <a href="index.php" class="btn btn-primary">Ir al Inicio</a>
                <?php else: ?>
                  <p>Ya puedes iniciar sesión y disfrutar de todas las promociones.</p>
                  <a href="login.php" class="btn btn-success">Iniciar Sesión</a>
                <?php endif; ?>
              </div>
            <?php elseif ($mensaje == "error"): ?>
              <div class="alert alert-danger">
                <h5>❌ Error en la verificación</h5>
                <p>Ocurrió un error al verificar tu email. Por favor, contacta al administrador.</p>
                <a href="index.php" class="btn btn-outline-primary">Ir al Inicio</a>
              </div>
            <?php elseif ($mensaje == "invalid"): ?>
              <div class="alert alert-warning">
                <h5>⚠️ Enlace inválido</h5>
                <p>El enlace de verificación no es válido o ya fue utilizado.</p>
                <a href="index.php" class="btn btn-outline-primary">Ir al Inicio</a>
              </div>
            <?php else: ?>
              <div class="alert alert-info">
                <h5>🔍 No se encontró token</h5>
                <p>No se proporcionó un token de verificación válido.</p>
                <a href="index.php" class="btn btn-outline-primary">Ir al Inicio</a>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</body>

</html>