<?php
// includes/mailer.php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php'; // Composer autoload
$config = require __DIR__ . '/smtp_config.php';

/**
 * Send email using PHPMailer.
 *
 * @param string $toEmail   Recipient email address
 * @param string $toName    Recipient name (can be same as email)
 * @param string $subject   Email subject
 * @param string $bodyHtml  HTML email content
 * @param string $bodyText  Optional plain-text fallback
 *
 * @return array ['success' => bool, 'message' => string]
 */
function sendMail(string $toEmail, string $toName, string $subject, string $bodyHtml, string $bodyText = ''): array {
    global $config;

    try {
        $mail = new PHPMailer(true);

 $mail->SMTPDebug = 0; // or 2 for detailed log
$mail->isSMTP();
$mail->Host       = $config['host'];
$mail->SMTPAuth   = true;
$mail->Username   = $config['username'];
$mail->Password   = $config['password'];
$mail->SMTPSecure = $config['encryption'];
$mail->Port       = $config['port'];
$mail->setFrom($config['from_email'], $config['from_name']);
$mail->isHTML(true);
$mail->CharSet = 'UTF-8';


        // Sender
        $mail->setFrom($config['from_email'], $config['from_name'] ?? 'Inn Nexus');
        $mail->addAddress($toEmail, $toName);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $bodyHtml;
        $mail->AltBody = $bodyText ?: strip_tags($bodyHtml);

        // Attempt to send
        $mail->send();

        return [
            'success' => true,
            'message' => 'Email sent successfully'
        ];
    } catch (Exception $e) {
        // Log error for debugging (optional)
        $logFile = __DIR__ . '/../logs/mail_error.log';
        if (!is_dir(dirname($logFile))) {
            mkdir(dirname($logFile), 0777, true);
        }
        file_put_contents($logFile, '[' . date('Y-m-d H:i:s') . "] Mailer Error: {$e->getMessage()}\n", FILE_APPEND);

        return [
            'success' => false,
            'message' => 'Mailer Error: ' . $e->getMessage()
        ];
    }
}
