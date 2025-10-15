<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/mailer.php';
initSession();

$error = '';

if (($_POST['_action'] ?? '') === 'login') {
  if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    $error = 'Invalid CSRF token.';
  } else {
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $error = 'Please enter a valid email address.';
    } else {
      $pdo = getPdo();
      $stmt = $pdo->prepare("SELECT id, password_hash, role, is_active, email_verified, failed_login_attempts, locked_until FROM users WHERE email = ?");
      $stmt->execute([$email]);
      $user = $stmt->fetch(PDO::FETCH_ASSOC);

      if ($user) {
        // Check if account is locked
        $locked_until = strtotime($user['locked_until'] ?? '');
        if ($locked_until && $locked_until > time()) {
          $error = 'Account is temporarily locked due to too many failed attempts. Please try again later.';
        } elseif (!$user['is_active']) {
          $error = 'Account is disabled. Please contact an administrator.';
        } elseif (password_verify($password, $user['password_hash'])) {
          // Reset failed attempts on successful login
          $stmt = $pdo->prepare("UPDATE users SET failed_login_attempts = 0, locked_until = NULL, last_login_at = NOW(), last_login_ip = ? WHERE id = ?");
          $stmt->execute([$_SERVER['REMOTE_ADDR'] ?? '', $user['id']]);

          // Send 2FA OTP email using PHPMailer
          $otp = random_int(100000, 999999);
          $_SESSION['temp_user_id'] = $user['id'];
          $_SESSION['temp_email'] = $email;
          $_SESSION['temp_role'] = $user['role'];
          $_SESSION['2fa_required'] = true;
          $_SESSION['otp_code'] = $otp;
          $_SESSION['otp_expires'] = time() + 300; // 5 minutes

          // Send OTP email using PHPMailer
          require_once __DIR__ . '/vendor/autoload.php';
          $config = require __DIR__ . '/includes/smtp_config.php';

          $mail = new PHPMailer\PHPMailer\PHPMailer(true);
          try {
            $mail->isSMTP();
            $mail->Host       = $config['host'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $config['username'];
            $mail->Password   = $config['password'];
            $mail->SMTPSecure = $config['encryption'];
            $mail->Port       = $config['port'];

            $mail->setFrom($config['from_email'], $config['from_name']);
            $mail->addAddress($email);

            $mail->isHTML(true);
            $mail->Subject = 'Your Inn Nexus 2FA Verification Code';
            $mail->Body    = "
              <h2>Inn Nexus Login Verification</h2>
              <p>Your 6-digit verification code is:</p>
              <h1 style='font-size:24px; letter-spacing:4px; color: #3b82f6;'>$otp</h1>
              <p>This code will expire in 5 minutes.</p>
              <p>If you didn't request this, please ignore this email.</p>
              <hr>
              <p><small>Inn Nexus Hotel Management System</small></p>
            ";
            $mail->AltBody = "Your Inn Nexus verification code is: $otp (expires in 5 minutes).";

            $mail->send();

            header('Location: verify-2fa.php');
            exit;
          } catch (Exception $e) {
            $error = 'Failed to send verification email. Please try again.';
          }
        } else {
          // Increment failed attempts
          $failed_attempts = ($user['failed_login_attempts'] ?? 0) + 1;
          $lockout_threshold = 5; // Lock account after 5 failed attempts

          if ($failed_attempts >= $lockout_threshold) {
            // Lock the account for 15 minutes
            $locked_until = date('Y-m-d H:i:s', strtotime('+15 minutes'));
            $stmt = $pdo->prepare("UPDATE users SET failed_login_attempts = ?, locked_until = ? WHERE id = ?");
            $stmt->execute([$failed_attempts, $locked_until, $user['id']]);
            $error = 'Account locked due to too many failed attempts. Please try again in 15 minutes.';
          } else {
            $stmt = $pdo->prepare("UPDATE users SET failed_login_attempts = ? WHERE id = ?");
            $stmt->execute([$failed_attempts, $user['id']]);
            $error = 'Invalid email or password. ' . ($lockout_threshold - $failed_attempts) . ' attempts remaining.';
          }
        }
      } else {
        $error = 'Invalid email or password.';
      }
    }
  }
}
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Login - Inn Nexus Hotel Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="./public/css/tokens.css" />
  </head>

  <body class="min-h-screen bg-background flex items-center justify-center">
    <div class="w-full max-w-sm rounded-lg border bg-card p-6 shadow-sm">
      <h1 class="text-2xl font-bold mb-4 text-center">Sign in</h1>

      <?php if (!empty($error)): ?>
        <p class="text-red-500 text-sm mb-3 text-center"><?= htmlspecialchars($error) ?></p>
      <?php endif; ?>

      <form method="post" class="space-y-3">
        <input type="hidden" name="_action" value="login" />
        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>" />

        <div>
          <label class="text-xs text-muted-foreground">Email</label>
          <input name="email" type="email" required
                 class="h-10 w-full rounded-md border bg-background px-3 text-sm" />
        </div>

        <div>
          <label class="text-xs text-muted-foreground">Password</label>
          <input name="password" type="password" required
                 class="h-10 w-full rounded-md border bg-background px-3 text-sm" />
        </div>

        <button class="w-full h-10 rounded-md bg-primary text-primary-foreground hover:bg-primary/90 transition">
          Login
        </button>
      </form>

      <div class="mt-4 text-center">
        <a href="forgot_password.php" class="text-sm text-blue-600 hover:underline">
          Forgot password?
        </a>
      </div>
    </div>
  </body>
</html>
