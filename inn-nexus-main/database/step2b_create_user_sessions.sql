-- Step 2B: Create user_sessions table with a different approach
-- Run this after Step 2A completes successfully

USE inn_nexus;

-- Create user_sessions table without the problematic expires_at default
CREATE TABLE IF NOT EXISTS user_sessions (
    id VARCHAR(128) PRIMARY KEY,
    user_id INT NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT NULL,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL
);

-- Add a comment to explain the expires_at column
ALTER TABLE user_sessions MODIFY COLUMN expires_at TIMESTAMP NOT NULL COMMENT 'Session expiration time - set by application';
