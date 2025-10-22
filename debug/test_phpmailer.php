<?php
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;

$mail = new PHPMailer(true);
try {
  $mail->isSMTP();
  $mail->Host = 'smtp.gmail.com';
  $mail->SMTPAuth = true;
  $mail->Username = 'guille.petri47@gmail.com';
  $mail->Password = 'zdwm hxyv rmca pkio';
  $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
  $mail->Port = 587;

  $mail->setFrom('guille.petri47@gmail.com', 'Test');
  $mail->addAddress('guille.petri47@gmail.com', 'Test User');

  $mail->Subject = 'Test Email';
  $mail->Body = 'This is a test email';

  if ($mail->send()) {
    echo "✅ Email enviado correctamente";
  }
} catch (Exception $e) {
  echo "❌ Error: " . $mail->ErrorInfo;
}
