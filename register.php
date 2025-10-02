<?php
include("config/db.php");

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

if (isset($_POST['registrar'])) {
    $nombre   = $_POST['nombre'];
    $email    = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $rol      = $_POST['rol'];

    // Todos los usuarios quedan como "pendiente" hasta validación correspondiente
    $estado = "pendiente";

    // Generamos un token único
    $token = bin2hex(random_bytes(16));

    $sql = "INSERT INTO usuarios (nombre, email, password, rol, estado, token) 
            VALUES ('$nombre', '$email', '$password', '$rol', '$estado', '$token')";

    if ($conn->query($sql) === TRUE) {
        // PHPMailer - Aprovechamos esta funcion de PHP en vez del sendmail() de XAMPP
        $mail = new PHPMailer(true);
        try {
            // Configuración SMTP
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'guille.petri47@gmail.com';   // Mi Gmail personal
            $mail->Password   = 'jznk ougq frbj kveo';       // App Password de Gmail, sobre Stella Shopping
            $mail->SMTPSecure = 'tls';
            $mail->Port       = 587;

            // Remitente y destinatario
            $mail->setFrom('guille.petri47@gmail.com', 'Stella Shopping Rosario');
            $mail->addAddress($email, $nombre);

            // Contenido
            $mail->isHTML(true);
            $mail->Subject = 'Valida tu cuenta en Stella Shopping Rosario';
            $mail->Body    = "
                Hola <b>$nombre</b>,<br><br>
                Gracias por registrarte en Stella Shopping Rosario.<br>
                Para activar tu cuenta haz clic en el siguiente enlace:<br><br>
                <a href='http://localhost/shopping/validar.php?token=$token'>
                Validar cuenta</a><br><br>
                ¡Nos vemos pronto!";

            $mail->send();
            echo "<script>alert('Registro exitoso. Revisa tu correo para validar tu cuenta.'); window.location='login.php';</script>";
        } catch (Exception $e) {
            echo "Error al enviar correo: {$mail->ErrorInfo}";
        }
    } else {
        echo "Error: " . $conn->error;
    }
}
?>

