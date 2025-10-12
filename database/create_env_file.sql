-- Create a sample .env file for configuration
-- This is just for reference - you need to create the actual .env file

/*
Create a file named .env in your project root with this content:

# Core 1 PMS - Environment Configuration
# Security Settings
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:your-32-character-secret-key-here-change-this

# Database Configuration
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=inn_nexus
DB_USER=root
DB_PASS=

# Security Settings
BCRYPT_ROUNDS=12
SESSION_LIFETIME=900
MAX_LOGIN_ATTEMPTS=5
LOGIN_ATTEMPT_WINDOW=600
INACTIVITY_TIMEOUT=900

# Email Configuration (for verification)
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@core1pms.com
MAIL_FROM_NAME="Core 1 PMS"

# reCAPTCHA Configuration
RECAPTCHA_SITE_KEY=your-recaptcha-site-key
RECAPTCHA_SECRET_KEY=your-recaptcha-secret-key

# JWT Settings (for API)
JWT_SECRET=your-jwt-secret-key-here-change-this
JWT_ALGORITHM=HS256
JWT_EXPIRY=3600

# Security Headers
FORCE_HTTPS=true
SECURE_COOKIES=true
SAME_SITE_COOKIES=Strict

# Logging
LOG_LEVEL=info
LOG_FILE=logs/security.log
*/
