-- Step 2D: Alternative approach - Create user_sessions without expires_at initially
-- If Step 2C still fails, try this approach

USE inn_nexus;

-- Drop the table if it exists
DROP TABLE IF EXISTS user_sessions;

-- Create user_sessions table without the problematic expires_at column
CREATE TABLE user_sessions (
    id VARCHAR(128) PRIMARY KEY,
    user_id INT NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT NULL,
    last_activity DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Add expires_at column separately (this often works better)
ALTER TABLE user_sessions ADD COLUMN expires_at DATETIME NOT NULL;
