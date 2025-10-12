<?php
session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/security.php';

// Check if user is in 2FA verification state
if (!isset($_SESSION['2fa_required']) || !$_SESSION['2fa_required']) {
    header('Location: login.php');
    exit;
}

// Get user email for display
$userEmail = $_SESSION['temp_email'] ?? 'your email';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    
    if (!verifyCSRFToken($csrfToken)) {
        $error = 'Invalid security token. Please try again.';
    } else {
        $code = '';
        for ($i = 1; $i <= 6; $i++) {
            $code .= $_POST["code_{$i}"] ?? '';
        }
        
        if (strlen($code) !== 6 || !ctype_digit($code)) {
            $error = 'Please enter a valid 6-digit code';
        } else {
            // Verify the 2FA code
            $userId = $_SESSION['temp_user_id'] ?? null;
            if ($userId && verifyTOTPCode($userId, $code)) {
                // 2FA successful - complete login
                $_SESSION['user_id'] = $userId;
                $_SESSION['user_email'] = $_SESSION['temp_email'];
                $_SESSION['user_role'] = $_SESSION['temp_role'];
                $_SESSION['2fa_verified'] = true;
                
                // Clear temporary 2FA data
                unset($_SESSION['2fa_required']);
                unset($_SESSION['temp_user_id']);
                unset($_SESSION['temp_email']);
                unset($_SESSION['temp_role']);
                
                // 2FA verification successful
                // Redirect to dashboard
                header('Location: index.php');
                exit;
            } else {
                $error = 'Invalid verification code. Please try again.';
            }
        }
    }
}

// Handle resend code
if (isset($_POST['resend_code'])) {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (verifyCSRFToken($csrfToken)) {
        // Generate new code and send (in a real app, you'd send via email/SMS)
        $userId = $_SESSION['temp_user_id'] ?? null;
        if ($userId) {
            // For demo purposes, we'll just log this
            logSecurityEvent($userId, '2FA_CODE_RESENT', 'User requested new 2FA code');
            $resendSuccess = 'New verification code has been sent to your email.';
        }
    }
}

