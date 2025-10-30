<?php
session_start();
include("config/db.php");
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

    // validacion basica
    if (empty($nombre) || empty($email) || empty($password)) {
        $error = "Todos los campos son obligatorios";
    } elseif (strlen($password) < 6) {
        $error = "La contraseña debe tener al menos 6 caracteres";
    } else {
        // verificamos si el email ya existe
        $check_sql = "SELECT id FROM usuarios WHERE email = ?";
        $stmt = $conn->prepare($check_sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $check_result = $stmt->get_result();

        if ($check_result->num_rows > 0) {
            $error = "El email ya está registrado";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $token = bin2hex(random_bytes(50));

            // configuracion puntual segun el tipo de usuario
            if ($tipo == 'dueno') {
                $estado = 'pendiente';
                $categoria_cliente = NULL;
            } else {
                $estado = 'pendiente';
                $categoria_cliente = 'inicial';
            }

            // insertar el usuario
            $sql = "INSERT INTO usuarios (nombre, email, password, rol, estado, categoria_cliente, token_verificacion, email_verificado) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, 0)";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssssss", $nombre, $email, $hashed_password, $tipo, $estado, $categoria_cliente, $token);

            if ($stmt->execute()) {
                $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                $host   = $_SERVER['HTTP_HOST'];
                // carpeta del proyecto a partir de la ruta del script actual
                $projectRoot = rtrim(str_replace(basename($_SERVER['SCRIPT_NAME']), '', $_SERVER['SCRIPT_NAME']), '/\\');
                // URL final a verificar_email.php en la raiz de nuestro proyecto
                $enlace_verificacion = $scheme . '://' . $host . rtrim($projectRoot, '/\\') . '/verificar_email.php?token=' . $token;

                $tipo_usuario = ($tipo == 'dueno') ? 'Dueño de Local' : 'Cliente';

                $mail = new PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = 'joaquingarciaforestello@gmail.com';           // <-- el Gmail que elegimos
                    $mail->Password = 'fcyt bvju nlte smek';     // <-- contraseña de la aplicacion (Gmail con 2FA)
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port = 587;

                    $mail->setFrom('no-reply@shoppingrosario.com', 'Shopping Rosario');
                    $mail->addAddress($email, $nombre);

                    $mail->isHTML(true);
                    $mail->Subject = 'Verifica tu cuenta - Shopping Rosario';

                    $mail->Body = "
                        <p>¡Hola $nombre!</p>
                        <p>Gracias por registrarte en <strong>Shopping Rosario</strong> como <strong>$tipo_usuario</strong>.</p>
                        <p>Para activar tu cuenta, hacé click aquí:</p>
                        <p><a href='$enlace_verificacion'>$enlace_verificacion</a></p>
                        <p>Si no te registraste, ignorá este email.</p>
                    ";
                    $mail->AltBody = "Hola $nombre!\n\nVerificá tu cuenta con este enlace:\n$enlace_verificacion";

                    if ($mail->send()) {
                        if ($tipo == 'dueno') {
                            $success = "✅ Registro exitoso. Te enviamos un email de verificación a <strong>$email</strong>. Como Dueño de Local, tu cuenta quedará pendiente de aprobación del administrador.";
                        } else {
                            $success = "✅ Registro exitoso. Te enviamos un email de verificación a <strong>$email</strong>. Revisá tu bandeja de entrada.";
                        }
                    } else {
                        // si por alguna razón no se envía el correo, mostramos el enlace
                        $success = "✅ Registro exitoso. No pudimos enviar el email automáticamente.<br>
                                    <strong>Verificá tu cuenta desde este enlace:</strong><br>
                                    <div class='mt-3 p-3 bg-light border rounded'>
                                        <a href='$enlace_verificacion' class='btn btn-success'>Verificar cuenta</a>
                                    </div>";
                    }
                } catch (Exception $e) {
                    error_log("Error PHPMailer (register): " . $mail->ErrorInfo);
                    $success = "✅ Registro exitoso. No pudimos enviar el email automáticamente.<br>
                                <strong>Verificá tu cuenta desde este enlace:</strong><br>
                                <div class='mt-3 p-3 bg-light border rounded'>
                                    <a href='$enlace_verificacion' class='btn btn-success'>Verificar cuenta</a>
                                </div>";
                }
            } else {
                $error = "Error en el registro: " . $conn->error;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Registro - Stella Shopping</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">Crear Cuenta</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger">
                                <?php echo $error; ?>
                                <div class="mt-3">
                                    <a href="index.php" class="btn btn-outline-primary">🏠 Volver al Inicio</a>
                                    <a href="login.php" class="btn btn-outline-secondary">🔐 Iniciar Sesión</a>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if ($success): ?>
                            <div class="alert alert-success">
                                <?php echo $success; ?>
                                <div class="mt-3 text-center">
                                    <p>Después de verificar tu cuenta, podrás iniciar sesión:</p>
                                    <a href="login.php" class="btn btn-primary">🔐 Iniciar Sesión</a>
                                    <a href="index.php" class="btn btn-outline-secondary">🏠 Volver al Inicio</a>
                                </div>
                            </div>
                        <?php else: ?>
                            <form method="POST">
                                <div class="mb-3">
                                    <label class="form-label">Nombre Completo *</label>
                                    <input type="text" class="form-control" name="nombre" required
                                        value="<?php echo isset($_POST['nombre']) ? htmlspecialchars($_POST['nombre']) : ''; ?>"
                                        placeholder="Ingresa tu nombre completo">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Email *</label>
                                    <input type="email" class="form-control" name="email" required
                                        value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                                        placeholder="ejemplo@correo.com">
                                    <div class="form-text">Te enviaremos un email de verificación</div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Contraseña *</label>
                                    <input type="password" class="form-control" name="password" required minlength="6"
                                        placeholder="Mínimo 6 caracteres">
                                    <div class="form-text">La contraseña debe tener al menos 6 caracteres</div>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label">Tipo de Usuario *</label>
                                    <select class="form-select" name="tipo" required>
                                        <option value="">Seleccionar tipo de usuario</option>
                                        <option value="cliente" <?php echo (isset($_POST['tipo']) && $_POST['tipo'] == 'cliente') ? 'selected' : ''; ?>>👤 Cliente</option>
                                        <option value="dueno" <?php echo (isset($_POST['tipo']) && $_POST['tipo'] == 'dueno') ? 'selected' : ''; ?>>🏪 Dueño de Local</option>
                                    </select>
                                    <div class="form-text">
                                        <small>
                                            <strong>Cliente:</strong> Accede a promociones y descuentos<br>
                                            <strong>Dueño de Local:</strong> Gestiona promociones de tu negocio (requiere aprobación)
                                        </small>
                                    </div>
                                </div>

                                <button type="submit" name="register" class="btn btn-primary w-100">
                                    <i class="fas fa-user-plus me-2"></i>Registrarse
                                </button>
                            </form>

                            <div class="text-center mt-3">
                                <a href="login.php">🔐 ¿Ya tienes cuenta? Inicia sesión</a>
                                <br>
                                <a href="index.php" class="btn btn-outline-secondary btn-sm mt-2">🏠 Volver al Inicio</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- footer -->
    <?php include('footer.php'); ?>
</body>

</html>