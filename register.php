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

    if (empty($nombre) || empty($email) || empty($password)) {
        $error = "Todos los campos son obligatorios";
    } else {
        $check_sql = "SELECT * FROM usuarios WHERE email='$email'";
        $check_result = $conn->query($check_sql);

        if ($check_result->num_rows > 0) {
            $error = "El email ya está registrado";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Si es dueño, queda pendiente. Si es cliente, queda aprobado automáticamente
            $estado = ($tipo == 'dueno') ? 'pendiente' : 'aprobado';
            
            $sql = "INSERT INTO usuarios (nombre, email, password, rol, estado, categoria_cliente) 
                    VALUES ('$nombre', '$email', '$hashed_password', '$tipo', '$estado', 'Inicial')";

            if ($conn->query($sql) === TRUE) {
                if ($tipo == 'dueno') {
                    $success = "✅ Registro exitoso. Tu cuenta como dueño de local está <strong>pendiente de aprobación</strong>. Te notificaremos por email cuando sea activada.";
                } else {
                    $success = "✅ Registro exitoso. Ya puedes iniciar sesión.";
                }
                $_POST = array();
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
                                    <?php if (isset($tipo) && $tipo == 'dueno'): ?>
                                        <a href="index.php" class="btn btn-outline-primary">🏠 Volver al Inicio</a>
                                        <a href="login.php" class="btn btn-outline-secondary">🔐 Ir al Login</a>
                                    <?php else: ?>
                                        <a href="login.php" class="btn btn-success">🎉 Ir al Login</a>
                                    <?php endif; ?>
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
                                <div class="form-text">
                                    <small>
                                        ✅ <strong>Cliente:</strong> Acceso inmediato<br>
                                        ⏳ <strong>Dueño:</strong> Requiere aprobación del administrador
                                    </small>
                                </div>
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