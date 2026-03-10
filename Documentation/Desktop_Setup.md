# Desktop App Setup and Backend Connection

## Overview
This guide explains how to run the Electron desktop app and connect it to the Skill-Link Services backend API.

## Location
Desktop app source files are in:

- `Desktop/`

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
The app sends requests through Electron IPC from `main.js`:

- `POST /api/auth/login`
- `GET /api/auth/profile` (Bearer token)

## 5. Change Backend URL (Optional)
If backend runs on another host/port, set:

```powershell
$env:SKILLLINK_API_BASE_URL="http://127.0.0.1:8080"
npm.cmd start
```

## Troubleshooting

### `Error invoking remote method 'auth:login': TypeError: fetch failed`
- Backend is not reachable.
- Confirm `php spark serve` is running.
- Confirm URL in desktop metadata line matches backend URL.

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
