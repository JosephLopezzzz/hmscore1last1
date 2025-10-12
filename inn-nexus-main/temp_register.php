<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/security.php';

initSession();

$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // Basic validation
    if (empty($email) || empty($password) || empty($confirmPassword)) {
        $error = 'All fields are required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match';
    } else {
        // Check if user already exists
        $pdo = getPdo();
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email');
        $stmt->execute([':email' => $email]);
        
        if ($stmt->fetch()) {
            $error = 'User with this email already exists';
        } else {
            // Create new admin user
            $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            $twoFactorSecret = 'JBSWY3DPEHPK3PXP'; // Default 2FA secret for Google Authenticator
            
            $stmt = $pdo->prepare('INSERT INTO users (email, password_hash, role, is_active, email_verified, two_factor_enabled, two_factor_secret) VALUES (:email, :hash, :role, 1, 1, 1, :secret)');
            $result = $stmt->execute([
                ':email' => $email,
                ':hash' => $hash,
                ':role' => 'admin',
                ':secret' => $twoFactorSecret
            ]);
            
            if ($result) {
                $success = true;
                $userId = $pdo->lastInsertId();
                
                // Log the user in
                $_SESSION['user_id'] = $userId;
                $_SESSION['user_role'] = 'admin';
                $_SESSION['user_email'] = $email;
                
                // Redirect to 2FA setup if enabled, otherwise to dashboard
                if (is2FAEnabled($userId)) {
                    header('Location: verify-2fa.php');
                } else {
                    header('Location: index.php');
                }
                exit;
            } else {
                $error = 'Failed to create user. Please try again.';
            }
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Admin Account - Inn Nexus</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">
    <div class="bg-white p-8 rounded-lg shadow-md w-full max-w-md">
        <h1 class="text-2xl font-bold text-center mb-6">Create Admin Account</h1>
        
        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                Account created successfully! Redirecting...
            </div>
        <?php endif; ?>
        
        <form method="POST" class="space-y-4">
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" id="email" name="email" required 
                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            </div>
            
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                <input type="password" id="password" name="password" required 
                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
            
            <div>
                <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required 
                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
            
            <div class="pt-2">
                <button type="submit" class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    Create Admin Account
                </button>
            </div>
        </form>
        
        <div class="mt-4 text-center text-sm text-gray-600">
            <p>Already have an account? <a href="login.php" class="text-blue-600 hover:underline">Log in here</a></p>
            <p class="mt-2 text-xs text-gray-500">Note: This is a temporary registration page. Please delete this file after use.</p>
        </div>
    </div>
</body>
</html>
