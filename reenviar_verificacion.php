<?php

require_once "config/db.php";

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$pageMessage = "";

/* Construye enlace absoluto a verificar_email.php */
function buildVerificationLink(string $token): string
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $dir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\') . '/';
    return $scheme . '://' . $host . $dir . 'verificar_email.php?token=' . urlencode($token);
}

/* Envía email con PHPMailer (SMTP Gmail) */
function enviarEmailVerificacion(string $email, string $nombre, string $token): bool
{
    $enlace_verificacion = buildVerificationLink($token);

    $mail = new PHPMailer(true);
    try {
        // SMTP
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'joaquingarciaforestello@gmail.com';           // <-- TU Gmail real
        $mail->Password = 'fcyt bvju nlte smek';     // <-- Contraseña de aplicación
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // From/To
        $mail->setFrom('no-reply@shoppingrosario.com', 'Shopping Rosario');
        $mail->addAddress($email, $nombre);

        // Contenido
        $mail->isHTML(true);
        $mail->Subject = 'Nuevo enlace para verificar tu cuenta';
        $bodyLink = htmlspecialchars($enlace_verificacion, ENT_QUOTES, 'UTF-8');
        $safeName = htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8');
        $mail->Body = "
            <p>¡Hola {$safeName}!</p>
            <p>Te enviamos un nuevo enlace para verificar tu cuenta.</p>
            <p><a href='{$bodyLink}'>{$bodyLink}</a></p>
            <p>Si no solicitaste este correo, podés ignorarlo.</p>
        ";
        $mail->AltBody = "Hola {$nombre}!\n\nVerificá tu cuenta con este enlace:\n{$enlace_verificacion}";

        return $mail->send();
    } catch (Exception $e) {
        error_log("Error PHPMailer (reenviar): " . $mail->ErrorInfo);
        return false;
    }
}


if (!isset($_GET['email']) || !filter_var($_GET['email'], FILTER_VALIDATE_EMAIL)) {
    $pageMessage = "Email inválido o faltante.";
} else {
    $email = $_GET['email'];

    // Buscar usuario NO verificado
    $stmt = $conn->prepare("SELECT id, nombre, email FROM usuarios WHERE email = ? AND email_verificado = 0 LIMIT 1");
    if (!$stmt) {
        error_log("Prepare SELECT falló: " . $conn->error);
        $pageMessage = "Ocurrió un error. Intentá nuevamente más tarde.";
    } else {
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            // Respuesta neutra: no revelamos si existe o si ya estaba verificada
            $pageMessage = "Si la cuenta existe y no estaba verificada, te enviamos un nuevo correo.";
        } else {
            $usuario = $result->fetch_assoc();
            $stmt->close();

            // Generar token nuevo y guardarlo
            try {
                $nuevo_token = bin2hex(random_bytes(32)); // 64 chars
            } catch (Exception $e) {
                $nuevo_token = bin2hex(openssl_random_pseudo_bytes(32));
            }

            $upd = $conn->prepare("UPDATE usuarios SET token_verificacion = ? WHERE id = ?");
            if (!$upd) {
                error_log("Prepare UPDATE token falló: " . $conn->error);
                $pageMessage = "No se pudo generar el nuevo enlace de verificación. Probá más tarde.";
            } else {
                $upd->bind_param("si", $nuevo_token, $usuario['id']);
                if (!$upd->execute()) {
                    error_log("Error UPDATE token en reenviar: " . $conn->error);
                    $pageMessage = "No se pudo generar el nuevo enlace de verificación. Probá más tarde.";
                } else {
                    // Enviar el correo
                    $okEnvio = enviarEmailVerificacion($usuario['email'], $usuario['nombre'], $nuevo_token);
                    if ($okEnvio) {
                        $safeEmail = htmlspecialchars($usuario['email'], ENT_QUOTES, 'UTF-8');
                        $pageMessage = "✅ Te enviamos un nuevo email de verificación a <strong>{$safeEmail}</strong>.";
                    } else {
                        // Mostramos el enlace en pantalla si no se envia el mail
                        $fallback = htmlspecialchars(buildVerificationLink($nuevo_token), ENT_QUOTES, 'UTF-8');
                        $pageMessage = "No pudimos enviar el email automáticamente. Podés verificar desde este enlace:<br>
                                        <a class='btn btn-success mt-2' href='{$fallback}'>Verificar cuenta</a>";
                    }
                }
                $upd->close();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Reenviar verificación</title>
    <!-- bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #f7f7f7;
        }

        .card {
            border-radius: 16px;
        }
    </style>
</head>

<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-12 col-md-8 col-lg-6">
                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <h1 class="h4 mb-3">Reenviar verificación</h1>
                        <div class="alert alert-info" role="alert">
                            <?php echo $pageMessage; ?>
                        </div>
                        <a href="login.php" class="btn btn-outline-primary">Volver al inicio de sesión</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- bootstrap  -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>