<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
initSession();

$token = $_GET['token'] ?? '';
$error = '';
$success = '';

if (empty($token)) {
  $error = 'Invalid or missing password reset token.';
} else {
  $pdo = getPdo();

  // Find the token in password_reset_tokens table (check if not expired using UTC)
  $currentTime = gmdate('Y-m-d H:i:s');
  $stmt = $pdo->prepare("
    SELECT prt.id, prt.user_id, prt.expires_at, u.email
    FROM password_reset_tokens prt
    JOIN users u ON prt.user_id = u.id
    WHERE prt.token = ? AND prt.used_at IS NULL AND prt.expires_at > ?
  ");
  $stmt->execute([$token, $currentTime]);
  $reset_request = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$reset_request) {
    $error = 'Invalid or expired password reset link.';
  } else {
    // Handle password reset form
    if (($_POST['_action'] ?? '') === 'reset_password') {
      if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid CSRF token.';
      } else {
        $newPassword = $_POST['password'] ?? '';
        $confirm = $_POST['confirm'] ?? '';

        if (empty($newPassword) || empty($confirm)) {
          $error = 'Please fill in all fields.';
        } elseif ($newPassword !== $confirm) {
          $error = 'Passwords do not match.';
        } elseif (strlen($newPassword) < 8) {
          $error = 'Password must be at least 8 characters long.';
        } else {
          // Hash new password and mark token as used
          $hash = password_hash($newPassword, PASSWORD_DEFAULT);
          $pdo->beginTransaction();
          try {
            // Update user password
            $update = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            $update->execute([$hash, $reset_request['user_id']]);

            // Mark token as used
            $update = $pdo->prepare("UPDATE password_reset_tokens SET used_at = NOW() WHERE id = ?");
            $update->execute([$reset_request['id']]);

            $pdo->commit();
            $success = 'Your password has been reset successfully. You can now log in.';
          } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Failed to reset password. Please try again.';
          }
        }
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
    <title>Reset Password - Inn Nexus Hotel Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="./public/css/tokens.css" />
  </head>

  <body class="min-h-screen bg-background flex items-center justify-center">
    <div class="w-full max-w-sm rounded-lg border bg-card p-6 shadow-sm">
      <h1 class="text-2xl font-bold mb-4 text-center">Reset Password</h1>

      <?php if (!empty($error)): ?>
        <p class="text-red-500 text-sm mb-3 text-center"><?= htmlspecialchars($error) ?></p>
      <?php endif; ?>

      <?php if (!empty($success)): ?>
        <div class="p-3 rounded-md bg-green-50 text-green-700 border border-green-200 mb-4 text-center">
          <?= htmlspecialchars($success) ?>
        </div>
        <div class="text-center">
          <a href="login.php" class="inline-flex items-center rounded-md bg-primary text-primary-foreground px-4 py-2 text-sm">
            Go to Login
          </a>
        </div>
      <?php endif; ?>

      <?php if (empty($success) && $reset_request ?? false): ?>
        <form method="post" class="space-y-3">
          <input type="hidden" name="_action" value="reset_password" />
          <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>" />

          <div>
            <label class="text-xs text-muted-foreground">New Password</label>
            <input name="password" type="password" required minlength="8"
                   class="h-10 w-full rounded-md border bg-background px-3 text-sm"
                   placeholder="Enter new password (min 8 characters)" />
          </div>

          <div>
            <label class="text-xs text-muted-foreground">Confirm Password</label>
            <input name="confirm" type="password" required minlength="8"
                   class="h-10 w-full rounded-md border bg-background px-3 text-sm"
                   placeholder="Confirm new password" />
          </div>

          <button class="w-full h-10 rounded-md bg-primary text-primary-foreground hover:bg-primary/90 transition">
            Update Password
          </button>
        </form>
      <?php endif; ?>
    </div>
  </body>
</html>
