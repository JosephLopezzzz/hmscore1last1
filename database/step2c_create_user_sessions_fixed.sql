-- Step 2C: Create user_sessions table with DATETIME instead of TIMESTAMP
-- This should work without any MySQL strict mode issues

USE inn_nexus;

-- Drop the table if it exists (in case of partial creation)
DROP TABLE IF EXISTS user_sessions;

-- Create user_sessions table with DATETIME instead of TIMESTAMP
CREATE TABLE user_sessions (
    id VARCHAR(128) PRIMARY KEY,
    user_id INT NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT NULL,
    last_activity DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL
);
