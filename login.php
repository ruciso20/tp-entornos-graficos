<?php
session_start();
include("config/db.php");

if (isset($_POST['login'])) {
    $email    = $_POST['email'];
    $password = $_POST['password'];

    $sql = "SELECT * FROM usuarios WHERE email='$email'";
    $result = $conn->query($sql);

    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();

        if (password_verify($password, $row['password'])) {
            if ($row['estado'] == 'aprobado') {
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['nombre']  = $row['nombre'];
                $_SESSION['rol']     = $row['rol'];

                header("Location: index.php");
                exit;
            } else {
                echo "<script>alert('Tu cuenta aún no fue aprobada por el administrador.');</script>";
            }
        } else {
            echo "<script>alert('Contraseña incorrecta');</script>";
        }
    } else {
        echo "<script>alert('Usuario no encontrado');</script>";
    }
}
?>
