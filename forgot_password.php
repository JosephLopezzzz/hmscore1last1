<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/mailer.php';

initSession();

$error = '';
$success = '';

if (($_POST['_action'] ?? '') === 'forgot_password') {
  if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    $error = 'Invalid CSRF token.';
  } else {
    $email = sanitizeInput($_POST['email'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $error = 'Please enter a valid email address.';
    } else {
      $pdo = getPdo();

      // Check if user exists and is active
      $stmt = $pdo->prepare("SELECT id, email, is_active FROM users WHERE email = ?");
      $stmt->execute([$email]);
      $user = $stmt->fetch(PDO::FETCH_ASSOC);

      if ($user && $user['is_active']) {
        // Generate reset token and expiry (1 hour from now in UTC)
        $token = bin2hex(random_bytes(32));
        $expires = gmdate('Y-m-d H:i:s', time() + 3600);

        // Insert token into password_reset_tokens table
        $insert = $pdo->prepare("INSERT INTO password_reset_tokens (user_id, token, expires_at) VALUES (?, ?, ?)");
        $insert->execute([$user['id'], $token, $expires]);

        // Prepare reset link
        $resetLink = "http://localhost/hmscore1last1/reset_password.php?token=" . urlencode($token);

        $subject = "Password Reset Request - Inn Nexus";
        $bodyHtml = "
          <h2>Inn Nexus Password Reset</h2>
          <p>Hello,</p>
          <p>We received a request to reset your password. Click the link below to set a new password:</p>
          <p><a href=\"$resetLink\" style=\"background-color: #3b82f6; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;\">Reset Password</a></p>
          <p>This link will expire in 1 hour.</p>
          <br><p>If you didn't request this, please ignore this message.</p>
          <p>— Inn Nexus Security</p>
        ";
        $bodyText = "Password reset request for Inn Nexus. Click this link to reset your password: $resetLink (expires in 1 hour)";

        $sent = sendMail($email, 'Inn Nexus User', $subject, $bodyHtml, $bodyText);

        if ($sent) {
          $success = 'A password reset link has been sent to your email.';
        } else {
          $error = 'Failed to send reset email. Please check your mailer configuration.';
        }
      } else {
        $error = 'No active account found with that email address.';
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
    <title>Forgot Password - Inn Nexus Hotel Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="./public/css/tokens.css" />
  </head>

  <body class="min-h-screen bg-background flex items-center justify-center">
    <div class="w-full max-w-sm rounded-lg border bg-card p-6 shadow-sm">
      <h1 class="text-2xl font-bold mb-4 text-center">Forgot Password</h1>

      <?php if (!empty($error)): ?>
        <p class="text-red-500 text-sm mb-3 text-center"><?= htmlspecialchars($error) ?></p>
      <?php endif; ?>

      <?php if (!empty($success)): ?>
        <div class="p-3 rounded-md bg-green-50 text-green-700 border border-green-200 mb-4 text-center">
          <?= htmlspecialchars($success) ?>
        </div>
        <div class="text-center">
          <a href="login.php" class="inline-flex items-center rounded-md bg-primary text-primary-foreground px-4 py-2 text-sm">
            Back to Login
          </a>
        </div>
      <?php else: ?>
        <form method="post" class="space-y-3">
          <input type="hidden" name="_action" value="forgot_password" />
          <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>" />

          <div>
            <label class="text-xs text-muted-foreground">Email</label>
            <input name="email" type="email" required
                   class="h-10 w-full rounded-md border bg-background px-3 text-sm"
                   placeholder="Enter your registered email address" />
          </div>

          <button class="w-full h-10 rounded-md bg-primary text-primary-foreground hover:bg-primary/90 transition">
            Send Reset Link
          </button>
        </form>

        <div class="mt-4 text-center">
          <a href="login.php" class="text-sm text-blue-600 hover:underline">
            Back to Login
          </a>
        </div>
      <?php endif; ?>
    </div>
  </body>
</html>
