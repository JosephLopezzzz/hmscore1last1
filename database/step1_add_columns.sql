-- Step 1: Add security columns to users table
-- Run this first, one column at a time if needed

USE inn_nexus;

-- Add email verification columns
ALTER TABLE users ADD COLUMN email_verification_token VARCHAR(64) NULL;
ALTER TABLE users ADD COLUMN email_verified_at TIMESTAMP NULL;

-- Add password reset columns  
ALTER TABLE users ADD COLUMN password_reset_token VARCHAR(64) NULL;
ALTER TABLE users ADD COLUMN password_reset_expires TIMESTAMP NULL;

-- Add login tracking columns
ALTER TABLE users ADD COLUMN last_login_at TIMESTAMP NULL;
ALTER TABLE users ADD COLUMN last_login_ip VARCHAR(45) NULL;

-- Add security columns
ALTER TABLE users ADD COLUMN failed_login_attempts INT DEFAULT 0;
ALTER TABLE users ADD COLUMN locked_until TIMESTAMP NULL;

-- Add 2FA columns
ALTER TABLE users ADD COLUMN totp_secret VARCHAR(255) NULL;
ALTER TABLE users ADD COLUMN totp_enabled TINYINT(1) DEFAULT 0;

-- Add timestamp columns
ALTER TABLE users ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE users ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;