$csrfToken = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <!-- Theme initialization (must be first to prevent flash) -->
    <script>
      (function() {
        const theme = localStorage.getItem('theme') || 'light';
        document.documentElement.classList.toggle('dark', theme === 'dark');
      })();
    </script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Primary Meta Tags -->
    <title>Two-Factor Authentication - Inn Nexus Hotel Management System</title>
    <meta name="title" content="Two-Factor Authentication - Inn Nexus Hotel Management System" />
    <meta name="description" content="Secure two-factor authentication for Inn Nexus hotel management system. Enter your 6-digit verification code to access your account." />
    <meta name="keywords" content="2FA, two factor authentication, hotel security, secure login, verification code" />
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
    <style>
        .code-input {
            transition: all 0.2s ease-in-out;
        }
        .code-input:focus {
            transform: scale(1.05);
        }
        .fade-in {
            animation: fadeIn 0.5s ease-in-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .slide-up {
            animation: slideUp 0.3s ease-in-out;
        }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body class="min-h-screen bg-gray-900 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
    <div class="fade-in sm:mx-auto sm:w-full sm:max-w-md">
        <div class="flex justify-center">
            <svg class="w-12 h-12 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
            </svg>
        </div>
        <h2 class="mt-6 text-center text-3xl font-extrabold text-white">
            Two-Factor Authentication
        </h2>
        <p class="mt-2 text-center text-sm text-gray-400">
            We've sent a verification code to <?php echo htmlspecialchars($userEmail); ?>
        </p>
    </div>

    <div class="slide-up mt-8 sm:mx-auto sm:w-full sm:max-w-md">
        <div class="bg-gray-800 py-8 px-4 shadow sm:rounded-lg sm:px-10">
            <?php if (isset($error)): ?>
                <div class="mb-4 p-3 bg-red-900/20 border border-red-500/50 rounded-md">
                    <p class="text-sm text-red-400 text-center"><?php echo htmlspecialchars($error); ?></p>
                </div>
            <?php endif; ?>

            <?php if (isset($resendSuccess)): ?>
                <div class="mb-4 p-3 bg-green-900/20 border border-green-500/50 rounded-md">
                    <p class="text-sm text-green-400 text-center"><?php echo htmlspecialchars($resendSuccess); ?></p>
                </div>
            <?php endif; ?>

            <form class="space-y-6" method="POST" id="verifyForm">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                
                <div>
                    <div class="flex justify-center space-x-2">
                        <?php for ($i = 1; $i <= 6; $i++): ?>
                            <input
                                type="text"
                                name="code_<?php echo $i; ?>"
                                id="code_<?php echo $i; ?>"
                                maxlength="1"
                                class="code-input w-12 h-12 text-center text-2xl font-semibold bg-gray-700 border border-gray-600 rounded-md text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                autocomplete="off"
                                <?php echo $i === 1 ? 'autofocus' : ''; ?>
                                oninput="handleInput(this, <?php echo $i; ?>)"
                                onkeydown="handleKeyDown(event, <?php echo $i; ?>)"
                            />
                        <?php endfor; ?>
                    </div>
                </div>

                <div>
                    <button
                        type="submit"
                        id="verifyBtn"
                        class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                        disabled
                    >
                        <span id="btnText">Verify Code</span>
                        <span id="btnSpinner" class="hidden">
                            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Verifying...
                        </span>
                    </button>
                </div>
            </form>

            <div class="mt-6">
                <div class="relative">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-gray-600"></div>
                    </div>
                    <div class="relative flex justify-center text-sm">
                        <span class="px-2 bg-gray-800 text-gray-400">
                            Having trouble?
                        </span>
                    </div>
                </div>

                <div class="mt-6 text-center">
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                        <button
                            type="submit"
                            name="resend_code"
                            id="resendBtn"
                            class="text-sm font-medium text-blue-400 hover:text-blue-300"
                        >
                            Resend Code
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        let resendCooldown = 30;
        let cooldownTimer;

        function handleInput(input, index) {
            // Only allow numbers
            if (!/^\d*$/.test(input.value)) {
                input.value = '';
                return;
            }

            // Auto-focus next input
            if (input.value && index < 6) {
                document.getElementById(`code_${index + 1}`).focus();
            }

            checkFormComplete();
        }

        function handleKeyDown(event, index) {
            // Handle backspace
            if (event.key === 'Backspace' && !event.target.value && index > 1) {
                document.getElementById(`code_${index - 1}`).focus();
            }
        }

        function checkFormComplete() {
            const inputs = document.querySelectorAll('input[name^="code_"]');
            const allFilled = Array.from(inputs).every(input => input.value);
            const verifyBtn = document.getElementById('verifyBtn');
            verifyBtn.disabled = !allFilled;
        }

        function startResendCooldown() {
            const resendBtn = document.getElementById('resendBtn');
            resendBtn.disabled = true;
            resendBtn.textContent = `Resend code in ${resendCooldown}s`;
            
            cooldownTimer = setInterval(() => {
                resendCooldown--;
                if (resendCooldown <= 0) {
                    clearInterval(cooldownTimer);
                    resendBtn.disabled = false;
                    resendBtn.textContent = 'Resend Code';
                    resendCooldown = 30;
                } else {
                    resendBtn.textContent = `Resend code in ${resendCooldown}s`;
                }
            }, 1000);
        }

        // Handle form submission
        document.getElementById('verifyForm').addEventListener('submit', function(e) {
            const btnText = document.getElementById('btnText');
            const btnSpinner = document.getElementById('btnSpinner');
            const verifyBtn = document.getElementById('verifyBtn');
            
            btnText.classList.add('hidden');
            btnSpinner.classList.remove('hidden');
            verifyBtn.disabled = true;
        });

        // Start resend cooldown if page was reloaded after resend
        <?php if (isset($resendSuccess)): ?>
            startResendCooldown();
        <?php endif; ?>
    </script>
</body>
</html>
