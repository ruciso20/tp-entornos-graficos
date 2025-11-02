<?php
$baseA = __DIR__ . '/PHPMailer/src/';            
$baseB = dirname(__DIR__) . '/PHPMailer/src/';   

$phpmailerBase = is_file($baseA . 'PHPMailer.php') ? $baseA :
                 (is_file($baseB . 'PHPMailer.php') ? $baseB : null);

if ($phpmailerBase === null) {
  die('No se encontró PHPMailer. Coloca la carpeta "PHPMailer" en "secciones/" o en la raíz del proyecto.');
}

require $phpmailerBase . 'Exception.php';
require $phpmailerBase . 'PHPMailer.php';
require $phpmailerBase . 'SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (function_exists('session_status') && session_status() === PHP_SESSION_NONE) {
}





$success_msg = null;
$error_msg   = null;

// Procesar envío
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['contacto'])) {
  // Honeypot anti-spam
  if (!empty($_POST['website'])) {
    $error_msg = 'Detectamos actividad sospechosa.';
  } else {
    // Sanitizar/validar
    $nombre  = trim(filter_input(INPUT_POST, 'nombre', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
    $email   = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL) ?? '');
    $asunto  = trim(filter_input(INPUT_POST, 'asunto', FILTER_SANITIZE_SPECIAL_CHARS) ?? 'Consulta desde el sitio');
    $mensaje = trim(filter_input(INPUT_POST, 'mensaje', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');

    if ($nombre === '' || $email === '' || $mensaje === '') {
      $error_msg = 'Por favor, completa nombre, email y mensaje.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $error_msg = 'El email no es válido.';
    } else {
      try {
        $mail = new PHPMailer(true);
        // Configuración SMTP
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';     // p.ej. smtp.gmail.com
        $mail->SMTPAuth   = true;
        $mail->Username   = 'joaquingarciaforestello@gmail.com';  // usuario/alias
        $mail->Password   = 'fcyt bvju nlte smek'; // contraseña o App Password
        $mail->Port       = 587;                  // 587 TLS / 465 SSL
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;

        // Remitente / Destinatario
        $mail->setFrom('contacto@shoppingrosario.com', 'Web Contacto');
        $mail->addAddress('joaquingarciaforestello@gmail.com','Contacto');
        $mail->addReplyTo($email, $nombre);

        // Contenido
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = $asunto !== '' ? $asunto : 'Consulta desde el sitio';

        $body  = '<h3>Nuevo mensaje desde el formulario</h3>';
        $body .= '<p><strong>Nombre:</strong> ' . nl2br(htmlspecialchars($nombre)) . '</p>';
        $body .= '<p><strong>Email:</strong> ' . nl2br(htmlspecialchars($email)) . '</p>';
        $body .= '<p><strong>Mensaje:</strong><br>' . nl2br(htmlspecialchars($mensaje)) . '</p>';
        $body .= '<hr><small>Enviado el ' . date('d/m/Y H:i') . '</small>';

        $mail->Body    = $body;
        $mail->AltBody = "Nuevo mensaje desde el formulario\n"
                       . "Nombre: $nombre\n"
                       . "Email: $email\n\n"
                       . "Mensaje:\n$mensaje\n";

        if ($mail->send()) {
          $success_msg = '¡Tu mensaje fue enviado con éxito! Te responderemos a la brevedad.';
          $_POST = []; // limpia valores para no repoblar
        } else {
          $error_msg = 'No se pudo enviar el mensaje.';
        }
      } catch (Exception $e) {
        $error_msg = 'Error al enviar: ' . $e->getMessage();
      }
    }
  }
}
?>
<!-- seccion de Contacto-->
<section class="py-5 bg-light" id="contacto">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-md-8 col-lg-6">

        <?php if ($success_msg): ?>
          <div class="alert alert-success" role="alert"><?php echo htmlspecialchars($success_msg); ?></div>
        <?php elseif ($error_msg): ?>
          <div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($error_msg); ?></div>
        <?php endif; ?>

        <div class="card contact-form shadow-sm">
          <div class="card-body p-4">
            <form method="post" action="#contacto" novalidate>
              <div class="row text-center mb-5">
                <div class="col">
                  <h2 class="fw-bold">📞 Contáctanos</h2>
                  <p class="text-muted fs-5">¿Tienes preguntas? Estamos aquí para ayudarte</p>
                </div>
              </div>

              <input type="text" name="website" autocomplete="off"
                     style="position:absolute; left:-5000px; top:-5000px;"
                     tabindex="-1" aria-hidden="true">

              <div class="row g-3">
                <div class="col-md-6">
                  <label for="nombre" class="form-label">Nombre</label>
                  <input
                    type="text"
                    class="form-control"
                    id="nombre"
                    name="nombre"
                    required
                    value="<?php echo htmlspecialchars($_POST['nombre'] ?? ''); ?>">
                </div>
                <div class="col-md-6">
                  <label for="email" class="form-label">Email</label>
                  <input
                    type="email"
                    class="form-control"
                    id="email"
                    name="email"
                    required
                    value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>
              </div>

              <div class="row g-3 mt-1">
                <div class="col-12">
                  <label for="asunto" class="form-label">Asunto</label>
                  <input
                    type="text"
                    class="form-control"
                    id="asunto"
                    name="asunto"
                    value="<?php echo htmlspecialchars($_POST['asunto'] ?? ''); ?>">
                </div>
              </div>

              <div class="mb-3 mt-3">
                <label for="mensaje" class="form-label">Mensaje</label>
                <textarea
                  class="form-control"
                  id="mensaje"
                  name="mensaje"
                  rows="5"
                  required><?php echo htmlspecialchars($_POST['mensaje'] ?? ''); ?></textarea>
              </div>

              <button type="submit" class="btn btn-primary btn-lg w-100" name="contacto">
                <i class="fas fa-paper-plane me-2"></i> Enviar Mensaje
              </button>
            </form>
          </div>
        </div>

      </div>
    </div>
  </div>
</section>
