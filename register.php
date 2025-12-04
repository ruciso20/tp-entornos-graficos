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
    $password_repeat = $_POST['password_repeat'];
    $tipo = $_POST['tipo'];

    // validacion basica de todos los campos y requisitos de la contraseña
    if (empty($nombre) || empty($email) || empty($password) || empty($password_repeat)) {
        $error = "Todos los campos son obligatorios";
    } elseif (strlen($password) < 8) {
        $error = "La contraseña debe tener al menos 8 caracteres";
    } elseif ($password !== $password_repeat) {
        $error = "Las contraseñas no coinciden";
    } elseif (!preg_match('/[A-Z]/', $password)) {
        $error = "La contraseña debe contener al menos una letra mayúscula";
    } elseif (!preg_match('/[0-9]/', $password)) {
        $error = "La contraseña debe contener al menos un número";
    } elseif (!preg_match('/[!@#$%^&*()\-_=+{};:,<.>]/', $password)) {
        $error = "La contraseña debe contener al menos un carácter especial (!@#$%^&*)";
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
                $estado = 'aprobado';
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
                $projectRoot = rtrim(str_replace(basename($_SERVER['SCRIPT_NAME']), '', $_SERVER['SCRIPT_NAME']), '/\\');
                $enlace_verificacion = $scheme . '://' . $host . rtrim($projectRoot, '/\\') . '/verificar_email.php?token=' . $token;

                $tipo_usuario = ($tipo == 'dueno') ? 'Dueño de Local' : 'Cliente';

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
                    $mail->addAddress($email, $nombre);

                    $mail->isHTML(true);
                    $mail->Subject = 'Verifica tu cuenta - Stella Shopping Rosario';

                    $mail->Body = "
                        <p>¡Hola $nombre!</p>
                        <p>Gracias por registrarte en <strong>Stella Shopping Rosario</strong> como <strong>$tipo_usuario</strong>.</p>
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
    <title>Registro - Stella Shopping Rosario</title>
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

        footer {
            margin-top: auto;
        }

        .main-content {
            min-height: 70vh;
            display: flex;
            align-items: center;
        }

        /* Estilos para los campos de la contraseña (para el ojo) */
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
        }

        .toggle-password:hover {
            color: #495057;
        }

        /* Para desactivar el ojo nativo del navegador para la contraseña que queda mal (no funcionó bien) */
        /* Para Chrome, Safari, Edge */
        input[type="password"]::-webkit-credentials-auto-fill-button,
        input[type="password"]::-webkit-textfield-decoration-container,
        input[type="password"]::-webkit-caps-lock-indicator,
        input[type="password"]::-webkit-strong-password-auto-fill-button {
            display: none !important;
            visibility: hidden !important;
            opacity: 0 !important;
            pointer-events: none !important;
            position: absolute !important;
            right: -1000px !important;
        }

        /* Para Firefox */
        input[type="password"]::-moz-textfield-decoration-container,
        input[type="password"]::-moz-caps-lock-indicator {
            display: none !important;
        }

        /* Deshabilitamos los iconos nativos de los navegadores */
        input[type="password"] {
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
        }

        .password-container .toggle-password {
            z-index: 100 !important;
            background-color: white !important;
            padding: 0 5px !important;
        }

        /* barra de fortaleza de contraseña */
        .password-strength {
            height: 5px;
            margin-top: 5px;
            border-radius: 3px;
            transition: all 0.3s ease;
        }

        .strength-weak {
            width: 25%;
            background-color: #dc3545;
        }

        .strength-medium {
            width: 50%;
            background-color: #ffc107;
        }

        .strength-good {
            width: 75%;
            background-color: #17a2b8;
        }

        .strength-strong {
            width: 100%;
            background-color: #28a745;
        }

        /* lista de requisitos */
        .requirement-list {
            list-style: none;
            padding-left: 0;
            margin-top: 5px;
            font-size: 0.85rem;
        }

        .requirement-list li {
            margin-bottom: 3px;
        }

        .requirement-valid {
            color: #28a745;
        }

        .requirement-invalid {
            color: #6c757d;
        }

        .requirement-valid i {
            margin-right: 5px;
        }

        .requirement-invalid i {
            margin-right: 5px;
        }
    </style>
