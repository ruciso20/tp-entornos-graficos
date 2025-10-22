<?php
session_start();
include("config/db.php");

$error = "";
$success = "";

if (isset($_POST['register'])) {
    $nombre = trim($_POST['nombre']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $tipo = $_POST['tipo'];

    // Validaciones básicas
    if (empty($nombre) || empty($email) || empty($password)) {
        $error = "Todos los campos son obligatorios";
    } elseif (strlen($password) < 6) {
        $error = "La contraseña debe tener al menos 6 caracteres";
    } else {
        // Verificar si el email ya existe
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

            // Configuración según tipo de usuario
            if ($tipo == 'dueno') {
                $estado = 'pendiente';
                $categoria_cliente = NULL;
            } else {
                $estado = 'pendiente';
                $categoria_cliente = 'inicial';
            }

            // Insertar usuario
            $sql = "INSERT INTO usuarios (nombre, email, password, rol, estado, categoria_cliente, token_verificacion, email_verificado) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, 0)";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssssss", $nombre, $email, $hashed_password, $tipo, $estado, $categoria_cliente, $token);

            if ($stmt->execute()) {
                // Enviar email con mail() nativo
                $enlace_verificacion = "http://localhost/tpentornosgraficos/verificar_email.php?token=" . $token;
                $tipo_usuario = ($tipo == 'dueno') ? 'Dueño de Local' : 'Cliente';

                $asunto = "Verifica tu cuenta - Shopping Rosario";

                $mensaje = "
                ¡Hola $nombre!

                Gracias por registrarte en Shopping Rosario como $tipo_usuario.

                Para activar tu cuenta, haz clic en el siguiente enlace:
                $enlace_verificacion

                O copia y pega la URL en tu navegador.

                Si no te registraste en Shopping Rosario, por favor ignora este email.

                --
                Shopping Rosario
                ";

                $headers = "From: no-reply@shoppingrosario.com\r\n";
                $headers .= "Reply-To: no-reply@shoppingrosario.com\r\n";
                $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

                if (mail($email, $asunto, $mensaje, $headers)) {
                    if ($tipo == 'dueno') {
                        $success = "✅ Registro exitoso. Te hemos enviado un email de verificación a <strong>$email</strong>. Una vez verificado, tu cuenta estará pendiente de aprobación del administrador.";
                    } else {
                        $success = "✅ Registro exitoso. Te hemos enviado un email de verificación a <strong>$email</strong>. Una vez verificado, podrás acceder a todas las promociones.";
                    }
                } else {
                    // Fallback: mostrar enlace directamente
                    $success = "✅ Registro exitoso. No pudimos enviar el email automáticamente.";
                    $success .= "<br><br><strong>Para activar tu cuenta, haz clic en este enlace:</strong><br>
                                <div class='mt-3 p-3 bg-light border rounded'>
                                    <a href='$enlace_verificacion' class='btn btn-success btn-lg w-100' target='_blank'>
                                        ✅ Verificar Mi Cuenta
                                    </a>
                                    <div class='mt-2 text-muted small'>
                                        O copia esta URL: <code>$enlace_verificacion</code>
                                    </div>
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
    <title>Registro - Shopping Rosario</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white text-center">
                        <h4 class="mb-0">🛍 Registro - Shopping Rosario</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
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
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Nombre Completo *</label>
                                            <input type="text" class="form-control" name="nombre" required
                                                value="<?php echo isset($_POST['nombre']) ? htmlspecialchars($_POST['nombre']) : ''; ?>">
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Email *</label>
                                            <input type="email" class="form-control" name="email" required
                                                value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                                            <div class="form-text">Te enviaremos un email de verificación</div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Contraseña *</label>
                                            <input type="password" class="form-control" name="password" required minlength="6">
                                            <div class="form-text">Mínimo 6 caracteres</div>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Tipo de Usuario *</label>
                                            <select class="form-select" name="tipo" required>
                                                <option value="">Seleccionar tipo</option>
                                                <option value="cliente" <?php echo (isset($_POST['tipo']) && $_POST['tipo'] == 'cliente') ? 'selected' : ''; ?>>👤 Cliente</option>
                                                <option value="dueno" <?php echo (isset($_POST['tipo']) && $_POST['tipo'] == 'dueno') ? 'selected' : ''; ?>>🏪 Dueño de Local</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <button type="submit" name="register" class="btn btn-primary w-100 btn-lg">
                                    📝 Registrarse
                                </button>
                            </form>

                            <div class="text-center mt-3">
                                <a href="login.php">¿Ya tienes cuenta? Inicia sesión</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>