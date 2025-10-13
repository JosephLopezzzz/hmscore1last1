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
    <title>Setup 2FA - Inn Nexus Hotel Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="./public/css/tokens.css" />
    <script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js"></script>
  </head>
  <body class="min-h-screen bg-background flex items-center justify-center">
    <?php require_once __DIR__ . '/includes/db.php'; 
    requireAuth(['admin','receptionist']);
    initSession();
    $userId = $_SESSION['user_id'] ?? null;
    $email = currentUserEmail() ?? '';
    $error = '';
    $success = '';
    
    if (($_POST['_action'] ?? '') === 'setup_2fa') {
      if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request';
      } else {
        $secret = $_POST['secret'] ?? '';
        $code = $_POST['code'] ?? '';
        if (verifyTOTPCode($secret, $code)) {
          if (setup2FA($userId, $secret)) {
            $success = '2FA enabled successfully!';
          } else {
            $error = 'Failed to enable 2FA';
          }
        } else {
          $error = 'Invalid verification code';
        }
      }
    }
    
    $secret = generateSecretKey();
    $qrUrl = generateQRCodeUrl($email, $secret);
    ?>
    
    <div class="w-full max-w-md rounded-lg border bg-card p-6 shadow-sm">
      <h1 class="text-2xl font-bold mb-4 text-center">Setup Two-Factor Authentication</h1>
      
      <?php if ($success): ?>
        <div class="p-3 rounded-md bg-success/10 text-success border border-success/20 mb-4">
          <?php echo $success; ?>
        </div>
        <div class="text-center">
          <a href="index.php" class="inline-flex items-center rounded-md bg-primary text-primary-foreground px-4 py-2">
            Continue to Dashboard
          </a>
        </div>
      <?php else: ?>
        <?php if ($error): ?>
          <div class="p-3 rounded-md bg-destructive/10 text-destructive border border-destructive/20 mb-4">
            <?php echo $error; ?>
          </div>
        <?php endif; ?>
        
        <div class="space-y-4">
          <div class="text-center">
            <p class="text-sm text-muted-foreground mb-2">Scan this QR code with your authenticator app:</p>
            <div id="qrcode" class="flex justify-center mb-4"></div>
            <p class="text-xs text-muted-foreground">Or enter this secret key manually:</p>
            <code class="block bg-muted p-2 rounded text-xs break-all"><?php echo $secret; ?></code>
          </div>
          
          <form method="post" class="space-y-3">
            <input type="hidden" name="_action" value="setup_2fa" />
            <input type="hidden" name="secret" value="<?php echo $secret; ?>" />
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>" />
            
            <div>
              <label class="text-xs text-muted-foreground">Enter 6-digit code from your app</label>
              <input name="code" type="text" maxlength="6" pattern="[0-9]{6}" required 
                     class="h-10 w-full rounded-md border bg-background px-3 text-sm text-center tracking-widest" 
                     placeholder="123456" />
            </div>
            
            <button class="w-full h-10 rounded-md bg-primary text-primary-foreground">
              Enable 2FA
            </button>
          </form>
        </div>
      <?php endif; ?>
    </div>
    
    <script>
      // Generate QR code
      const qrUrl = '<?php echo $qrUrl; ?>';
      QRCode.toCanvas(document.getElementById('qrcode'), qrUrl, {
        width: 200,
        height: 200,
        color: {
          dark: '#000000',
          light: '#FFFFFF'
        }
      });
    </script>
  </body>
</html>
