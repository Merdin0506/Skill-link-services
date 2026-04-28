# Backend Setup and Configuration Guide

## Overview
The Skill-Link Services backend is built with CodeIgniter 4 and provides REST API endpoints for the mobile and desktop applications.

## Prerequisites
- **PHP 8.2+** with extensions: `intl`, `mbstring`, `mysqli`, `curl`
- **MySQL 8.0+** or MariaDB 10.3+
- **Composer** for dependency management
- **XAMPP** (recommended for local development)

## Quick Setup

### 1. Start XAMPP
- Start Apache and MySQL services
- Ensure MySQL is running on port 3306

### 2. Database Setup
```sql
CREATE DATABASE skilllink_services;
```

### 3. Install Dependencies
```bash
# from repository root
cd Backend
composer install
```

### 4. Environment Configuration
Copy `env.example` to `.env` and update:
```ini
# App Configuration
app.baseURL = 'http://localhost:8080'

# Database Configuration
database.default.hostname = localhost
database.default.database = skilllink_services
database.default.username = root
database.default.password = 

# JWT Configuration
JWT_SECRET = your-secret-key-change-this
```

### Shared Team Environment Baseline
For this project, the team should align on these local defaults unless someone has a reason to override them:

- Backend URL: `http://localhost:8080`
- Database name: `skilllink_services`
- Database host: `localhost`
- Database port: `3306`
- Database driver: `MySQLi`
- Session driver: `CodeIgniter\Session\Handlers\FileHandler`
- Desktop API override variable: `SKILLLINK_API_BASE_URL=http://127.0.0.1:8080` when needed

Recommended flow for each teammate:

1. Copy `Backend/env.example` to `Backend/.env`.
2. Keep shared defaults the same.
3. Set only machine-specific or secret values locally:
   - `database.default.username`
   - `database.default.password`
   - `encryption.key`
   - `JWT_SECRET`
   - `SMTP_USER`
   - `SMTP_PASS`
   - `GEMINI_API_KEY`
4. If XAMPP is installed in a different directory, update:
   - `backup.mysqlDumpBinary`
   - `backup.mysqlBinary`

Important:

- Do not commit `Backend/.env`.
- Do not commit real SMTP passwords, JWT secrets, encryption keys, or API keys.
- `Backend/env.example` is the shared template that should stay safe for Git.

### 5. Database Migration
```bash
php spark migrate
```

### 6. Start Development Server
```bash
php spark serve
```

The API will be available at: `http://localhost:8080`

## Gitignore Configuration

Essential files to ignore:

```
# Environment files
.env
.env.*

# Dependencies
/vendor/
/Backend/vendor/
composer.phar

# Writable directories
/writable/cache/*
/writable/logs/*
/writable/session/*
/writable/uploads/*
/Backend/writable/cache/*
/Backend/writable/logs/*
/Backend/writable/session/*
/Backend/writable/uploads/*

# Database files
*.sql
*.db

# Backup files
*.bak
*.backup

# IDE files
.vscode/
.idea/
.DS_Store
Thumbs.db
```

## API Endpoints

### Authentication
- `POST /api/auth/register` - User registration
- `POST /api/auth/login` - User login
- `GET /api/auth/profile` - Get user profile

### Services
- `GET /api/services` - List services
- `GET /api/services/categories` - Service categories

### Bookings
- `GET /api/bookings` - List bookings
- `POST /api/bookings` - Create booking
- `PUT /api/bookings/{id}` - Update booking

### Users
- `GET /api/users/workers` - List workers
- `GET /api/users/customers` - List customers

## User Types
- **Owner** - System owner
- **Admin** - Administrative access
- **Cashier** - Payment management
- **Worker** - Service providers
- **Customer** - Service consumers

## Testing API
Use Postman or curl to test endpoints:

```bash
# Login
curl -X POST http://localhost:8080/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email": "test@example.com", "password": "password123"}'

# Get services
curl http://localhost:8080/api/services
```

## Common Issues

**Database Connection Error**
- Ensure MySQL is running in XAMPP
- Check database credentials in `.env`

**formatNumber() Error**
- Fixed by loading dashboard helper in BaseController

**File Permissions**
- Ensure `writable/` directory is writable

**Desktop Login Error: `Class "Firebase\\JWT\\JWT" not found`**
- Cause: Backend `vendor/autoload.php` is missing or dependencies are incomplete.
- Fix:

```bash
cd Backend
composer install
composer dump-autoload
```

Then restart the backend server:

```bash
php spark serve --host 127.0.0.1 --port 8080
```

## Desktop Connection Notes

The Electron desktop app uses these backend endpoints:

- `POST /api/auth/login` for credential sign-in
- `GET /api/auth/profile` with `Authorization: Bearer <token>`

Default desktop API URL:

- `http://localhost:8080`

Optional override for desktop runtime:

```powershell
$env:SKILLLINK_API_BASE_URL="http://127.0.0.1:8080"
```

## Development Workflow
1. Make changes to code
2. Test locally with `php spark serve`
3. Use Postman to test API endpoints
4. Commit changes to Git

## Production Deployment
1. Upload files to server
2. Run `composer install --no-dev`
3. Configure `.env` for production
4. Run database migrations
5. Configure web server (Apache/Nginx)
6. Enable SSL certificates

---

**Framework**: CodeIgniter 4.7.0  
**PHP Version**: 8.2+  
**Database**: MySQL 8.0+  
**Last Updated**: March 2026
