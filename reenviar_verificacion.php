<?php
session_start();
include("config/db.php");
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;

if (isset($_GET['email'])) {
  $email = $_GET['email'];

  // Buscar usuario
  $sql = "SELECT * FROM usuarios WHERE email = '$email' AND email_verificado = 0";
  $result = $conn->query($sql);

  if ($result->num_rows > 0) {
    $usuario = $result->fetch_assoc();

    // Reenviar email de verificación
    if (enviarEmailVerificacion($usuario['email'], $usuario['nombre'], $usuario['token_verificacion'])) {
      $mensaje = "success";
    } else {
      $mensaje = "error";
    }
  } else {
    $mensaje = "notfound";
  }
} else {
  $mensaje = "noemail";
}

// Función enviarEmailVerificacion (la misma que en register.php)
function enviarEmailVerificacion($email, $nombre, $token)
{
  $mail = new PHPMailer(true);

  try {
    // Misma configuración que en register.php
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'tucorreo@gmail.com';
    $mail->Password = 'tu_password_de_aplicacion';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    $mail->setFrom('no-reply@shoppingrosario.com', 'Shopping Rosario');
    $mail->addAddress($email, $nombre);

    $mail->isHTML(true);
    $mail->Subject = 'Verifica tu cuenta - Shopping Rosario';

    $enlace_verificacion = "http://localhost/tpentornosgraficos/verificar_email.php?token=" . $token;

    $mail->Body = "..."; // Mismo contenido que en register.php

    $mail->send();
    return true;
  } catch (Exception $e) {
    error_log("Error PHPMailer: " . $mail->ErrorInfo);
    return false;
  }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <title>Reenviar Verificación - Shopping Rosario</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
  <div class="container mt-5">
    <div class="row justify-content-center">
      <div class="col-md-6">
        <div class="card shadow">
          <div class="card-header bg-warning text-dark text-center">
            <h4 class="mb-0">Reenviar Email de Verificación</h4>
          </div>
          <div class="card-body text-center">
            <?php if ($mensaje == "success"): ?>
              <div class="alert alert-success">
                <h5>✅ Email reenviado</h5>
                <p>Te hemos enviado un nuevo email de verificación a <strong><?php echo $email; ?></strong></p>
                <a href="login.php" class="btn btn-success">Volver al Login</a>
              </div>
            <?php elseif ($mensaje == "error"): ?>
              <div class="alert alert-danger">
                <h5>❌ Error al reenviar</h5>
                <p>No pudimos enviar el email de verificación. Contacta al administrador.</p>
                <a href="login.php" class="btn btn-outline-primary">Volver al Login</a>
              </div>
            <?php else: ?>
              <div class="alert alert-info">
                <h5>⚠️ No se pudo procesar</h5>
                <p>No se encontró el usuario o ya está verificado.</p>
                <a href="login.php" class="btn btn-outline-primary">Volver al Login</a>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</body>

</html>