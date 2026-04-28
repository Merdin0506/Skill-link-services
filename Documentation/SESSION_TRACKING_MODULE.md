# Session Tracking Module

## Overview
The Session Tracking Module records active user sessions for both the web dashboard and JWT-based API logins.

It supports:
- Tracking the current logged-in browser session
- Listing multiple active sessions for the same user
- Recording login time, last activity time, IP address, and device details
- Marking sessions as logged out or expired
- Admin monitoring of recent active sessions

## Purpose
This module was added to:
- monitor currently active user sessions
- improve account visibility and auditing
- prepare for future features such as force logout, suspicious session review, or per-device session management

## Core Files
Database:
- `Backend/app/Database/Migrations/2026-04-28-000001_CreateUserSessionsTable.php`

Models:
- `Backend/app/Models/UserSessionModel.php`

Libraries:
- `Backend/app/Libraries/SessionTracker.php`

Filters:
- `Backend/app/Filters/SessionActivityFilter.php`
- `Backend/app/Filters/DashboardAuth.php`
- `Backend/app/Filters/JWTAuthFilter.php`

Controllers:
- `Backend/app/Controllers/Auth.php`
- `Backend/app/Controllers/API/AuthController.php`
- `Backend/app/Controllers/Dashboard.php`

Views:
- `Backend/app/Views/dashboard/settings.php`

Configuration:
- `Backend/app/Config/Services.php`
- `Backend/app/Config/Filters.php`

## Database Structure
The `user_sessions` table stores one row per tracked session.

Main fields:
- `user_id`
- `session_key`
- `session_type`
- `status`
- `ip_address`
- `user_agent`
- `device_label`
- `logged_in_at`
- `last_activity_at`
- `logged_out_at`
- `expires_at`

### Session Types
- `web` for dashboard/browser logins
- `api` for JWT-authenticated API sessions

### Status Values
- `active`
- `logged_out`
- `expired`

## How It Works

### Web Login Flow
1. User logs in through `Auth::doLogin()`.
2. OTP is verified in `Auth::doVerifyOtp()`.
3. A tracked session is created by `SessionTracker::startWebSession()`.
4. The generated tracked session key is stored in the PHP session as `tracked_session_key`.
5. A JWT token is also created with the session id (`sid`) embedded.

### Web Activity Refresh
- `SessionActivityFilter` runs on non-API requests.
- If the user is logged in and has a tracked session key, `last_activity_at` is updated.

### Web Logout
- `Auth::logout()` calls `SessionTracker::endWebSession()`.
- The session row is marked as `logged_out`.

### API Login Flow
1. API login starts in `API\AuthController::login()`.
2. OTP is verified in `API\AuthController::verifyOtp()`.
3. A tracked API session is created by `SessionTracker::startApiSession()`.
4. The returned JWT includes a `sid` field tied to the session row.

### API Request Validation
- `JWTAuthFilter` validates the JWT token.
- It reads the `sid` from the token.
- It checks that the session still exists and is `active`.
- It updates `last_activity_at` through `SessionTracker::touchApiSession()`.

### API Logout
- `API\AuthController::logout()` ends the tracked API session using the `sid` from the authenticated request.

## Settings Page Behavior
The Settings page now includes session visibility features:

- `Current Session`
  - shows the active session being used right now
- `My Active Sessions`
  - only appears when the user has more than one active session
- `Active Session Monitor`
  - visible for admin or super admin roles
  - shows recent active sessions across users

## Expiration Rules
- Web sessions use the configured session lifetime from CodeIgniter session config
- API sessions currently use a 24-hour JWT/session lifetime
- Expired sessions are marked as `expired` instead of remaining active

## Testing Checklist
1. Login through the web dashboard.
2. Open Settings and confirm `Current Session` is visible.
3. Confirm one row is created in `user_sessions`.
4. Browse to another dashboard page and confirm `last_activity_at` updates.
5. Logout and confirm the session status changes from `active` to `logged_out`.
6. Login from another browser/incognito window and confirm multiple active sessions appear.
7. Use an authenticated API flow and confirm an `api` session row is created.

## Notes
- `::1` is normal for localhost over IPv6.
- `127.0.0.1` may appear instead depending on how the local server is accessed.
- Device labels are inferred from the browser user agent and may vary slightly by environment.

## Future Enhancements
- Add force logout for a selected session
- Add "logout all other sessions"
- Add session history/audit screen
- Add suspicious session alerts
- Add geolocation or city-level login visibility

## Status
Session tracking is implemented and migrated into the database.
