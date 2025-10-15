<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Register - Core 1 PMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="./public/css/tokens.css" />
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
  </head>
  <body class="min-h-screen bg-background flex items-center justify-center">
    <?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/mailer.php';
initSession();

$error = '';
$success = '';

if (($_POST['_action'] ?? '') === 'register') {
  if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    $error = 'Invalid CSRF token.';
  } else {
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    $role = $_POST['role'] ?? 'receptionist';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 8) {
      $error = 'Password must be at least 8 characters long.';
    } elseif ($password !== $password_confirm) {
      $error = 'Passwords do not match.';
    } else {
      $pdo = getPdo();

      // Check if email already exists
      $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
      $stmt->execute([$email]);
      if ($stmt->fetch()) {
        $error = 'An account with this email already exists.';
      } else {
        // Hash password and insert user
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (email, password_hash, role, email_verified, created_at) VALUES (?, ?, ?, 1, NOW())");
        $insertResult = $stmt->execute([$email, $password_hash, $role]);

        if ($insertResult) {
          $success = 'Account created successfully! You can now log in.';
        } else {
          $error = 'Failed to create account. Please try again.';
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
    <title>Register - Inn Nexus Hotel Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="./public/css/tokens.css" />
  </head>

  <body class="min-h-screen bg-background flex items-center justify-center">
    <div class="w-full max-w-sm rounded-lg border bg-card p-6 shadow-sm">
      <h1 class="text-2xl font-bold mb-4 text-center">Create Account</h1>

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
      <?php else: ?>
        <form method="post" class="space-y-3">
          <input type="hidden" name="_action" value="register" />
          <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>" />

          <div>
            <label class="text-xs text-muted-foreground">Email</label>
            <input name="email" type="email" required
                   class="h-10 w-full rounded-md border bg-background px-3 text-sm"
                   placeholder="Enter your email address" />
          </div>

          <div>
            <label class="text-xs text-muted-foreground">Password</label>
            <input name="password" type="password" required minlength="8"
                   class="h-10 w-full rounded-md border bg-background px-3 text-sm"
                   placeholder="Create a password (min 8 characters)" />
          </div>

          <div>
            <label class="text-xs text-muted-foreground">Confirm Password</label>
            <input name="password_confirm" type="password" required minlength="8"
                   class="h-10 w-full rounded-md border bg-background px-3 text-sm"
                   placeholder="Confirm your password" />
          </div>

          <div>
            <label class="text-xs text-muted-foreground">Role</label>
            <select name="role" class="h-10 w-full rounded-md border bg-background px-3 text-sm">
              <option value="receptionist">Receptionist</option>
              <option value="staff">Staff</option>
              <option value="manager">Manager</option>
              <option value="admin">Administrator</option>
            </select>
          </div>

          <button class="w-full h-10 rounded-md bg-primary text-primary-foreground hover:bg-primary/90 transition">
            Create Account
          </button>
        </form>

        <div class="mt-4 text-center">
          <a href="login.php" class="text-sm text-blue-600 hover:underline">
            Already have an account? Sign in
          </a>
        </div>
      <?php endif; ?>
    </div>

    <script>
      // Confirm password validation
      document.querySelector('input[name="password_confirm"]').addEventListener('input', function() {
        const password = document.querySelector('input[name="password"]').value;
        const confirmPassword = this.value;

        if (confirmPassword && password !== confirmPassword) {
          this.setCustomValidity('Passwords do not match');
        } else {
          this.setCustomValidity('');
        }
      });
    </script>
  </body>
</html>
