<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/vendor/autoload.php';
initSession();

// Redirect if session expired or direct access
if (empty($_SESSION['temp_user_id']) || empty($_SESSION['2fa_required'])) {
  header('Location: login.php');
  exit;
}

$userId = $_SESSION['temp_user_id'];
$email  = $_SESSION['temp_email'] ?? '';
$error = '';
$success = '';

if (($_POST['_action'] ?? '') === 'verify_otp') {
  $otp = trim($_POST['otp'] ?? '');
  if (empty($otp)) {
    $error = 'Please enter your verification code.';
  } elseif (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    $error = 'Invalid CSRF token. Please refresh the page.';
  } else {
    $sessionOtp = $_SESSION['otp_code'] ?? null;
    $expiry = $_SESSION['otp_expires'] ?? 0;

    if ($sessionOtp && time() <= $expiry && $otp === (string)$sessionOtp) {
      // ✅ OTP is valid — finalize login
      $_SESSION['user_id'] = $userId;
      $_SESSION['user_role'] = $_SESSION['temp_role'] ?? 'user';

      // Clear temp 2FA session data
      unset($_SESSION['2fa_required'], $_SESSION['temp_user_id'], $_SESSION['temp_email'], $_SESSION['temp_role'], $_SESSION['otp_code'], $_SESSION['otp_expires']);

      header('Location: index.php');
      exit;
    } else {
      $error = 'Invalid or expired code. Please try again.';
    }
  }
}

if (($_POST['_action'] ?? '') === 'resend_otp') {
  if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    $error = 'Invalid CSRF token.';
  } else {
    $otp = random_int(100000, 999999);
    $_SESSION['otp_code'] = $otp;
    $_SESSION['otp_expires'] = time() + 300; // 5 minutes

    // Resend OTP email using PHPMailer
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
      $mail->Subject = 'Your New Inn Nexus 2FA Verification Code';
      $mail->Body    = "
        <h2>New Verification Code</h2>
        <p>Hello,</p>
        <p>Your new login verification code is:</p>
        <h1 style='font-size:24px; letter-spacing:4px; color: #3b82f6;'>$otp</h1>
        <p>This code will expire in 5 minutes.</p>
        <p>If you didn't request this, please ignore this email.</p>
        <hr>
        <p><small>Inn Nexus Hotel Management System</small></p>
      ";
      $mail->AltBody = "Your new verification code is: $otp. This code expires in 5 minutes.";

      $mail->send();
      $success = 'A new verification code has been sent to your email.';
    } catch (Exception $e) {
      $error = 'Failed to resend verification code. Please try again.';
    }
  }
}
?>
<!doctype html>
<html lang="en">
  <head>
    <!-- Theme initialization (must be first to prevent flash) -->
    <script>
      (function() {
        const theme = localStorage.getItem('theme') || 'light';
        document.documentElement.classList.toggle('dark', theme === 'dark');
        document.documentElement.classList.toggle('light-mode', theme === 'light');
      })();
    </script>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Verify OTP - Inn Nexus Hotel Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="./public/css/tokens.css" />
  </head>

  <body class="min-h-screen bg-background flex items-center justify-center">
    <!-- Theme Toggle Button -->
    <button id="theme-toggle" class="fixed top-4 right-4 p-2 rounded-md bg-card border border-border hover:bg-muted transition-colors z-10">
      <i data-lucide="sun" class="h-5 w-5 text-foreground hidden dark:block"></i>
      <i data-lucide="moon" class="h-5 w-5 text-foreground block dark:hidden"></i>
    </button>

    <div class="w-full max-w-sm rounded-lg border border-border bg-card p-6 shadow-lg">
      <div class="text-center mb-6">
        <div class="flex items-center justify-center mb-4">
          <i data-lucide="shield-check" class="h-8 w-8 text-primary mr-2"></i>
          <span class="text-xl font-bold text-card-foreground">Inn Nexus</span>
        </div>
        <h1 class="text-2xl font-bold text-card-foreground">Two-Factor Verification</h1>
        <p class="text-sm text-muted-foreground mt-2">
          Enter the 6-digit code sent to your email<br>
          <strong class="text-card-foreground"><?= htmlspecialchars($email) ?></strong>
        </p>
      </div>

      <?php if (!empty($error)): ?>
        <div class="mb-4 p-3 rounded-md bg-destructive/10 border border-destructive/20">
          <p class="text-destructive text-sm text-center"><?= htmlspecialchars($error) ?></p>
        </div>
      <?php endif; ?>

      <?php if (!empty($success)): ?>
        <div class="mb-4 p-3 rounded-md bg-success/10 border border-success/20">
          <p class="text-success text-sm text-center"><?= htmlspecialchars($success) ?></p>
        </div>
      <?php endif; ?>

      <form method="post" class="space-y-4">
        <input type="hidden" name="_action" value="verify_otp" />
        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>" />
        <div>
          <label class="block text-sm font-medium text-card-foreground mb-2">Verification Code</label>
          <input
            name="otp"
            type="text"
            required
            maxlength="6"
            pattern="[0-9]{6}"
            class="h-10 w-full rounded-md border border-border bg-background text-foreground px-3 text-sm text-center tracking-widest font-semibold focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-colors"
            placeholder="Enter 6-digit code"
          />
        </div>
        <button type="submit" class="w-full h-10 rounded-md bg-primary text-primary-foreground hover:bg-primary/90 transition-colors font-medium">
          Verify Code
        </button>
      </form>

      <form method="post" class="mt-6 text-center">
        <input type="hidden" name="_action" value="resend_otp" />
        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>" />
        <button type="submit" class="text-sm text-primary hover:text-primary/80 hover:underline transition-colors">Resend Code</button>
      </form>

      <div class="mt-4 text-center">
        <a href="logout.php" class="text-xs text-muted-foreground hover:text-foreground hover:underline transition-colors">Cancel login</a>
      </div>
    </div>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
      window.lucide && window.lucide.createIcons();

      // Theme toggle functionality
      document.addEventListener('DOMContentLoaded', function() {
        const themeToggle = document.getElementById('theme-toggle');
        
        if (themeToggle) {
          themeToggle.addEventListener('click', function() {
            const currentTheme = localStorage.getItem('theme') || 'light';
            const newTheme = currentTheme === 'light' ? 'dark' : 'light';
            
            // Update localStorage
            localStorage.setItem('theme', newTheme);
            
            // Update DOM classes
            document.documentElement.classList.toggle('dark', newTheme === 'dark');
            document.documentElement.classList.toggle('light-mode', newTheme === 'light');
            
            // Update icons
            const sunIcon = themeToggle.querySelector('[data-lucide="sun"]');
            const moonIcon = themeToggle.querySelector('[data-lucide="moon"]');
            
            if (newTheme === 'dark') {
              sunIcon.classList.remove('hidden');
              sunIcon.classList.add('block');
              moonIcon.classList.remove('block');
              moonIcon.classList.add('hidden');
            } else {
              sunIcon.classList.remove('block');
              sunIcon.classList.add('hidden');
              moonIcon.classList.remove('hidden');
              moonIcon.classList.add('block');
            }
          });
        }
      });
    </script>
  </body>
</html>
