<!doctype html>
<html lang="en" class="">
  <head>
    <!-- Theme initialization (must be first to prevent flash) -->
    <script>
      (function() {
        const theme = localStorage.getItem('theme') || 'light';
        document.documentElement.classList.toggle('dark', theme === 'dark');
      })();
    </script>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    
    <!-- Primary Meta Tags -->
    <title>Login - Inn Nexus Hotel Management System</title>
    <meta name="title" content="Login - Inn Nexus Hotel Management System" />
    <meta name="description" content="Secure login to Inn Nexus hotel management system. Access your hotel operations dashboard with advanced security features." />
    <meta name="keywords" content="hotel login, PMS login, hotel management login, secure access, hospitality software" />
    <meta name="author" content="Inn Nexus Team" />
    <meta name="robots" content="noindex, nofollow" />
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="./public/favicon.svg" />
    <link rel="icon" type="image/png" href="./public/favicon.ico" />
    <link rel="apple-touch-icon" href="./public/favicon.svg" />
    
    <!-- Theme Color -->
    <meta name="theme-color" content="#3b82f6" />
    
    <!-- Stylesheets -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="./public/css/tokens.css" />
    
    <!-- Security -->
    <meta http-equiv="X-Content-Type-Options" content="nosniff" />
    <meta http-equiv="X-Frame-Options" content="DENY" />
    <meta http-equiv="X-XSS-Protection" content="1; mode=block" />
  </head>
  <body class="min-h-screen bg-background flex items-center justify-center relative">
    <?php require_once __DIR__ . '/includes/db.php'; initSession(); ensureDefaultAdmin(); ?>
    <?php
      $error = '';
      
      if (($_POST['_action'] ?? '') === 'login') {
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
          $error = 'Invalid request';
        } else {
          // Sanitize and validate inputs
          $email = sanitizeInput($_POST['email'] ?? '');
          $password = $_POST['password'] ?? '';
          
          // Validate email format
          if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address';
          } else {
            // Validate password strength (optional - you can remove this if you want to allow any password)
            $passwordErrors = validatePassword($password);
            if (!empty($passwordErrors)) {
              $error = 'Password does not meet security requirements: ' . implode(', ', $passwordErrors);
            } else {
              $ok = verifyLogin($email, $password);
              if ($ok) {
                $userId = $_SESSION['user_id'] ?? null;
                if ($userId && is2FAEnabled($userId)) {
                  // Set up 2FA verification state
                  $_SESSION['2fa_required'] = true;
                  $_SESSION['temp_user_id'] = $userId;
                  $_SESSION['temp_email'] = $email;
                  $_SESSION['temp_role'] = $_SESSION['user_role'] ?? 'user';
                  
                  // Clear the regular session data until 2FA is verified
                  unset($_SESSION['user_id']);
                  unset($_SESSION['user_role']);
                  
                  header('Location: verify-2fa.php');
                  exit;
                } else {
                  header('Location: index.php');
                  exit;
                }
              } else {
                $error = 'Invalid credentials';
              }
            }
          }
        }
      }
      
    ?>
    <!-- Theme toggle (top-right) -->
    <button id="theme-toggle" type="button" aria-label="Toggle theme" class="absolute top-4 right-4 h-9 w-9 rounded-md border border-border bg-background text-foreground hover:bg-muted inline-flex items-center justify-center">
      <i data-lucide="sun" class="h-4 w-4 icon-sun"></i>
      <i data-lucide="moon" class="h-4 w-4 icon-moon" style="display:none"></i>
    </button>

    <div class="w-full max-w-sm rounded-lg border bg-card p-6 shadow-sm">
      <h1 class="text-2xl font-bold mb-4 text-center">Sign in</h1>
      <?php if (!empty($error)): ?><p class="text-destructive text-sm mb-3"><?php echo $error; ?></p><?php endif; ?>
      <form method="post" class="space-y-3">
        <input type="hidden" name="_action" value="login" />
        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>" />
        <div>
          <label class="text-xs text-muted-foreground">Email</label>
          <input name="email" type="email" required class="h-10 w-full rounded-md border bg-background px-3 text-sm" />
        </div>
        <div>
          <label class="text-xs text-muted-foreground">Password</label>
          <input name="password" type="password" required class="h-10 w-full rounded-md border bg-background px-3 text-sm" />
        </div>
        <button class="w-full h-10 rounded-md bg-primary text-primary-foreground">Login</button>
      </form>
    </div>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        if (window.lucide) window.lucide.createIcons();
        const toggle = document.getElementById('theme-toggle');
        const html = document.documentElement;
        const sun = () => toggle?.querySelector('.icon-sun');
        const moon = () => toggle?.querySelector('.icon-moon');

        function applyIconState(){
          const isDark = html.classList.contains('dark');
          if (sun()) sun().style.display = isDark ? 'none' : 'inline';
          if (moon()) moon().style.display = isDark ? 'inline' : 'none';
        }

        // Set icon state initially (after reading localStorage in <head>)
        applyIconState();

        if (toggle) {
          toggle.addEventListener('click', function(e) {
            e.preventDefault();
            const isDark = html.classList.contains('dark');
            const newTheme = isDark ? 'light' : 'dark';
            html.classList.toggle('dark', !isDark);
            localStorage.setItem('theme', newTheme);
            applyIconState();
          });
        }
      });
    </script>
  </body>
  </html>


