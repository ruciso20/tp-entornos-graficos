<?php
session_start();
include("config/db.php");
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Procesar solicitud de recuperación de contraseña
if (isset($_POST['recuperar'])) {
    $email_recuperar = trim($_POST['email_recuperar']);

    if (empty($email_recuperar)) {
        $error_recuperar = "Por favor, ingresa tu email";
    } else {
        // Verificar si el email existe
        $stmt = $conn->prepare("SELECT id, nombre FROM usuarios WHERE email = ?");
        $stmt->bind_param("s", $email_recuperar);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            $token = bin2hex(random_bytes(50));
            $expiracion = date('Y-m-d H:i:s', strtotime('+1 hour'));

            // Guardar token en la base de datos
            $stmt = $conn->prepare("UPDATE usuarios SET token_recuperacion = ?, token_expiracion = ? WHERE email = ?");
            $stmt->bind_param("sss", $token, $expiracion, $email_recuperar);

            if ($stmt->execute()) {
                // Enviar email con PHPMailer
                $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                $host = $_SERVER['HTTP_HOST'];
                $projectRoot = rtrim(str_replace(basename($_SERVER['SCRIPT_NAME']), '', $_SERVER['SCRIPT_NAME']), '/\\');
                $enlace_recuperacion = $scheme . '://' . $host . rtrim($projectRoot, '/\\') . '/reset_password.php?token=' . $token;

                $mail = new PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = 'joaquingarciaforestello@gmail.com';
                    $mail->Password = 'fcyt bvju nlte smek';
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port = 587;

                    $mail->setFrom('no-reply@shoppingrosario.com', 'Stella Shopping Rosario');
                    $mail->addAddress($email_recuperar, $user['nombre']);

                    $mail->isHTML(true);
                    $mail->Subject = 'Recuperacion de usuario - Stella Shopping Rosario';

                    $mail->Body = "
                        <p>¡Hola {$user['nombre']}!</p>
                        <p>Recibimos una solicitud para restablecer tu contraseña en <strong>Shopping Rosario</strong>.</p>
                        <p>Para crear una nueva contraseña, hacé click aquí:</p>
                        <p><a href='$enlace_recuperacion'>$enlace_recuperacion</a></p>
                        <p><strong>Este enlace expirará en 1 hora.</strong></p>
                        <p>Si no solicitaste este cambio, ignorá este email.</p>
                    ";
                    $mail->AltBody = "Hola {$user['nombre']}!\n\nRestablecé tu contraseña con este enlace:\n$enlace_recuperacion\n\nEste enlace expira en 1 hora.";

                    if ($mail->send()) {
                        $success_recuperar = "✅ Te enviamos un email a <strong>$email_recuperar</strong> con instrucciones para recuperar tu contraseña. Revisá tu bandeja de entrada.";
                    } else {
                        $error_recuperar = "No pudimos enviar el email automáticamente. Contactá al administrador.";
                    }
                } catch (Exception $e) {
                    error_log("Error PHPMailer (recuperacion): " . $mail->ErrorInfo);
                    $error_recuperar = "Error al enviar el email de recuperación.";
                }
            } else {
                $error_recuperar = "Error al procesar la solicitud.";
            }
        } else {
            $error_recuperar = "No existe una cuenta con ese email.";
        }
    }
}

// Procesar login normal
if (isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM usuarios WHERE email = ? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();

        if (password_verify($password, $row['password'])) {
            // verificamos si el email esta verificado
            if (!$row['email_verificado']) {
                $error = "Tu email no ha sido verificado. Revisa tu bandeja de entrada y haz click en el enlace de verificación.";
                $email_no_verificado = true;

                // construir URL absoluta 
                $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                $host = $_SERVER['HTTP_HOST'];
                $dir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\') . '/';
                $reenviar_url = $scheme . '://' . $host . $dir . 'reenviar_verificacion.php?email=' . urlencode($email);

                $error .= "<br><br><a href='$reenviar_url' class='btn btn-warning btn-sm'>📧 Reenviar Email de Verificación</a>";
            }
            // verificamos el estado de la cuenta
            elseif ($row['estado'] == 'aprobado' || $row['rol'] == 'cliente') {
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['nombre'] = $row['nombre'];
                $_SESSION['rol'] = $row['rol'];
                $_SESSION['email_verificado'] = true;

                // solo guardamos la categoria si es cliente
                if ($row['rol'] == 'cliente') {
                    $_SESSION['categoria'] = $row['categoria_cliente'] ?: 'Inicial';
                } else {
                    $_SESSION['categoria'] = null;
                }

                header("Location: dashboard.php");
                exit;
            } elseif ($row['estado'] == 'pendiente') {
                $error = "Tu cuenta está pendiente de aprobación. Te notificaremos por email cuando sea activada.";
                $cuenta_pendiente = true;
            } elseif ($row['estado'] == 'rechazado') {
                $error = "Tu cuenta fue rechazada. Puedes contactar al administrador o crear una nueva solicitud.";
                $cuenta_rechazada = true;
            }
        } else {
            $error = "El correo o contraseña es incorrecto";
        }
    } else {
        $error = "El correo o contraseña es incorrecto";
    }
}

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Stella Shopping Rosario</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        html,
        body {
            height: 100%;
        }

        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .container {
            flex: 1;
        }

        /* Aseguramos que el footer se quede abajo */
        footer {
            margin-top: auto;
        }

        /* Espacio adicional para el contenido */
        .main-content {
            min-height: 70vh;
            display: flex;
            align-items: center;
        }

        /* Estilos para el campo de contraseña con ojo */
        .password-container {
            position: relative;
        }

        .password-container input {
            padding-right: 40px;
        }

        .toggle-password {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #6c757d;
            cursor: pointer;
            z-index: 10;
            padding: 5px;
        }

        .toggle-password:hover {
            color: #495057;
        }

        /* Ocultar controles nativos del navegador */
        input[type="password"]::-webkit-credentials-auto-fill-button,
        input[type="password"]::-webkit-caps-lock-indicator {
            display: none !important;
        }
    </style>
