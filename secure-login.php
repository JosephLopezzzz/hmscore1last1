<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Secure Login - Core 1 PMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="./public/css/tokens.css" />
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
  </head>
  <body class="min-h-screen bg-background flex items-center justify-center">
    <?php 
    require_once __DIR__ . '/includes/security.php';
    require_once __DIR__ . '/includes/auth.php';
    require_once __DIR__ . '/includes/db.php';
    
    // Enforce HTTPS
    $security = new SecurityManager(getPdo());
    $security->enforceHTTPS();
    
    $auth = new SecureAuth(getPdo());
    $error = '';
    $success = '';
    $step = $_GET['step'] ?? 'login';
    $requires2FA = false;
    
    // Handle login
    if (($_POST['_action'] ?? '') === 'login') {
        if (!$security->verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            $error = 'Invalid request. Please try again.';
        } else {
            // Verify reCAPTCHA
            if (!$security->verifyRecaptcha($_POST['g-recaptcha-response'] ?? '', $_SERVER['REMOTE_ADDR'] ?? '')) {
                $error = 'Please complete the reCAPTCHA verification.';
            } else {
                $result = $auth->login($_POST['email'] ?? '', $_POST['password'] ?? '', $_POST['totp_code'] ?? null);
                
                if ($result['success']) {
                    header('Location: index.php');
                    exit;
                } else {
                    $error = implode(', ', $result['errors']);
                    $requires2FA = $result['requires_2fa'] ?? false;
                }
            }
        }
    }
    
    // Handle 2FA verification
    if (($_POST['_action'] ?? '') === 'verify_2fa') {
        if (!$security->verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            $error = 'Invalid request. Please try again.';
        } else {
            $result = $auth->login($_POST['email'] ?? '', $_POST['password'] ?? '', $_POST['totp_code'] ?? '');
            
            if ($result['success']) {
                header('Location: index.php');
                exit;
            } else {
                $error = implode(', ', $result['errors']);
            }
        }
    }
    ?>
    
    <div class="w-full max-w-md rounded-lg border bg-card p-6 shadow-sm">
      <?php if ($step === 'login'): ?>
        <h1 class="text-2xl font-bold mb-4 text-center">Secure Login</h1>
        <?php if ($error): ?>
          <div class="p-3 rounded-md bg-destructive/10 text-destructive border border-destructive/20 mb-4">
            <?php echo htmlspecialchars($error); ?>
          </div>
        <?php endif; ?>
        <?php if ($success): ?>
          <div class="p-3 rounded-md bg-success/10 text-success border border-success/20 mb-4">
            <?php echo htmlspecialchars($success); ?>
          </div>
        <?php endif; ?>
        
        <form method="post" class="space-y-4">
          <input type="hidden" name="_action" value="login" />
          <input type="hidden" name="csrf_token" value="<?php echo $security->generateCSRFToken(); ?>" />
          
          <div>
            <label class="text-sm text-muted-foreground">Email Address</label>
            <input name="email" type="email" required 
                   class="h-10 w-full rounded-md border bg-background px-3 text-sm mt-1" 
                   placeholder="Enter your email" />
          </div>
          
          <div>
            <label class="text-sm text-muted-foreground">Password</label>
            <input name="password" type="password" required 
                   class="h-10 w-full rounded-md border bg-background px-3 text-sm mt-1" 
                   placeholder="Enter your password" />
          </div>
          
          <?php if ($requires2FA): ?>
            <div>
              <label class="text-sm text-muted-foreground">2FA Code</label>
              <input name="totp_code" type="text" maxlength="6" pattern="[0-9]{6}" 
                     class="h-10 w-full rounded-md border bg-background px-3 text-sm mt-1 text-center tracking-widest" 
                     placeholder="123456" />
            </div>
          <?php endif; ?>
          
          <div class="g-recaptcha" data-sitekey="<?php echo RECAPTCHA_SITE_KEY; ?>"></div>
          
          <button type="submit" class="w-full h-10 rounded-md bg-primary text-primary-foreground">
            <?php echo $requires2FA ? 'Verify & Login' : 'Login'; ?>
          </button>
        </form>
        
        <div class="text-center mt-4 space-y-2">
          <a href="register.php" class="text-sm text-muted-foreground hover:text-foreground">
            Don't have an account? Register
          </a>
          <br>
          <a href="forgot-password.php" class="text-sm text-muted-foreground hover:text-foreground">
            Forgot your password?
          </a>
        </div>
        
      <?php endif; ?>
    </div>
    
    <script>
      // Auto-focus on first input
      document.addEventListener('DOMContentLoaded', function() {
        const firstInput = document.querySelector('input[type="email"]');
        if (firstInput) {
          firstInput.focus();
        }
      });
      
      // Form validation
      document.querySelector('form').addEventListener('submit', function(e) {
        const email = document.querySelector('input[name="email"]').value;
        const password = document.querySelector('input[name="password"]').value;
        
        if (!email || !password) {
          e.preventDefault();
          alert('Please fill in all required fields.');
          return;
        }
        
        if (password.length < 8) {
          e.preventDefault();
          alert('Password must be at least 8 characters long.');
          return;
        }
      });
    </script>
  </body>
</html>
