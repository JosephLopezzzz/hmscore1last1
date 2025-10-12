<?php
declare(strict_types=1);

require_once __DIR__ . '/security.php';
require_once __DIR__ . '/db.php';

/**
 * Enhanced Authentication System
 * Implements secure login with all security best practices
 */

class SecureAuth {
    private $pdo;
    private $security;
    
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
        $this->security = new SecurityManager($pdo);
    }
    
    /**
     * Secure user registration with email verification
     */
    public function registerUser(array $userData): array {
        try {
            // Validate input data
            $email = $this->security->sanitizeInput($userData['email'], 'email');
            $password = $userData['password'];
            $name = $this->security->sanitizeInput($userData['name'], 'string');
            $role = $userData['role'] ?? 'receptionist';
            
            // Validate password strength
            $passwordErrors = InputValidator::validatePassword($password);
            if (!empty($passwordErrors)) {
                return ['success' => false, 'errors' => $passwordErrors];
            }
            
            // Check if user already exists
            $stmt = $this->pdo->prepare("SELECT id FROM users WHERE email = :email");
            $stmt->execute([':email' => $email]);
            if ($stmt->fetch()) {
                return ['success' => false, 'errors' => ['Email already registered']];
            }
            
            // Hash password securely
            $hashedPassword = $this->security->hashPassword($password);
            
            // Generate email verification token
            $verificationToken = $this->security->generateEmailVerificationToken();
            
            // Insert user
            $stmt = $this->pdo->prepare("
                INSERT INTO users (name, email, password_hash, role, email_verification_token, created_at) 
                VALUES (:name, :email, :password_hash, :role, :token, NOW())
            ");
            
            $result = $stmt->execute([
                ':name' => $name,
                ':email' => $email,
                ':password_hash' => $hashedPassword,
                ':role' => $role,
                ':token' => $verificationToken
            ]);
            
            if ($result) {
                $userId = $this->pdo->lastInsertId();
                
                // Log registration
                $this->security->logSecurityEvent(
                    'user_registered',
                    $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                    $userId,
                    "User registered with email: $email"
                );
                
                // Send verification email
                $this->sendVerificationEmail($email, $verificationToken);
                
                return ['success' => true, 'message' => 'Registration successful. Please check your email for verification.'];
            }
            
            return ['success' => false, 'errors' => ['Registration failed']];
            
        } catch (Exception $e) {
            error_log("Registration error: " . $e->getMessage());
            return ['success' => false, 'errors' => ['Registration failed. Please try again.']];
        }
    }
    
    /**
     * Secure login with rate limiting and 2FA
     */
    public function login(string $email, string $password, ?string $totpCode = null): array {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        
        try {
            // Check rate limiting
            if (!$this->security->checkRateLimit($ip, 'login')) {
                $this->security->logSecurityEvent('rate_limit_exceeded', $ip, null, "Too many login attempts from IP: $ip");
                return ['success' => false, 'errors' => ['Too many login attempts. Please try again later.']];
            }
            
            // Sanitize input
            $email = $this->security->sanitizeInput($email, 'email');
            
            // Get user data
            $stmt = $this->pdo->prepare("
                SELECT id, name, email, password_hash, role, email_verified_at, 
                       failed_login_attempts, locked_until, totp_enabled, totp_secret
                FROM users WHERE email = :email
            ");
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch();
            
            if (!$user) {
                $this->security->logSecurityEvent('login_failed', $ip, null, "Invalid email: $email");
                return ['success' => false, 'errors' => ['Invalid credentials']];
            }
            
            // Check if account is locked
            if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
                $this->security->logSecurityEvent('login_failed', $ip, $user['id'], "Account locked until: " . $user['locked_until']);
                return ['success' => false, 'errors' => ['Account is temporarily locked. Please try again later.']];
            }
            
            // Check email verification
            if (!$user['email_verified_at']) {
                $this->security->logSecurityEvent('login_failed', $ip, $user['id'], "Email not verified");
                return ['success' => false, 'errors' => ['Please verify your email before logging in.']];
            }
            
            // Verify password
            if (!$this->security->verifyPassword($password, $user['password_hash'])) {
                $this->handleFailedLogin($user['id'], $ip);
                return ['success' => false, 'errors' => ['Invalid credentials']];
            }
            
            // Check 2FA if enabled
            if ($user['totp_enabled']) {
                if (!$totpCode || !$this->security->verifyTOTPCode($user['totp_secret'], $totpCode)) {
                    $this->security->logSecurityEvent('login_failed', $ip, $user['id'], "Invalid 2FA code");
                    return ['success' => false, 'errors' => ['Invalid 2FA code'], 'requires_2fa' => true];
                }
            }
            
            // Reset failed attempts and start secure session
            $this->resetFailedAttempts($user['id']);
            $this->startUserSession($user, $ip);
            
            $this->security->logSecurityEvent('login_success', $ip, $user['id'], "User logged in successfully");
            
            return ['success' => true, 'message' => 'Login successful'];
            
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            $this->security->logSecurityEvent('login_error', $ip, null, "Login error: " . $e->getMessage());
            return ['success' => false, 'errors' => ['Login failed. Please try again.']];
        }
    }
    
    /**
     * Handle failed login attempts
     */
    private function handleFailedLogin(int $userId, string $ip): void {
        $stmt = $this->pdo->prepare("
            UPDATE users 
            SET failed_login_attempts = failed_login_attempts + 1,
                locked_until = CASE 
                    WHEN failed_login_attempts >= 4 THEN DATE_ADD(NOW(), INTERVAL 15 MINUTE)
                    ELSE locked_until
                END
            WHERE id = :user_id
        ");
        $stmt->execute([':user_id' => $userId]);
        
        $this->security->logSecurityEvent('login_failed', $ip, $userId, "Failed login attempt");
    }
    
    /**
     * Reset failed login attempts
     */
    private function resetFailedAttempts(int $userId): void {
        $stmt = $this->pdo->prepare("
            UPDATE users 
            SET failed_login_attempts = 0, 
                locked_until = NULL,
                last_login_at = NOW(),
                last_login_ip = :ip
            WHERE id = :user_id
        ");
        $stmt->execute([
            ':user_id' => $userId,
            ':ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
    }
    
    /**
     * Start secure user session
     */
    private function startUserSession(array $user, string $ip): void {
        $this->security->startSecureSession();
        
        // Regenerate session ID for security
        session_regenerate_id(true);
        
        // Set session data
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['last_activity'] = time();
        $_SESSION['ip_address'] = $ip;
        $_SESSION['session_created'] = time();
        
        // Store session in database
        $this->storeSessionInDatabase($user['id'], $ip);
    }
    
    /**
     * Store session in database for tracking
     */
    private function storeSessionInDatabase(int $userId, string $ip): void {
        $sessionId = session_id();
        $expiresAt = date('Y-m-d H:i:s', time() + SESSION_LIFETIME);
        
        $stmt = $this->pdo->prepare("
            INSERT INTO user_sessions (id, user_id, ip_address, user_agent, expires_at) 
            VALUES (:id, :user_id, :ip, :user_agent, :expires_at)
            ON DUPLICATE KEY UPDATE 
                last_activity = NOW(),
                expires_at = :expires_at
        ");
        
        $stmt->execute([
            ':id' => $sessionId,
            ':user_id' => $userId,
            ':ip' => $ip,
            ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            ':expires_at' => $expiresAt
        ]);
    }
    
    /**
     * Verify email address
     */
    public function verifyEmail(string $token): array {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE users 
                SET email_verified_at = NOW(), 
                    email_verification_token = NULL 
                WHERE email_verification_token = :token 
                AND email_verified_at IS NULL
            ");
            
            $stmt->execute([':token' => $token]);
            
            if ($stmt->rowCount() > 0) {
                $this->security->logSecurityEvent('email_verified', $_SERVER['REMOTE_ADDR'] ?? 'unknown', null, "Email verified with token");
                return ['success' => true, 'message' => 'Email verified successfully'];
            }
            
            return ['success' => false, 'errors' => ['Invalid or expired verification token']];
            
        } catch (Exception $e) {
            error_log("Email verification error: " . $e->getMessage());
            return ['success' => false, 'errors' => ['Email verification failed']];
        }
    }
    
    /**
     * Send verification email
     */
    private function sendVerificationEmail(string $email, string $token): void {
        // This would integrate with your email service
        // For now, we'll just log it
        $verificationUrl = "https://" . $_SERVER['HTTP_HOST'] . "/verify-email.php?token=" . $token;
        error_log("Verification email for $email: $verificationUrl");
        
        // In production, use PHPMailer or similar
        // $this->sendEmail($email, 'Verify Your Email', "Click here to verify: $verificationUrl");
    }
    
    /**
     * Logout user securely
     */
    public function logout(): void {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $sessionId = session_id();
            
            // Remove session from database
            $stmt = $this->pdo->prepare("DELETE FROM user_sessions WHERE id = :id");
            $stmt->execute([':id' => $sessionId]);
            
            // Log logout
            $this->security->logSecurityEvent(
                'logout',
                $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                $_SESSION['user_id'] ?? null,
                'User logged out'
            );
        }
        
        $this->security->destroySession();
    }
    
    /**
     * Check if user is authenticated
     */
    public function isAuthenticated(): bool {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return false;
        }
        
        return $this->security->validateSession() && 
               isset($_SESSION['user_id']) && 
               isset($_SESSION['last_activity']);
    }
    
    /**
     * Get current user data
     */
    public function getCurrentUser(): ?array {
        if (!$this->isAuthenticated()) {
            return null;
        }
        
        return [
            'id' => $_SESSION['user_id'],
            'name' => $_SESSION['user_name'],
            'email' => $_SESSION['user_email'],
            'role' => $_SESSION['user_role']
        ];
    }
}
?>