</head>

<body class="bg-light">
    <nav class="navbar navbar-dark bg-dark">
        <div class="container-fluid">
            <div class="navbar-brand">
                <a class="navbar-brand fw-bold" href="index.php">🛍️
                    <span class="ms-1">Stella Shopping Rosario</span></a>
                <span class="navbar-text text-light">Inicio de Sesión</span>
            </div>
        </div>
    </nav>
    <main class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">Iniciar Sesión</h4>
                    </div>
                    <div class="card-body">
                        <?php if (isset($error)): ?>
                            <div class="alert alert-<?php
                                                    echo isset($email_no_verificado) ? 'warning' : (isset($cuenta_pendiente) ? 'warning' : (isset($cuenta_rechazada) ? 'danger' : 'danger'));
                                                    ?>">
                                <?php echo $error; ?>
                                <div class="mt-3">
                                    <?php if (isset($email_no_verificado)): ?>
                                        <!-- El botón de reenviar ya está incluido en el mensaje de error -->
                                    <?php elseif (isset($cuenta_rechazada)): ?>
                                        <a href="register.php" class="btn btn-outline-warning">📝 Nueva Solicitud</a>
                                    <?php elseif (isset($cuenta_pendiente)): ?>
                                        <a href="register.php" class="btn btn-outline-secondary">📝 Crear otra cuenta</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Mostrar mensaje de recuperación exitosa -->
                        <?php if (isset($success_recuperar)): ?>
                            <div class="alert alert-success">
                                <?php echo $success_recuperar; ?>
                                <div class="mt-3">
                                    <a href="login.php" class="btn btn-primary">🔐 Volver al Login</a>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Mostrar formulario de recuperación si se activó -->
                        <?php if (isset($_GET['recuperar']) || isset($error_recuperar)): ?>
                            <div class="recuperacion-section">
                                <h5 class="mb-3">Recuperar Contraseña</h5>
                                <?php if (isset($error_recuperar)): ?>
                                    <div class="alert alert-danger"><?php echo $error_recuperar; ?></div>
                                <?php endif; ?>

                                <form method="POST" aria-label="Formulario de recuperación de contraseña">
                                    <div class="mb-3">
                                        <label for="email_recuperar" class="form-label">Ingresa tu email</label>
                                        <input type="email" class="form-control" id="email_recuperar" name="email_recuperar" required autocomplete="email"
                                            value="<?php echo isset($_POST['email_recuperar']) ? htmlspecialchars($_POST['email_recuperar']) : ''; ?>"
                                            placeholder="ejemplo@correo.com">
                                        <div class="form-text">Te enviaremos un email con instrucciones para recuperar tu contraseña.</div>
                                    </div>

                                    <div class="d-grid gap-2">
                                        <button type="submit" name="recuperar" class="btn btn-primary">
                                            Enviar Email de Recuperación
                                        </button>
                                        <a href="login.php" class="btn btn-outline-secondary">
                                            Volver al Login
                                        </a>
                                    </div>
                                </form>
                            </div>
                        <?php else: ?>
                            <!-- Formulario de login normal -->
                            <form method="POST" aria-label="Formulario de inicio de sesión">
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" required
                                        value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                                        placeholder="Ingresa tu email">
                                </div>

                                <div class="mb-3">
                                    <label for="password" class="form-label">Contraseña</label>
                                    <div class="password-container">
                                        <input type="password" class="form-control" id="password" name="password" required
                                            placeholder="Ingresa tu contraseña">
                                        <button type="button" class="toggle-password" aria-label="Mostrar u ocultar la contraseña" onclick="togglePassword('password')">
                                            <i class="fas fa-eye" aria-hidden="true"></i>
                                        </button>
                                    </div>
                                </div>

                                <button type="submit" name="login" class="btn btn-primary w-100">
                                    <i class="fas fa-sign-in-alt me-2" aria-hidden="true"></i>Iniciar Sesión
                                </button>

                                <div class="mb-3 text-center mt-3">
                                    <a href="login.php?recuperar=1" class="text-decoration-none">
                                        ¿Olvidaste tu contraseña?
                                    </a>
                                </div>
                            </form>

                            <div class="text-center mt-3">
                                <a href="register.php" class="d-block mb-2">🔐 ¿No tienes cuenta? Regístrate</a>
                                <a href="index.php" class="btn btn-outline-secondary btn-sm">🏠 Volver al Inicio</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- footer -->
    <?php include('footer.php'); ?>

    <script>
        // Función para mostrar/ocultar contraseña
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const button = field.parentNode.querySelector('.toggle-password');
            const icon = button.querySelector('i');

            if (field.type === 'password') {
                field.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                field.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    </script>
</body>

</html>