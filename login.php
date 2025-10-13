<?php
session_start();
include("config/db.php");

if (isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $sql = "SELECT * FROM usuarios WHERE email='$email'";
    $result = $conn->query($sql);

    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();

        if (password_verify($password, $row['password'])) {
            // Verificar si el email está verificado
            if (!$row['email_verificado']) {
                $error = "❌ Tu email no ha sido verificado. Revisa tu bandeja de entrada y haz click en el enlace de verificación.";
                $email_no_verificado = true;

                // Ofrecer reenviar verificación
                $token = $row['token_verificacion'];
                if ($token) {
                    $reenviar_url = "reenviar_verificacion.php?email=" . urlencode($email);
                    $error .= "<br><br><a href='$reenviar_url' class='btn btn-warning btn-sm'>📧 Reenviar Email de Verificación</a>";
                }
            }
            // Verificar estado de la cuenta
            elseif ($row['estado'] == 'aprobado' || $row['rol'] == 'cliente') {
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['nombre'] = $row['nombre'];
                $_SESSION['rol'] = $row['rol'];
                $_SESSION['email_verificado'] = true;

                // MODIFICADO: Solo guardar categoría si es cliente
                if ($row['rol'] == 'cliente') {
                    $_SESSION['categoria'] = $row['categoria_cliente'] ?: 'Inicial';
                } else {
                    $_SESSION['categoria'] = null; // Dueños no tienen categoría
                }

                header("Location: dashboard.php");
                exit;
            } elseif ($row['estado'] == 'pendiente') {
                $error = "⏳ Tu cuenta está pendiente de aprobación. Te notificaremos por email cuando sea activada.";
                $cuenta_pendiente = true;
            } elseif ($row['estado'] == 'rechazado') {
                $error = "❌ Tu cuenta fue rechazada. Puedes contactar al administrador o crear una nueva solicitud.";
                $cuenta_rechazada = true;
            }
        } else {
            $error = "❌ Contraseña incorrecta";
        }
    } else {
        $error = "❌ Usuario no encontrado";
    }
}

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Login - Shopping Rosario</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body class="bg-light">
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
                                    <a href="index.php" class="btn btn-outline-primary">🏠 Volver al Inicio</a>
                                    <?php if (isset($email_no_verificado)): ?>
                                        <a href="register.php" class="btn btn-outline-warning">📧 Reenviar Verificación</a>
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
                                    value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Contraseña</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <button type="submit" name="login" class="btn btn-primary w-100">Iniciar Sesión</button>
                        </form>
                        <div class="text-center mt-3">
                            <a href="register.php">¿No tienes cuenta? Regístrate</a>
                            <br>
                            <a href="index.php" class="btn btn-outline-secondary btn-sm mt-2">🏠 Volver al Inicio</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>