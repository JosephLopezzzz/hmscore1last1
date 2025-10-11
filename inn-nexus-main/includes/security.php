<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/env.php';

/**
 * Enhanced Security Module
 * Implements OWASP Top 10 security best practices
 */

class SecurityManager {
    private $pdo;
    private $logger;
    
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
        $this->logger = new SecurityLogger();
    }
    
    /**
     * Secure password hashing with bcrypt (OWASP: A02:2021 – Cryptographic Failures)
     */
    public function hashPassword(string $password): string {
        $options = [
            'cost' => BCRYPT_ROUNDS,
            'salt' => random_bytes(16) // Additional entropy
        ];
        return password_hash($password, PASSWORD_BCRYPT, $options);
    }
    
    /**
     * Verify password with timing attack protection
     */
    public function verifyPassword(string $password, string $hash): bool {
        return password_verify($password, $hash);
    }
    
    /**
     * Input sanitization and validation (OWASP: A03:2021 – Injection)
     */
    public function sanitizeInput(string $input, string $type = 'string'): string {
        $input = trim($input);
        
        switch ($type) {
            case 'email':
                $input = filter_var($input, FILTER_SANITIZE_EMAIL);
                if (!filter_var($input, FILTER_VALIDATE_EMAIL)) {
                    throw new InvalidArgumentException('Invalid email format');
                }
                break;
            case 'int':
                $input = filter_var($input, FILTER_SANITIZE_NUMBER_INT);
                if (!filter_var($input, FILTER_VALIDATE_INT)) {
                    throw new InvalidArgumentException('Invalid integer');
                }
                break;
            case 'string':
            default:
                $input = htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                break;
        }
        
        return $input;
    }
    
    /**
     * CSRF Protection (OWASP: A01:2021 – Broken Access Control)
     */
    public function generateCSRFToken(): string {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        
        return $_SESSION['csrf_token'];
    }
    
    public function verifyCSRFToken(string $token): bool {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        
        return isset($_SESSION['csrf_token']) && 
               hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Rate limiting (OWASP: A04:2021 – Insecure Design)
     */
    public function checkRateLimit(string $ip, string $action = 'login'): bool {
        $window = time() - LOGIN_ATTEMPT_WINDOW;
        
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as attempts 
            FROM security_logs 
            WHERE ip_address = :ip 
            AND action = :action 
            AND created_at > :window
        ");
        
        $stmt->execute([
            ':ip' => $ip,
            ':action' => $action,
            ':window' => $window
        ]);
        
        $result = $stmt->fetch();
        return ($result['attempts'] ?? 0) < MAX_LOGIN_ATTEMPTS;
    }
    
    /**
     * Log security events
     */
    public function logSecurityEvent(string $action, string $ip, ?int $userId = null, string $details = ''): void {
        $stmt = $this->pdo->prepare("
            INSERT INTO security_logs (user_id, action, ip_address, details, created_at) 
            VALUES (:user_id, :action, :ip, :details, NOW())
        ");
        
        $stmt->execute([
            ':user_id' => $userId,
            ':action' => $action,
            ':ip' => $ip,
            ':details' => $details
        ]);
        
        $this->logger->log($action, $ip, $userId, $details);
    }
    
    /**
     * Session management with rotation (OWASP: A07:2021 – Identification and Authentication Failures)
     */
    public function startSecureSession(): void {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        
        // Secure session configuration
        ini_set('session.cookie_httponly', '1');
        ini_set('session.cookie_secure', SECURE_COOKIES ? '1' : '0');
        ini_set('session.cookie_samesite', SAME_SITE_COOKIES);
        ini_set('session.use_strict_mode', '1');
        ini_set('session.cookie_lifetime', SESSION_LIFETIME);
        ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
        
        session_start();
        
        // Regenerate session ID for security
        if (!isset($_SESSION['session_created'])) {
            session_regenerate_id(true);
            $_SESSION['session_created'] = time();
        }
    }
    
    /**
     * Check session validity and inactivity timeout
     */
    public function validateSession(): bool {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return false;
        }
        
        $lastActivity = $_SESSION['last_activity'] ?? 0;
        $currentTime = time();
        
        // Check inactivity timeout
        if (($currentTime - $lastActivity) > INACTIVITY_TIMEOUT) {
            $this->destroySession();
            return false;
        }
        
        // Update last activity
        $_SESSION['last_activity'] = $currentTime;
        
        return true;
    }
    
    /**
     * Secure session destruction
     */
    public function destroySession(): void {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_unset();
            session_destroy();
        }
        
        // Clear session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
    }
    
    /**
     * Email verification token generation
     */
    public function generateEmailVerificationToken(): string {
        return bin2hex(random_bytes(32));
    }
    
    /**
     * Validate email verification token
     */
    public function validateEmailToken(string $token): bool {
        $stmt = $this->pdo->prepare("
            SELECT id FROM users 
            WHERE email_verification_token = :token 
            AND email_verified_at IS NULL 
            AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ");
        
        $stmt->execute([':token' => $token]);
        return $stmt->fetch() !== false;
    }
    
    /**
     * reCAPTCHA verification
     */
    public function verifyRecaptcha(string $response, string $ip): bool {
        if (empty(RECAPTCHA_SECRET_KEY)) {
            return true; // Skip if not configured
        }
        
        $data = [
            'secret' => RECAPTCHA_SECRET_KEY,
            'response' => $response,
            'remoteip' => $ip
        ];
        
        $options = [
            'http' => [
                'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                'method' => 'POST',
                'content' => http_build_query($data)
            ]
        ];
        
        $context = stream_context_create($options);
        $result = file_get_contents('https://www.google.com/recaptcha/api/siteverify', false, $context);
        $response = json_decode($result, true);
        
        return $response['success'] ?? false;
    }
    
    /**
     * Force HTTPS redirect
     */
    public function enforceHTTPS(): void {
        if (FORCE_HTTPS && !$this->isHTTPS()) {
            $redirectURL = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            header("Location: $redirectURL", true, 301);
            exit;
        }
    }
    
    private function isHTTPS(): bool {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
               $_SERVER['SERVER_PORT'] == 443 ||
               (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    }
}

/**
 * Security Logger
 */
class SecurityLogger {
    private $logFile;
    
    public function __construct() {
        $this->logFile = __DIR__ . '/../logs/security.log';
        $this->ensureLogDirectory();
    }
    
    private function ensureLogDirectory(): void {
        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }
    
    public function log(string $action, string $ip, ?int $userId, string $details): void {
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = sprintf(
            "[%s] Action: %s | IP: %s | User: %s | Details: %s\n",
            $timestamp,
            $action,
            $ip,
            $userId ?? 'anonymous',
            $details
        );
        
        file_put_contents($this->logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
}

/**
 * Input Validator
 */
class InputValidator {
    public static function validateEmail(string $email): bool {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    public static function validatePassword(string $password): array {
        $errors = [];
        
        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters long';
        }
        
        // Check for common passwords
        $commonPasswords = ['password', '123456', 'password123', 'admin', 'qwerty'];
        if (in_array(strtolower($password), $commonPasswords)) {
            $errors[] = 'Password is too common, please choose a stronger password';
        }
        
        return $errors;
    }
    
    public static function validatePhone(string $phone): bool {
        return preg_match('/^\+?[1-9]\d{1,14}$/', $phone);
    }
    
    public static function sanitizeString(string $input, int $maxLength = 255): string {
        $input = trim($input);
        $input = htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return substr($input, 0, $maxLength);
    }
}
?>
