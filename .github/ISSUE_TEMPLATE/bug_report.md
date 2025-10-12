# Inn Nexus Hotel Management System

A comprehensive, secure hotel management system built with PHP, MySQL, and modern web technologies.

## 🌟 Key Features

- **Dashboard Overview** - Real-time hotel operations
- **Reservation Management** - Complete booking lifecycle
- **Room Management** - Visual floor layout with status updates
- **Billing & Payments** - Payment processing with CSV export
- **Guest Management** - Guest profiles and relationships
- **Two-Factor Authentication** - TOTP-based security
- **Responsive Design** - Mobile-first with Tailwind CSS
- **Dark/Light Mode** - User preference theme switching

## 🚀 Quick Start

1. **Setup Database**
   ```sql
   CREATE DATABASE inn_nexus;
   USE inn_nexus;
   SOURCE database/complete_integration.sql;
   ```

2. **Create Test User**
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

## 🔐 Security

- Two-Factor Authentication (2FA)
- Secure password hashing (bcrypt)
- CSRF protection
- Input sanitization
- Session management
- Rate limiting

## 📊 Tech Stack

- **Backend:** PHP 7.4+ with PDO MySQL
- **Frontend:** HTML5, Tailwind CSS, JavaScript
- **Database:** MySQL 5.7+
- **Security:** bcrypt, TOTP, CSRF tokens
- **Icons:** Lucide Icons

## 📁 Project Structure

```
inn-nexus/
├── api/                    # REST API endpoints
├── config/                 # Configuration files
├── database/              # Database schemas
├── includes/              # Core PHP includes
├── partials/              # Reusable components
├── public/                 # Static assets
├── index.php              # Dashboard
├── login.php              # Authentication
├── reservations.php       # Booking management
├── billing.php            # Payment processing
└── rooms-overview.php     # Room management
```

## 📈 API Endpoints

- `GET /api/health` - System health check
- `GET /api/guests` - Guest data
- `GET /api/reservations` - Reservation data
- `GET /api/rooms` - Room data
- `POST /api/rooms` - Update room status

## 🎨 UI Features

- Responsive design with Tailwind CSS
- Dark/light mode toggle
- Modal forms for data entry
- Toast notifications
- Real-time updates
- CSV export functionality

## 📝 License

MIT License - see LICENSE file for details.

---

**Inn Nexus** - Professional Hotel Management Made Simple ⭐
