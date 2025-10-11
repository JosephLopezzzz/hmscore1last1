<?php
// Debug script to test 2FA functionality
require_once __DIR__ . '/includes/db.php';

echo "<h2>2FA Debug Information</h2>";

// Test database connection
$pdo = getPdo();
if (!$pdo) {
    echo "<p style='color: red;'>❌ Database connection failed</p>";
    exit;
}
echo "<p style='color: green;'>✅ Database connection successful</p>";

// Check if test user exists
$stmt = $pdo->prepare('SELECT id, email, role, two_factor_enabled, totp_enabled, two_factor_secret, totp_secret FROM users WHERE email = ?');
$stmt->execute(['test@example.com']);
$user = $stmt->fetch();

if (!$user) {
    echo "<p style='color: red;'>❌ Test user not found</p>";
    echo "<p>Please run the test user creation script first.</p>";
    exit;
}

echo "<h3>Test User Information:</h3>";
echo "<ul>";
echo "<li><strong>ID:</strong> " . htmlspecialchars($user['id']) . "</li>";
echo "<li><strong>Email:</strong> " . htmlspecialchars($user['email']) . "</li>";
echo "<li><strong>Role:</strong> " . htmlspecialchars($user['role']) . "</li>";
echo "<li><strong>two_factor_enabled:</strong> " . ($user['two_factor_enabled'] ? 'Yes' : 'No') . "</li>";
echo "<li><strong>totp_enabled:</strong> " . ($user['totp_enabled'] ? 'Yes' : 'No') . "</li>";
echo "<li><strong>two_factor_secret:</strong> " . htmlspecialchars($user['two_factor_secret'] ?? 'NULL') . "</li>";
echo "<li><strong>totp_secret:</strong> " . htmlspecialchars($user['totp_secret'] ?? 'NULL') . "</li>";
echo "</ul>";

// Test is2FAEnabled function
$is2FAEnabled = is2FAEnabled($user['id']);
echo "<p><strong>is2FAEnabled() result:</strong> " . ($is2FAEnabled ? '✅ Yes' : '❌ No') . "</p>";

// Test get2FASecret function
$secret = get2FASecret($user['id']);
echo "<p><strong>get2FASecret() result:</strong> " . ($secret ? '✅ ' . htmlspecialchars($secret) : '❌ NULL') . "</p>";

// Generate a test TOTP code
if ($secret) {
    $testCode = calculateTOTP($secret, floor(time() / 30));
    echo "<p><strong>Current TOTP Code:</strong> " . htmlspecialchars($testCode) . "</p>";
    echo "<p><em>Note: This code changes every 30 seconds</em></p>";
    
    // Test verification
    $isValid = verifyTOTPCode($user['id'], $testCode);
    echo "<p><strong>Code verification test:</strong> " . ($isValid ? '✅ Valid' : '❌ Invalid') . "</p>";
}

echo "<h3>Instructions:</h3>";
echo "<ol>";
echo "<li>If 2FA is enabled, you should be redirected to verify-2fa.php after login</li>";
echo "<li>Use Google Authenticator with secret: <strong>JBSWY3DPEHPK3PXP</strong></li>";
echo "<li>Enter the 6-digit code from your authenticator app</li>";
echo "<li>You should be redirected to the dashboard upon successful verification</li>";
echo "</ol>";

echo "<p><a href='login.php'>← Back to Login</a></p>";
?>
