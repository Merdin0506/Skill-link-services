# Desktop App Start Guide

## Overview
This document explains exactly how to start the SkillLink desktop app with the existing CodeIgniter backend.

Current behavior:
- Electron loads its own frontend files from Desktop/index.html.
- Electron frontend calls backend API endpoints.
- Backend routes and API logic remain unchanged.

## Prerequisites
- PHP 8.2+
- Composer
- Node.js 18+
- npm
- MySQL running and backend database already configured

## Project Paths
- Backend app root: Backend/
- Desktop app root: Desktop/

## One-Time Setup

### 1. Install backend dependencies
From Backend/:

  composer install

### 2. Install desktop dependencies
From Desktop/:

  npm install

If PowerShell blocks npm scripts, use npm.cmd instead:

  npm.cmd install

### 3. Create backend environment file
If Backend/.env does not exist, copy from template:

  copy env.example .env

Then set at least:
- app.baseURL = 'http://127.0.0.1:8080'
- database.default.* values
- JWT_SECRET value
- encryption.key value

### 4. Prepare database schema
From Backend/:

  php spark migrate

Optional (if you use seed data):

  php spark db:seed InitialDataSeeder

## Daily Startup Steps

### Step 1. Start backend server
Open Terminal 1, go to Backend/, then run:

  php spark serve --host 127.0.0.1 --port 8080

Expected backend base URL:
- http://127.0.0.1:8080

### Step 2. Start desktop app
Open Terminal 2, go to Desktop/, then run:

  npm start

PowerShell alternative:

  npm.cmd start

### Step 3. Login in desktop app
Use valid backend credentials.
The desktop app authenticates against backend API endpoints and then loads the replicated dashboard UI.

## Optional Backend URL Override
If your backend is not on 127.0.0.1:8080, set the environment variable before starting Electron:

  $env:SKILLLINK_API_BASE_URL="http://your-host:your-port"
  npm.cmd start

Notes:
- Main-process API base URL uses SKILLLINK_API_BASE_URL if set.
- Renderer fallback URL is currently set in Desktop/src/renderer/config/appConfig.js.
- Best practice is to keep both pointing to the same backend host and port.

## Team Environment Notes

To keep the whole team on the same local environment:

- Use `Backend/env.example` as the source of truth for shared backend defaults.
- Keep the backend server on `127.0.0.1:8080` or `localhost:8080` unless there is a team-wide change.
- If someone changes the backend port, they should also set `SKILLLINK_API_BASE_URL` before starting the desktop app.
- Secrets stay only in each developer's local `Backend/.env`, never in Git.

## Quick Health Checklist
Before launching desktop, verify:
- Backend is running on the expected host and port.
- Backend dependencies are installed.
- Desktop dependencies are installed.
- Database is reachable by backend.
- JWT secret is configured in Backend/.env.

## Common Startup Issues

### Backend fails to start
Try:

  composer install
  php spark serve --host 127.0.0.1 --port 8080

### Login/API requests fail from desktop
Check:
- Backend URL is correct.
- Backend server is running.
- User credentials are valid.
- Backend returns JSON for API routes.

### JWT class not found
From Backend/:

  composer install
  composer dump-autoload

Restart backend after that.

## Recommended Start Command Pair (Windows PowerShell)
Terminal 1 in Backend/:

  php spark serve --host 127.0.0.1 --port 8080

Terminal 2 in Desktop/:

  npm.cmd start
