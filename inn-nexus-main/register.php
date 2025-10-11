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
    require_once __DIR__ . '/includes/security.php';
    require_once __DIR__ . '/includes/auth.php';
    require_once __DIR__ . '/includes/db.php';
    
    // Enforce HTTPS
    $security = new SecurityManager(getPdo());
    $security->enforceHTTPS();
    
    $auth = new SecureAuth(getPdo());
    $error = '';
    $success = '';
    
    // Handle registration
    if (($_POST['_action'] ?? '') === 'register') {
        if (!$security->verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            $error = 'Invalid request. Please try again.';
        } else {
            // Verify reCAPTCHA
            if (!$security->verifyRecaptcha($_POST['g-recaptcha-response'] ?? '', $_SERVER['REMOTE_ADDR'] ?? '')) {
                $error = 'Please complete the reCAPTCHA verification.';
            } else {
                $result = $auth->registerUser([
                    'name' => $_POST['name'] ?? '',
                    'email' => $_POST['email'] ?? '',
                    'password' => $_POST['password'] ?? '',
                    'role' => $_POST['role'] ?? 'receptionist'
                ]);
                
                if ($result['success']) {
                    $success = $result['message'];
                } else {
                    $error = implode(', ', $result['errors']);
                }
            }
        }
    }
    ?>
    
    <div class="w-full max-w-md rounded-lg border bg-card p-6 shadow-sm">
      <h1 class="text-2xl font-bold mb-4 text-center">Create Account</h1>
      
      <?php if ($error): ?>
        <div class="p-3 rounded-md bg-destructive/10 text-destructive border border-destructive/20 mb-4">
          <?php echo htmlspecialchars($error); ?>
        </div>
      <?php endif; ?>
      
      <?php if ($success): ?>
        <div class="p-3 rounded-md bg-success/10 text-success border border-success/20 mb-4">
          <?php echo htmlspecialchars($success); ?>
        </div>
        <div class="text-center">
          <a href="secure-login.php" class="inline-flex items-center rounded-md bg-primary text-primary-foreground px-4 py-2">
            Go to Login
          </a>
        </div>
      <?php else: ?>
        <form method="post" class="space-y-4">
          <input type="hidden" name="_action" value="register" />
          <input type="hidden" name="csrf_token" value="<?php echo $security->generateCSRFToken(); ?>" />
          
          <div>
            <label class="text-sm text-muted-foreground">Full Name</label>
            <input name="name" type="text" required 
                   class="h-10 w-full rounded-md border bg-background px-3 text-sm mt-1" 
                   placeholder="Enter your full name" />
          </div>
          
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
                   placeholder="Create a strong password" />
            <div class="text-xs text-muted-foreground mt-1">
              Must be at least 8 characters with uppercase, lowercase, number, and special character
            </div>
          </div>
          
          <div>
            <label class="text-sm text-muted-foreground">Confirm Password</label>
            <input name="password_confirm" type="password" required 
                   class="h-10 w-full rounded-md border bg-background px-3 text-sm mt-1" 
                   placeholder="Confirm your password" />
          </div>
          
          <div>
            <label class="text-sm text-muted-foreground">Role</label>
            <select name="role" class="h-10 w-full rounded-md border bg-background px-3 text-sm mt-1">
              <option value="receptionist">Receptionist</option>
              <option value="admin">Administrator</option>
            </select>
          </div>
          
          <div class="g-recaptcha" data-sitekey="<?php echo RECAPTCHA_SITE_KEY; ?>"></div>
          
          <button type="submit" class="w-full h-10 rounded-md bg-primary text-primary-foreground">
            Create Account
          </button>
        </form>
        
        <div class="text-center mt-4">
          <a href="secure-login.php" class="text-sm text-muted-foreground hover:text-foreground">
            Already have an account? Login
          </a>
        </div>
      <?php endif; ?>
    </div>
    
    <script>
      // Password strength indicator
      document.querySelector('input[name="password"]').addEventListener('input', function() {
        const password = this.value;
        const strength = calculatePasswordStrength(password);
        updatePasswordStrengthIndicator(strength);
      });
      
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
      
      function calculatePasswordStrength(password) {
        let score = 0;
        if (password.length >= 8) score++;
        if (/[A-Z]/.test(password)) score++;
        if (/[a-z]/.test(password)) score++;
        if (/[0-9]/.test(password)) score++;
        if (/[^A-Za-z0-9]/.test(password)) score++;
        return score;
      }
      
      function updatePasswordStrengthIndicator(strength) {
        // This would update a visual strength indicator
        console.log('Password strength:', strength);
      }
      
      // Form validation
      document.querySelector('form').addEventListener('submit', function(e) {
        const password = document.querySelector('input[name="password"]').value;
        const confirmPassword = document.querySelector('input[name="password_confirm"]').value;
        
        if (password !== confirmPassword) {
          e.preventDefault();
          alert('Passwords do not match.');
          return;
        }
        
        const strength = calculatePasswordStrength(password);
        if (strength < 4) {
          e.preventDefault();
          alert('Password is too weak. Please use a stronger password.');
          return;
        }
      });
    </script>
  </body>
</html>
