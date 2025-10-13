<?php
session_start();
include("config/db.php");

// Incluir PHPMailer
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$error = "";
$success = "";

if (isset($_POST['register'])) {
    $nombre = trim($_POST['nombre']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $tipo = $_POST['tipo'];

    if (empty($nombre) || empty($email) || empty($password)) {
        $error = "Todos los campos son obligatorios";
    } else {
        $check_sql = "SELECT * FROM usuarios WHERE email='$email'";
        $check_result = $conn->query($check_sql);

        if ($check_result->num_rows > 0) {
            $error = "El email ya está registrado";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Generar token único para verificación
            $token = bin2hex(random_bytes(50));

            // MODIFICADO: Configuración diferente para clientes y dueños
            if ($tipo == 'dueno') {
                $estado = 'pendiente'; // Dueños requieren aprobación del admin
                $categoria_cliente = 'NULL'; // Dueños NO tienen categoría
                $email_verificado = 0; // Dueños también requieren verificación por email
            } else {
                $estado = 'activo'; // Clientes activos después de verificación
                $categoria_cliente = "'Inicial'"; // Clientes SI tienen categoría
                $email_verificado = 0; // Clientes requieren verificación por email
            }

            // MODIFICADO: Consulta adaptada para manejar NULL en categoría
            $sql = "INSERT INTO usuarios (nombre, email, password, rol, estado, categoria_cliente, token_verificacion, email_verificado) 
                    VALUES ('$nombre', '$email', '$hashed_password', '$tipo', '$estado', $categoria_cliente, '$token', $email_verificado)";

            if ($conn->query($sql) === TRUE) {
                // Enviar email de verificación con PHPMailer
                if (enviarEmailVerificacion($email, $nombre, $token, $tipo)) {
                    if ($tipo == 'dueno') {
                        $success = "✅ Registro exitoso. Te hemos enviado un email de verificación a <strong>$email</strong>. Una vez verificado, tu cuenta estará pendiente de aprobación del administrador.";
                    } else {
                        $success = "✅ Registro exitoso. Te hemos enviado un email de verificación a <strong>$email</strong>. Una vez verificado, podrás acceder a todas las promociones.";
                    }
                } else {
                    $success = "✅ Registro exitoso, pero no pudimos enviar el email de verificación. Por favor, contacta al administrador.";
                    // Mostrar enlace alternativo para desarrollo
                    $enlace_alternativo = "http://localhost/tpentornosgraficos/verificar_email.php?token=" . $token;
                    $success .= "<br><br><strong>Enlace alternativo:</strong> <a href='$enlace_alternativo'>$enlace_alternativo</a>";
                }
                $_POST = array();
            } else {
                $error = "Error en el registro: " . $conn->error;
            }
        }
    }
}

// MODIFICADO: Función para enviar email de verificación con PHPMailer (mejorada)
function enviarEmailVerificacion($email, $nombre, $token, $tipo)
{
    $mail = new PHPMailer(true);

    try {
        // Configuración del servidor - AJUSTA ESTOS DATOS SEGÚN TU CONFIGURACIÓN
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';  // O tu servidor SMTP
        $mail->SMTPAuth = true;
        $mail->Username = 'guille.petri47@gmail.com';  // Tu email
        $mail->Password = 'Jazminpetri123!';  // Password de aplicación de gmail
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Destinatarios
        $mail->setFrom('no-reply@shoppingrosario.com', 'Shopping Stella Rosario');
        $mail->addAddress($email, $nombre);

        // Contenido
        $mail->isHTML(true);
        $mail->Subject = 'Verifica tu cuenta - Shopping Rosario';

        $enlace_verificacion = "http://localhost/tpentornosgraficos/verificar_email.php?token=" . $token;

        // MODIFICADO: Contenido del email según el tipo de usuario
        if ($tipo == 'dueno') {
            $contenido_especifico = "
                <div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin: 15px 0;'>
                    <strong>🏪 Información para Dueños:</strong> Después de verificar tu email, tu cuenta estará pendiente de aprobación por parte del administrador. Te notificaremos cuando sea aprobada.
                </div>
            ";
        } else {
            $contenido_especifico = "
                <div style='background: #d1ecf1; padding: 15px; border-radius: 5px; margin: 15px 0;'>
                    <strong>👤 Información para Clientes:</strong> Después de verificar tu email, podrás acceder inmediatamente a todas las promociones disponibles para tu categoría Inicial.
                </div>
            ";
        }

        $mail->Body = "
        <!DOCTYPE html>
        <html lang='es'>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; background-color: #f4f4f4; padding: 20px; }
                .container { max-width: 600px; background: white; padding: 30px; border-radius: 10px; margin: 0 auto; }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { padding: 20px; }
                .button { background: #007bff; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 15px 0; }
                .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🛍️ Shopping Rosario</h1>
                    <p>Verificación de Cuenta</p>
                </div>
                <div class='content'>
                    <h2>¡Hola $nombre!</h2>
                    <p>Gracias por registrarte en <strong>Shopping Rosario</strong> como <strong>" . ($tipo == 'dueno' ? 'Dueño de Local' : 'Cliente') . "</strong>.</p>
                    
                    $contenido_especifico
                    
                    <p>Para activar tu cuenta, haz click en el siguiente botón:</p>
                    
                    <div style='text-align: center;'>
                        <a href='$enlace_verificacion' class='button'>✅ Verificar Mi Cuenta</a>
                    </div>
                    
                    <p>O copia y pega esta URL en tu navegador:</p>
                    <p style='background: #f8f9fa; padding: 10px; border-radius: 5px; word-break: break-all;'>
                        $enlace_verificacion
                    </p>
                    
                    <div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                        <strong>⚠️ Importante:</strong> Este enlace expirará en 24 horas. Si no verificas tu cuenta en ese tiempo, deberás solicitar un nuevo enlace.
                    </div>
                    
                    <p>Si no te registraste en Shopping Rosario, por favor ignora este email.</p>
                </div>
                <div class='footer'>
                    <p>&copy; 2024 Shopping Rosario. Todos los derechos reservados.</p>
                    <p>Este es un email automático, por favor no respondas a este mensaje.</p>
                </div>
            </div>
        </body>
        </html>
        ";

        // Versión alternativa en texto plano
        $tipo_texto = ($tipo == 'dueno') ? 'Dueño de Local' : 'Cliente';
        $mail->AltBody = "Hola $nombre,\n\nVerifica tu cuenta en Shopping Rosario (como $tipo_texto) usando este enlace: $enlace_verificacion\n\nSi no te registraste, ignora este email.";

        $mail->send();
        return true;
    } catch (Exception $e) {
        // Log del error
        error_log("Error PHPMailer: " . $mail->ErrorInfo);
        return false;
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Registro - Shopping Rosario</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">Registro de Usuario</h4>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger">
                                <?php echo $error; ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($success)): ?>
                            <div class="alert alert-success">
                                <?php echo $success; ?>
                                <div class="mt-3">
                                    <a href="index.php" class="btn btn-outline-primary">🏠 Volver al Inicio</a>
                                    <a href="login.php" class="btn btn-outline-secondary">🔐 Ir al Login</a>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if (empty($success)): ?>
                            <form method="POST" id="registerForm">
                                <div class="mb-3">
                                    <label for="nombre" class="form-label">Nombre Completo *</label>
                                    <input type="text" class="form-control" id="nombre" name="nombre" required
                                        value="<?php echo isset($_POST['nombre']) ? htmlspecialchars($_POST['nombre']) : ''; ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email *</label>
                                    <input type="email" class="form-control" id="email" name="email" required
                                        value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                                    <div class="form-text">Te enviaremos un email de verificación a esta dirección</div>
                                </div>
                                <div class="mb-3">
                                    <label for="password" class="form-label">Contraseña *</label>
                                    <input type="password" class="form-control" id="password" name="password" required minlength="6">
                                    <div class="form-text">Mínimo 6 caracteres</div>
                                </div>
                                <div class="mb-3">
                                    <label for="tipo" class="form-label">Tipo de Usuario *</label>
                                    <select class="form-control" id="tipo" name="tipo" required>
                                        <option value="">Seleccionar tipo</option>
                                        <option value="cliente" <?php echo (isset($_POST['tipo']) && $_POST['tipo'] == 'cliente') ? 'selected' : ''; ?>>👤 Cliente</option>
                                        <option value="dueno" <?php echo (isset($_POST['tipo']) && $_POST['tipo'] == 'dueno') ? 'selected' : ''; ?>>🏪 Dueño de Local</option>
                                    </select>
                                </div>
                                <button type="submit" name="register" class="btn btn-primary w-100">Registrarse</button>
                            </form>
                            <div class="text-center mt-3">
                                <a href="login.php">¿Ya tienes cuenta? Inicia sesión</a>
                                <br>
                                <a href="index.php" class="btn btn-outline-secondary btn-sm mt-2">🏠 Volver al Inicio</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>