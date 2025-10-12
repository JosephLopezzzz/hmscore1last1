-- Add only the missing columns to users table
-- This version checks each column individually

USE inn_nexus;

-- Check and add email_verification_token
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE table_name = 'users' 
     AND table_schema = 'inn_nexus' 
     AND column_name = 'email_verification_token') = 0,
    'ALTER TABLE users ADD COLUMN email_verification_token VARCHAR(64) NULL',
    'SELECT "email_verification_token already exists" as message'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check and add email_verified_at
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE table_name = 'users' 
     AND table_schema = 'inn_nexus' 
     AND column_name = 'email_verified_at') = 0,
    'ALTER TABLE users ADD COLUMN email_verified_at TIMESTAMP NULL',
    'SELECT "email_verified_at already exists" as message'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check and add password_reset_token
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE table_name = 'users' 
     AND table_schema = 'inn_nexus' 
     AND column_name = 'password_reset_token') = 0,
    'ALTER TABLE users ADD COLUMN password_reset_token VARCHAR(64) NULL',
    'SELECT "password_reset_token already exists" as message'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check and add password_reset_expires
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE table_name = 'users' 
     AND table_schema = 'inn_nexus' 
     AND column_name = 'password_reset_expires') = 0,
    'ALTER TABLE users ADD COLUMN password_reset_expires TIMESTAMP NULL',
    'SELECT "password_reset_expires already exists" as message'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check and add last_login_at
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE table_name = 'users' 
     AND table_schema = 'inn_nexus' 
     AND column_name = 'last_login_at') = 0,
    'ALTER TABLE users ADD COLUMN last_login_at TIMESTAMP NULL',
    'SELECT "last_login_at already exists" as message'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check and add last_login_ip
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE table_name = 'users' 
     AND table_schema = 'inn_nexus' 
     AND column_name = 'last_login_ip') = 0,
    'ALTER TABLE users ADD COLUMN last_login_ip VARCHAR(45) NULL',
    'SELECT "last_login_ip already exists" as message'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check and add failed_login_attempts
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE table_name = 'users' 
     AND table_schema = 'inn_nexus' 
     AND column_name = 'failed_login_attempts') = 0,
    'ALTER TABLE users ADD COLUMN failed_login_attempts INT DEFAULT 0',
    'SELECT "failed_login_attempts already exists" as message'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check and add locked_until
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE table_name = 'users' 
     AND table_schema = 'inn_nexus' 
     AND column_name = 'locked_until') = 0,
    'ALTER TABLE users ADD COLUMN locked_until TIMESTAMP NULL',
    'SELECT "locked_until already exists" as message'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check and add totp_secret
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE table_name = 'users' 
     AND table_schema = 'inn_nexus' 
     AND column_name = 'totp_secret') = 0,
    'ALTER TABLE users ADD COLUMN totp_secret VARCHAR(255) NULL',
    'SELECT "totp_secret already exists" as message'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check and add totp_enabled
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE table_name = 'users' 
     AND table_schema = 'inn_nexus' 
     AND column_name = 'totp_enabled') = 0,
    'ALTER TABLE users ADD COLUMN totp_enabled TINYINT(1) DEFAULT 0',
    'SELECT "totp_enabled already exists" as message'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check and add created_at
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE table_name = 'users' 
     AND table_schema = 'inn_nexus' 
     AND column_name = 'created_at') = 0,
    'ALTER TABLE users ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
    'SELECT "created_at already exists" as message'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check and add updated_at
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE table_name = 'users' 
     AND table_schema = 'inn_nexus' 
     AND column_name = 'updated_at') = 0,
    'ALTER TABLE users ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
    'SELECT "updated_at already exists" as message'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
