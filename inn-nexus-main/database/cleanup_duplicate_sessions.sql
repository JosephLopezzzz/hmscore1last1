-- Cleanup: Remove duplicate user_sessions table
-- Since both Step 2C and Step 2D succeeded, we have duplicate tables
-- This script will keep one clean version

USE inn_nexus;

-- Drop the table completely to start fresh
DROP TABLE IF EXISTS user_sessions;

-- Create a clean, single user_sessions table using the DATETIME approach
CREATE TABLE user_sessions (
    id VARCHAR(128) PRIMARY KEY,
    user_id INT NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT NULL,
    last_activity DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL
);

-- Verify the table was created successfully
SHOW TABLES LIKE 'user_sessions';
