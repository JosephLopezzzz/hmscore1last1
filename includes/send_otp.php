send_otp.php<?php
// includes/send_otp.php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/mailer.php';

function generateOtpCode(): string {
    return str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
}

/**
 * Send OTP to user's email.
 * Returns array: ['success'=>bool,'message'=>string]
 */
function sendUserOtp(int $userId): array {
    $pdo = getPdo();
    if (!$pdo) return ['success'=>false,'message'=>'DB connection failed'];

    // Check user exists and get email / name
    $stmt = $pdo->prepare('SELECT id, email, name FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) return ['success'=>false,'message'=>'User not found'];

    // Rate-limit: last OTP for this user within 60 seconds?
    $stmt = $pdo->prepare('SELECT created_at, expires_at FROM user_otp WHERE user_id = ? ORDER BY id DESC LIMIT 1');
    $stmt->execute([$userId]);
    $last = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($last) {
        $lastCreated = strtotime($last['created_at']);
        if (time() - $lastCreated < 60) {
            return ['success'=>false,'message'=>'Please wait before requesting another code (1 minute)'];
        }
    }

    $otp = generateOtpCode();
    $expiresAt = date('Y-m-d H:i:s', time() + 300); // 5 minutes

    // Insert OTP into DB
    $ins = $pdo->prepare('INSERT INTO user_otp (user_id, otp_code, expires_at) VALUES (?, ?, ?)');
    $ok = $ins->execute([$userId, $otp, $expiresAt]);
    if (!$ok) return ['success'=>false,'message'=>'Failed to store code'];

    // Send email
    $subject = 'Your Inn Nexus Login Verification Code';
    $bodyHtml = "<p>Hello " . htmlspecialchars($user['name'] ?: $user['email']) . ",</p>"
      . "<p>Your verification code is <strong>{$otp}</strong>. It expires in 5 minutes.</p>"
      . "<p>If you did not request this, please ignore this message.</p>";
    $bodyText = "Your verification code is: {$otp}. It expires in 5 minutes.";

    $sent = sendMail($user['email'], $user['name'] ?: $user['email'], $subject, $bodyHtml, $bodyText);

    if ($sent) return ['success'=>true,'message'=>'OTP sent'];
    return ['success'=>false,'message'=>'Failed to send email'];
}
