<?php
session_start();
include("config/db.php");

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
    <meta charset="UTF-8">
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
    </style>
</head>


<body class="bg-light">
    <nav class="navbar navbar-dark bg-dark">
        <div class="container-fluid">
            <div class="navbar-brand">
                <a class="navbar-brand fw-bold" href="../index.php">🛍️
                    <span class="ms-1">Stella Shopping Rosario</span></a>
                <span class="navbar-text text-light">Inicio de Sesion</span>
            </div>
        </div>
    </nav>
    <div class="container mt-5">
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

                        <form method="POST">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" required
                                    value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                                    placeholder="Ingresa tu email">
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Contraseña</label>
                                <input type="password" class="form-control" id="password" name="password" required
                                    placeholder="Ingresa tu contraseña">
                            </div>
                            <button type="submit" name="login" class="btn btn-primary w-100">Iniciar Sesión</button>
                        </form>
                        <div class="text-center mt-3">
                            <a href="register.php" class="d-block mb-2">🔐 ¿No tienes cuenta? Regístrate</a>
                            <a href="index.php" class="btn btn-outline-secondary btn-sm">🏠 Volver al Inicio</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- footer -->
    <?php include('footer.php'); ?>
</body>

</html>