# Desktop App Setup and Backend Connection

## Overview
This guide explains how to run the Electron desktop app and connect it to the Skill-Link Services backend UI.

The desktop app now uses a modular, feature-based architecture and opens backend web routes directly so the desktop interface exactly matches the website dashboard and role-based pages.

## Location
Desktop app source files are in:

- `Desktop/`

Main architecture:

- `Desktop/src/main/config/` - Environment and backend URL configuration
- `Desktop/src/main/features/app/` - App lifecycle and system handlers
- `Desktop/src/main/features/navigation/` - Backend route navigation handlers
- `Desktop/src/main/features/window/` - Browser window creation behavior
- `Desktop/src/preload/` - Safe renderer bridge APIs

## Prerequisites
- Node.js 18+
- npm
- Backend API running from `Backend/`

## 1. Install Desktop Dependencies
```bash
cd Desktop
npm install
```

On Windows PowerShell, if script policy blocks `npm`, use:

```powershell
npm.cmd install
```

## 2. Start the Backend API
Open another terminal and run:

```bash
cd Backend
php spark serve --host 127.0.0.1 --port 8080
```

Backend base URL used by desktop (default):

- `http://localhost:8080`

Desktop startup route:

- `GET /auth/login`

## 3. Run the Desktop App
```bash
cd Desktop
npm start
```

On Windows PowerShell, you can also run:

```powershell
npm.cmd start
```

## 4. Login Flow Used by Desktop
The desktop window loads backend web routes directly:

- Login page: `/auth/login`
- Dashboard page: `/dashboard`

This ensures the desktop and backend website interface are the same.

## 5. Change Backend URL (Optional)
If backend runs on another host/port, set:

```powershell
$env:SKILLLINK_API_BASE_URL="http://127.0.0.1:8080"
npm.cmd start
```

## Troubleshooting

### `Error invoking remote method 'auth:login': TypeError: fetch failed`
- This error should no longer appear in the new architecture.
- If backend is down, desktop shows a fallback screen with actions to open backend routes.
- Confirm `php spark serve` is running.

### `Error invoking remote method 'auth:login': Class "Firebase\\JWT\\JWT" not found`
- Backend dependencies/autoload are incomplete.
- Run:

```bash
cd Backend
composer install
composer dump-autoload
```

Then restart backend server.

### `Request failed (400)`
- Usually invalid payload or validation error.
- Ensure you send valid email/password from seeded users.
- Try sample account:
  - `admin@skilllink.com` / `admin123`

## Seeded Test Accounts
Defined in `Backend/app/Database/Seeds/InitialDataSeeder.php`:

- Super Admin: `admin@skilllink.com` / `admin123`
- Finance: `finance@skilllink.com` / `finance123`
- Worker: `juan.santos@skilllink.com` / `worker123`
- Customer: `ana.cruz@email.com` / `customer123`
