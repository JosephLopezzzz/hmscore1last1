<?php
require __DIR__ . '/vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;

$config = require __DIR__ . '/includes/smtp_config.php';

$mail = new PHPMailer(true);
try {
  $mail->isSMTP();
  $mail->Host       = $config['host'];
  $mail->SMTPAuth   = true;
  $mail->Username   = $config['username'];
  $mail->Password   = $config['password'];
  $mail->SMTPSecure = $config['encryption'];
  $mail->Port       = $config['port'];
  $mail->setFrom($config['from_email'], 'Inn Nexus Test');
  $mail->addAddress($config['username']);
  $mail->isHTML(true);
  $mail->Subject = 'Test Email';
  $mail->Body    = 'If you see this, SMTP works!';
  $mail->send();
  echo "✅ Test email sent successfully!";
} catch (Exception $e) {
  echo "❌ Mail error: " . $mail->ErrorInfo;
}
