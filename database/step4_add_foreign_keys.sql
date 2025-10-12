-- Step 4: Add foreign key constraints
-- Run this after Step 3 completes successfully

USE inn_nexus;

-- Add foreign key to security_logs
ALTER TABLE security_logs ADD CONSTRAINT fk_security_logs_user_id 
FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL;

-- Add foreign key to email_verification_tokens
ALTER TABLE email_verification_tokens ADD CONSTRAINT fk_email_verification_tokens_user_id 
FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;

-- Add foreign key to password_reset_tokens
ALTER TABLE password_reset_tokens ADD CONSTRAINT fk_password_reset_tokens_user_id 
FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;

-- Add foreign key to user_sessions
ALTER TABLE user_sessions ADD CONSTRAINT fk_user_sessions_user_id 
FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;

-- Add foreign key to security_events
ALTER TABLE security_events ADD CONSTRAINT fk_security_events_user_id 
FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL;
