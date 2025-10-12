-- Step 3: Add indexes and foreign keys
-- Run this after Step 2 completes successfully

USE inn_nexus;

-- Add indexes to security_logs
CREATE INDEX idx_user_id ON security_logs(user_id);
CREATE INDEX idx_action ON security_logs(action);
CREATE INDEX idx_ip_address ON security_logs(ip_address);
CREATE INDEX idx_created_at ON security_logs(created_at);

-- Add indexes to email_verification_tokens
CREATE INDEX idx_token ON email_verification_tokens(token);
CREATE INDEX idx_user_id ON email_verification_tokens(user_id);
CREATE INDEX idx_expires_at ON email_verification_tokens(expires_at);

-- Add indexes to password_reset_tokens
CREATE INDEX idx_token ON password_reset_tokens(token);
CREATE INDEX idx_user_id ON password_reset_tokens(user_id);
CREATE INDEX idx_expires_at ON password_reset_tokens(expires_at);

-- Add indexes to user_sessions
CREATE INDEX idx_user_id ON user_sessions(user_id);
CREATE INDEX idx_expires_at ON user_sessions(expires_at);

-- Add indexes to rate_limits
CREATE INDEX idx_window_start ON rate_limits(window_start);

-- Add indexes to security_events
CREATE INDEX idx_event_type ON security_events(event_type);
CREATE INDEX idx_severity ON security_events(severity);
CREATE INDEX idx_user_id ON security_events(user_id);
CREATE INDEX idx_ip_address ON security_events(ip_address);
CREATE INDEX idx_created_at ON security_events(created_at);

-- Add indexes to users table
CREATE INDEX idx_users_email_verified ON users(email_verified_at);
CREATE INDEX idx_users_last_login ON users(last_login_at);
CREATE INDEX idx_users_failed_attempts ON users(failed_login_attempts);
CREATE INDEX idx_users_locked_until ON users(locked_until);
