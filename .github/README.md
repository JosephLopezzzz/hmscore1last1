# Inn Nexus - Hotel Management System

## 🚀 Getting Started

### Prerequisites
- PHP 7.4+ with PDO MySQL extension
- MySQL 5.7+ or MariaDB 10.3+
- Web server (Apache/Nginx) or PHP built-in server

### Quick Setup

1. **Database Setup**
   ```sql
   CREATE DATABASE inn_nexus;
   USE inn_nexus;
   SOURCE database/complete_integration.sql;
   ```

2. **Test User**
   ```sql
   SOURCE database/create_test_user_with_2fa.sql;
   ```

3. **Run Server**
   ```bash
   php -S localhost:8000
   ```

4. **Login**
   - Email: `test@example.com`
   - Password: `test123`
   - 2FA Secret: `JBSWY3DPEHPK3PXP`

## 📁 Key Files

- `index.php` - Dashboard
- `login.php` - Authentication
- `verify-2fa.php` - 2FA verification
- `reservations.php` - Booking management
- `billing.php` - Payment processing
- `rooms-overview.php` - Room management
- `includes/db.php` - Database connection
- `includes/security.php` - Security functions

## 🔐 Security Features

- Two-Factor Authentication (2FA)
- Secure password hashing (bcrypt)
- CSRF protection
- Input sanitization
- Session management
- Rate limiting

## 🎨 Features

- Responsive design with Tailwind CSS
- Dark/light mode toggle
- Real-time room status updates
- CSV export functionality
- Modal forms and toast notifications
- RESTful API endpoints

## 📊 Database Schema

The system uses MySQL with the following main tables:
- `users` - User accounts and authentication
- `guests` - Guest information
- `reservations` - Booking data
- `rooms` - Room management
- `billing_transactions` - Payment records
- `security_logs` - Audit trails

## 🔧 Configuration

Update database credentials in `includes/db.php`:
```php
function getDbConfig(): array {
  return [
    'host' => '127.0.0.1',
    'database' => 'inn_nexus',
    'username' => 'root',
    'password' => '',
  ];
}
```

## 📈 API Endpoints

- `GET /api/health` - System health
- `GET /api/guests` - Guest data
- `GET /api/reservations` - Reservation data
- `GET /api/rooms` - Room data
- `POST /api/rooms` - Update room status

## 🧪 Testing

Use the provided test account or create your own users through the registration system.

## 📝 License

MIT License - see LICENSE file for details.

---

**Inn Nexus** - Professional Hotel Management Made Simple ⭐
