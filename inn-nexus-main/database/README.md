# Database Setup Scripts

This folder contains the essential SQL scripts needed to set up the Inn Nexus database.

## 📋 Setup Order

Run these scripts **in order** to set up your database:

### 0️⃣ **00_initial_database_setup.sql** ⭐ START HERE!
**Purpose:** Creates the database and all tables from scratch

**What it does:**
- Creates the `inn_nexus` database
- Creates ALL 12 tables:
  - **Core Tables:** users, guests, rooms, reservations, billing_transactions
  - **Security Tables:** security_logs, email_verification_tokens, password_reset_tokens, user_sessions, rate_limits, security_events
  - **Audit Tables:** room_status_logs
- Sets up all indexes and foreign keys
- Establishes complete database structure

**Run this FIRST if setting up from scratch:**
```sql
SOURCE database/00_initial_database_setup.sql;
```

---

### 1️⃣ **complete_integration.sql**
**Purpose:** Creates the main database structure and sample data

**What it does:**
- Creates/updates `rooms` table with room details (room_number, type, floor, etc.)
- Creates/updates `reservations` table with booking information
- Creates `billing_transactions` table for payment tracking
- Creates `room_status_logs` table for audit trail
- Inserts sample room data (101-310)
- Inserts sample billing transactions

**Run this first:**
```sql
USE inn_nexus;
SOURCE database/complete_integration.sql;
```

---

### 2️⃣ **setup_security_simple.sql**
**Purpose:** Adds security features to the database

**What it does:**
- Adds security columns to `users` table:
  - Email verification (token, verified_at)
  - Password reset (token, expires)
  - Login tracking (last_login_at, last_login_ip)
  - Account security (failed_login_attempts, locked_until)
  - Two-factor authentication (totp_secret, totp_enabled)
- Creates security tables:
  - `security_logs` - Audit trail of security events
  - `email_verification_tokens` - Email verification management
  - `password_reset_tokens` - Password reset management
  - `user_sessions` - Active session tracking
  - `rate_limits` - Rate limiting for login attempts
  - `security_events` - Security monitoring

**Run this second:**
```sql
SOURCE database/setup_security_simple.sql;
```

---

### 3️⃣ **create_test_user_with_2fa.sql**
**Purpose:** Creates a test admin user with 2FA enabled

**What it does:**
- Creates a test admin account for development/testing

**Test User Credentials:**
- **Email:** `test@example.com`
- **Password:** `test123`
- **Role:** `admin`
- **2FA Secret:** `JBSWY3DPEHPK3PXP` (for Google Authenticator)

**Run this third:**
```sql
SOURCE database/create_test_user_with_2fa.sql;
```

---

## 🚀 Quick Setup (All at Once)

### **Option A: Complete Fresh Install (Recommended for new setups)**

Run all four scripts in order:

```bash
# Using MySQL CLI
mysql -u root -p < database/00_initial_database_setup.sql
mysql -u root -p inn_nexus < database/complete_integration.sql
mysql -u root -p inn_nexus < database/setup_security_simple.sql
mysql -u root -p inn_nexus < database/create_test_user_with_2fa.sql
```

### **Option B: Upgrade Existing Database**

If you already have the database, run only these:

```bash
# Using MySQL CLI
mysql -u root -p inn_nexus < database/complete_integration.sql
mysql -u root -p inn_nexus < database/setup_security_simple.sql
mysql -u root -p inn_nexus < database/create_test_user_with_2fa.sql
```

### **Using phpMyAdmin:**
1. Go to "SQL" tab
2. Import `00_initial_database_setup.sql` (creates database + tables)
3. Select the `inn_nexus` database
4. Import remaining files in order

---

## 📊 Database Tables Overview

After running all scripts, you'll have:

### Core Tables
- **`users`** - User accounts and authentication
- **`guests`** - Guest information
- **`reservations`** - Booking records
- **`rooms`** - Room inventory and status
- **`billing_transactions`** - Payment records

### Security Tables
- **`security_logs`** - Audit trail
- **`email_verification_tokens`** - Email verification
- **`password_reset_tokens`** - Password resets
- **`user_sessions`** - Active sessions
- **`rate_limits`** - Login attempt tracking
- **`security_events`** - Security monitoring

### Audit Tables
- **`room_status_logs`** - Room status change history

---

## 🔐 Security Features

The database includes these security measures:
- ✅ **Password Hashing** - bcrypt with 12 rounds
- ✅ **Two-Factor Authentication** - TOTP support
- ✅ **Email Verification** - Token-based verification
- ✅ **Rate Limiting** - Prevent brute force attacks
- ✅ **Session Management** - Track active sessions
- ✅ **Audit Logging** - Complete security event trail
- ✅ **Account Lockout** - Temporary lockout after failed attempts

---

## ⚠️ Important Notes

### For Development:
- Use the test user (`test@example.com` / `test123`) for development
- All scripts use `IF NOT EXISTS` clauses, so they're safe to re-run

### For Production:
1. **Delete** the test user after setup
2. **Create** real admin accounts with strong passwords
3. **Enable** 2FA for all admin accounts
4. **Change** all default credentials immediately
5. **Never** commit real credentials to version control
6. **Use** environment variables for sensitive data

---

## 📝 Creating Additional Users

### Option 1: Using PHP Function
```php
require_once 'includes/db.php';
createUser('admin@example.com', 'SecurePassword123', 'admin');
```

### Option 2: Using SQL
```sql
INSERT INTO users (email, password_hash, role, is_active, email_verified, email_verified_at)
VALUES (
    'admin@example.com',
    '$2y$12$YOUR_BCRYPT_HASH_HERE',
    'admin',
    1,
    1,
    NOW()
);
```

To generate password hash:
```bash
php -r "echo password_hash('YourPassword', PASSWORD_BCRYPT, ['cost' => 12]);"
```

---

## 🔧 Troubleshooting

### "Table already exists" error
- Scripts use `IF NOT EXISTS`, so this shouldn't happen
- If it does, scripts are safe to re-run

### "Unknown column" error
- Make sure you ran `complete_integration.sql` first
- Check that all three scripts completed successfully

### "Duplicate entry" error when creating test user
- User already exists, it will update instead (see `ON DUPLICATE KEY UPDATE`)
- This is expected behavior

### MySQL strict mode issues
- Scripts are compatible with strict mode
- If you see timestamp errors, check your MySQL version

---

## 📞 Support

For issues or questions:
1. Check the main README.md in the project root
2. Review the error messages carefully
3. Verify you ran scripts in the correct order
4. Check that `inn_nexus` database exists before running scripts

---

**Last Updated:** October 2025

