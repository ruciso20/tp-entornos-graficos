<?php
include("config/db.php");

$mensaje = "";
$tipo = "danger"; // por defecto rojo

if (isset($_GET['token'])) {
  $token = $_GET['token'];

  $sql = "SELECT * FROM usuarios WHERE token='$token' AND estado='pendiente'";
  $result = $conn->query($sql);

  if ($result->num_rows == 1) {
    $row = $result->fetch_assoc();
    $id  = $row['id'];

    $sql_update = "UPDATE usuarios SET estado='aprobado', token=NULL WHERE id=$id";
    if ($conn->query($sql_update) === TRUE) {
      $mensaje = "Tu cuenta fue validada con éxito. Ya puedes iniciar sesión.";
      $tipo = "success"; // verde
    } else {
      $mensaje = "Error al activar la cuenta. Intenta nuevamente.";
    }
  } else {
    $mensaje = "Token inválido o la cuenta ya fue validada.";
  }
} else {
  $mensaje = "No se proporcionó un token de validación.";
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <title>Validación de Cuenta - Shopping Rosario</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

  <div class="container mt-5">
    <div class="row justify-content-center">
      <div class="col-md-6">
        <div class="card shadow">
          <div class="card-body text-center">
            <h3 class="card-title mb-4">Validación de Cuenta</h3>
            <div class="alert alert-<?php echo $tipo; ?>">
              <?php echo $mensaje; ?>
            </div>
            <a href="login.php" class="btn btn-primary">Ir al Login</a>
          </div>
        </div>
      </div>
    </div>
  </div>

</body>

</html>