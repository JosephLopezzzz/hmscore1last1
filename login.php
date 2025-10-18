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
            $mail->Subject = 'Your Core 1 2FA Verification Code';
            $mail->Body    = "
              <h2>Core 1 Login Verification</h2>
              <p>Your 6-digit verification code is:</p>
              <h1 style='font-size:24px; letter-spacing:4px; color: #3b82f6;'>$otp</h1>
              <p>This code will expire in 5 minutes.</p>
              <p>If you didn't request this, please ignore this email.</p>
              <hr>
              <p><small>Core 1 Hotel Management System</small></p>
            ";
            $mail->AltBody = "Your Core 1 verification code is: $otp (expires in 5 minutes).";

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
    <title>Login - Core 1 Hotel Management System</title>
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
          <i data-lucide="hotel" class="h-8 w-8 text-primary mr-2"></i>
          <span class="text-xl font-bold text-card-foreground">Core 1</span>
        </div>
        <h1 class="text-2xl font-bold text-card-foreground">Sign in</h1>
        <p class="text-sm text-muted-foreground mt-2">Welcome back to your dashboard</p>
      </div>

      <?php if (!empty($error)): ?>
        <div class="mb-4 p-3 rounded-md bg-destructive/10 border border-destructive/20">
          <p class="text-destructive text-sm text-center"><?= htmlspecialchars($error) ?></p>
        </div>
      <?php endif; ?>

      <form method="post" class="space-y-4">
        <input type="hidden" name="_action" value="login" />
        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>" />

        <div>
          <label class="block text-sm font-medium text-card-foreground mb-2">Email</label>
          <input name="email" type="email" required
                 class="h-10 w-full rounded-md border border-border bg-background text-foreground px-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-colors" />
        </div>

        <div>
          <label class="block text-sm font-medium text-card-foreground mb-2">Password</label>
          <input name="password" type="password" required
                 class="h-10 w-full rounded-md border border-border bg-background text-foreground px-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-colors" />
        </div>

        <button class="w-full h-10 rounded-md bg-primary text-primary-foreground hover:bg-primary/90 transition-colors font-medium">
          Sign In
        </button>
      </form>

      <div class="mt-6 text-center">
        <a href="forgot_password.php" class="text-sm text-primary hover:text-primary/80 hover:underline transition-colors">
          Forgot password?
        </a>
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
