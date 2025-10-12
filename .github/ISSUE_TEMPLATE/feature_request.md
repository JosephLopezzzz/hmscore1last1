# Inn Nexus Hotel Management System

## 🚀 Quick Setup Guide

### 1. Database Setup
```sql
CREATE DATABASE inn_nexus;
USE inn_nexus;
SOURCE database/complete_integration.sql;
SOURCE database/create_test_user_with_2fa.sql;
```

### 2. Run Server
```bash
php -S localhost:8000
```

### 3. Login
- **Email:** `test@example.com`
- **Password:** `test123`
- **2FA Secret:** `JBSWY3DPEHPK3PXP` (for Google Authenticator)

## 🔐 Security Features
- Two-Factor Authentication (2FA)
- Secure password hashing (bcrypt)
- CSRF protection
- Input sanitization
- Session management

## 🎨 Features
- Responsive design with Tailwind CSS
- Dark/light mode toggle
- Real-time room status updates
- CSV export functionality
- Modal forms and notifications

## 📊 Tech Stack
- **Backend:** PHP 7.4+ with PDO MySQL
- **Frontend:** HTML5, Tailwind CSS, JavaScript
- **Database:** MySQL 5.7+
- **Security:** bcrypt, TOTP, CSRF tokens

## 📁 Key Files
- `index.php` - Dashboard
- `login.php` - Authentication
- `reservations.php` - Booking management
- `billing.php` - Payment processing
- `rooms-overview.php` - Room management

## 📈 API Endpoints
- `GET /api/health` - System health
- `GET /api/guests` - Guest data
- `GET /api/reservations` - Reservation data
- `GET /api/rooms` - Room data
- `POST /api/rooms` - Update room status

## 📝 License
MIT License

---

**Inn Nexus** - Professional Hotel Management Made Simple ⭐
