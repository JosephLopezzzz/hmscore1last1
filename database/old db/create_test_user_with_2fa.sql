-- Create a test user with 2FA enabled for testing the new 2FA flow
USE inn_nexus;

-- Insert test user (password: "test123")
-- Password hash generated with bcrypt, 12 rounds
INSERT INTO users (email, password_hash, role, is_active, email_verified, two_factor_enabled, two_factor_secret, created_at, updated_at) 
VALUES (
    'test@example.com',
    '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- "test123"
    'admin',
    1,
    1,
    1,
    'JBSWY3DPEHPK3PXP', -- Base32 encoded secret for Google Authenticator
    NOW(),
    NOW()
) ON DUPLICATE KEY UPDATE
    password_hash = VALUES(password_hash),
    two_factor_enabled = 1,
    two_factor_secret = VALUES(two_factor_secret),
    updated_at = NOW();

-- Verify the user was created/updated
SELECT id, email, role, two_factor_enabled, created_at FROM users WHERE email = 'test@example.com';
