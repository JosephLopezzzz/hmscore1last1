<?php
// Environment Configuration Loader
// Loads environment variables from .env file

function loadEnv($path = null) {
    if ($path === null) {
        $path = __DIR__ . '/../.env';
    }
    
    if (!file_exists($path)) {
        return false;
    }
    
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        
        if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
            putenv(sprintf('%s=%s', $name, $value));
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
    
    return true;
}

// Load environment variables
loadEnv();

// Security Configuration
define('BCRYPT_ROUNDS', (int)($_ENV['BCRYPT_ROUNDS'] ?? 12));
define('SESSION_LIFETIME', (int)($_ENV['SESSION_LIFETIME'] ?? 900));
define('MAX_LOGIN_ATTEMPTS', (int)($_ENV['MAX_LOGIN_ATTEMPTS'] ?? 5));
define('LOGIN_ATTEMPT_WINDOW', (int)($_ENV['LOGIN_ATTEMPT_WINDOW'] ?? 600));
define('INACTIVITY_TIMEOUT', (int)($_ENV['INACTIVITY_TIMEOUT'] ?? 900));
define('FORCE_HTTPS', filter_var($_ENV['FORCE_HTTPS'] ?? 'true', FILTER_VALIDATE_BOOLEAN));
define('SECURE_COOKIES', filter_var($_ENV['SECURE_COOKIES'] ?? 'true', FILTER_VALIDATE_BOOLEAN));
define('SAME_SITE_COOKIES', $_ENV['SAME_SITE_COOKIES'] ?? 'Strict');

// Database Configuration
define('DB_HOST', $_ENV['DB_HOST'] ?? '127.0.0.1');
define('DB_PORT', (int)($_ENV['DB_PORT'] ?? 3306));
define('DB_NAME', $_ENV['DB_NAME'] ?? 'inn_nexus');
define('DB_USER', $_ENV['DB_USER'] ?? 'root');
define('DB_PASS', $_ENV['DB_PASS'] ?? '');

// Email Configuration
define('MAIL_HOST', $_ENV['MAIL_HOST'] ?? 'smtp.gmail.com');
define('MAIL_PORT', (int)($_ENV['MAIL_PORT'] ?? 587));
define('MAIL_USERNAME', $_ENV['MAIL_USERNAME'] ?? '');
define('MAIL_PASSWORD', $_ENV['MAIL_PASSWORD'] ?? '');
define('MAIL_ENCRYPTION', $_ENV['MAIL_ENCRYPTION'] ?? 'tls');
define('MAIL_FROM_ADDRESS', $_ENV['MAIL_FROM_ADDRESS'] ?? 'noreply@core1pms.com');
define('MAIL_FROM_NAME', $_ENV['MAIL_FROM_NAME'] ?? 'Core 1 PMS');

// reCAPTCHA Configuration
define('RECAPTCHA_SITE_KEY', $_ENV['RECAPTCHA_SITE_KEY'] ?? '');
define('RECAPTCHA_SECRET_KEY', $_ENV['RECAPTCHA_SECRET_KEY'] ?? '');

// JWT Configuration
define('JWT_SECRET', $_ENV['JWT_SECRET'] ?? 'your-jwt-secret-key-here-change-this');
define('JWT_ALGORITHM', $_ENV['JWT_ALGORITHM'] ?? 'HS256');
define('JWT_EXPIRY', (int)($_ENV['JWT_EXPIRY'] ?? 3600));
?>