</head>

<body class="bg-light">
    <nav class="navbar navbar-dark bg-dark">
        <div class="container-fluid">
            <div class="navbar-brand">
                <a class="navbar-brand fw-bold" href="../index.php">🛍️
                    <span class="ms-1">Stella Shopping Rosario</span></a>
                <span class="navbar-text text-light">Registrarse</span>
            </div>
        </div>
    </nav>
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
                            <form method="POST" id="registerForm">
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
                                    <div class="password-container">
                                        <input type="password" class="form-control" name="password" id="password" required
                                            placeholder="Mínimo 8 caracteres con mayúscula, número y símbolo">
                                        <button type="button" class="toggle-password" onclick="togglePassword('password')">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>

                                    <!-- Barra de fortaleza -->
                                    <div class="password-strength mt-2" id="passwordStrengthBar"></div>
                                    <small class="text-muted" id="passwordStrengthText">Fortaleza: </small>

                                    <!-- Lista de requisitos -->
                                    <ul class="requirement-list mt-2" id="passwordRequirements">
                                        <li id="reqLength"><i class="fas fa-circle"></i> Al menos 8 caracteres</li>
                                        <li id="reqUppercase"><i class="fas fa-circle"></i> Al menos una mayúscula</li>
                                        <li id="reqNumber"><i class="fas fa-circle"></i> Al menos un número</li>
                                        <li id="reqSpecial"><i class="fas fa-circle"></i> Al menos un símbolo (!@#$%^&*)</li>
                                    </ul>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Repetir Contraseña *</label>
                                    <div class="password-container">
                                        <input type="password" class="form-control" name="password_repeat" id="password_repeat" required minlength="6"
                                            placeholder="Repite tu contraseña">
                                        <button type="button" class="toggle-password" onclick="togglePassword('password_repeat')">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    <div class="form-text" id="passwordMatchText">Las contraseñas deben coincidir</div>
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

    <script>
        // para evaluar la contraseña
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

        // funcion para evaluar la fortaleza de la contraseña
        function checkPasswordStrength(password) {
            let score = 0;
            let strength = 'débil';
            let color = '#dc3545';

            // verificamos los requisitos minimos
            const hasLength = password.length >= 8;
            const hasUppercase = /[A-Z]/.test(password);
            const hasNumber = /[0-9]/.test(password);
            const hasSpecial = /[!@#$%^&*()\-_=+{};:,<.>]/.test(password);

            // calculamos el puntaje de la contraseña
            if (hasLength) score++;
            if (hasUppercase) score++;
            if (hasNumber) score++;
            if (hasSpecial) score++;

            // determinamos la fortaleza para la barra
            if (score === 4) {
                strength = 'fuerte';
                color = '#28a745';
            } else if (score === 3) {
                strength = 'buena';
                color = '#17a2b8';
            } else if (score === 2) {
                strength = 'media';
                color = '#ffc107';
            } else {
                strength = 'débil';
                color = '#dc3545';
            }

            return {
                score,
                strength,
                color,
                hasLength,
                hasUppercase,
                hasNumber,
                hasSpecial
            };
        }

        function updateRequirementsUI(result) {
            document.getElementById('reqLength').className = result.hasLength ? 'requirement-valid' : 'requirement-invalid';
            document.getElementById('reqUppercase').className = result.hasUppercase ? 'requirement-valid' : 'requirement-invalid';
            document.getElementById('reqNumber').className = result.hasNumber ? 'requirement-valid' : 'requirement-invalid';
            document.getElementById('reqSpecial').className = result.hasSpecial ? 'requirement-valid' : 'requirement-invalid';

            const reqLengthIcon = document.querySelector('#reqLength i');
            const reqUppercaseIcon = document.querySelector('#reqUppercase i');
            const reqNumberIcon = document.querySelector('#reqNumber i');
            const reqSpecialIcon = document.querySelector('#reqSpecial i');

            reqLengthIcon.className = result.hasLength ? 'fas fa-check-circle' : 'fas fa-circle';
            reqUppercaseIcon.className = result.hasUppercase ? 'fas fa-check-circle' : 'fas fa-circle';
            reqNumberIcon.className = result.hasNumber ? 'fas fa-check-circle' : 'fas fa-circle';
            reqSpecialIcon.className = result.hasSpecial ? 'fas fa-check-circle' : 'fas fa-circle';

            const strengthBar = document.getElementById('passwordStrengthBar');
            const strengthText = document.getElementById('passwordStrengthText');

            strengthBar.className = 'password-strength strength-' + result.strength;
            strengthBar.style.backgroundColor = result.color;
            strengthText.innerHTML = `Fortaleza: <strong>${result.strength}</strong>`;
            strengthText.style.color = result.color;
        }

        // Validacion en tiempo real de contraseña
        document.addEventListener('DOMContentLoaded', function() {
            const password = document.getElementById('password');
            const passwordRepeat = document.getElementById('password_repeat');
            const passwordMatchText = document.getElementById('passwordMatchText');

            password.addEventListener('input', function() {
                const passwordValue = this.value;

                if (passwordValue.length > 0) {
                    const result = checkPasswordStrength(passwordValue);
                    updateRequirementsUI(result);
                } else {
                    // Resetear cuando este vacio
                    document.getElementById('passwordStrengthBar').className = 'password-strength';
                    document.getElementById('passwordStrengthBar').style.backgroundColor = '#e9ecef';
                    document.getElementById('passwordStrengthText').textContent = 'Fortaleza: ';

                    // resetamos la lista de contenidos para validar la contraseña
                    const requirements = document.querySelectorAll('#passwordRequirements li');
                    requirements.forEach(req => {
                        req.className = 'requirement-invalid';
                        req.querySelector('i').className = 'fas fa-circle';
                    });
                }

                // verificamos la coincidencia si el segundo campo tiene contenido
                if (passwordRepeat.value) {
                    checkPasswordMatch();
                }
            });

            function checkPasswordMatch() {
                if (password.value && passwordRepeat.value) {
                    if (password.value === passwordRepeat.value) {
                        passwordMatchText.innerHTML = '<span class="text-success">✓ Las contraseñas coinciden</span>';
                        return true;
                    } else {
                        passwordMatchText.innerHTML = '<span class="text-danger">✗ Las contraseñas no coinciden</span>';
                        return false;
                    }
                }
                passwordMatchText.textContent = 'Las contraseñas deben coincidir';
                return false;
            }

            passwordRepeat.addEventListener('input', checkPasswordMatch);

            document.getElementById('registerForm').addEventListener('submit', function(e) {
                const passwordValue = password.value;
                const result = checkPasswordStrength(passwordValue);

                // validar requisitos mínimos
                if (!result.hasLength || !result.hasUppercase || !result.hasNumber || !result.hasSpecial) {
                    e.preventDefault();
                    alert('La contraseña debe cumplir con todos los requisitos:\n\n' +
                        '• Al menos 8 caracteres\n' +
                        '• Al menos una letra mayúscula\n' +
                        '• Al menos un número\n' +
                        '• Al menos un símbolo (!@#$%^&*)');
                    return;
                }

                // validar coincidencia con la otra contraseña 
                if (!checkPasswordMatch()) {
                    e.preventDefault();
                    alert('Por favor, asegúrate de que las contraseñas coincidan.');
                }
            });
        });
    </script>
</body>

</html>