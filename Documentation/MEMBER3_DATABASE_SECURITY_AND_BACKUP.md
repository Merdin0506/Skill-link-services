# Member 3: Database Security and Backup Management

## Overview

This document summarizes the backend work completed for Member 3 in the Skill Link Services project. The implementation focuses on three areas:

1. Secure user data storage
2. Encryption of sensitive user information
3. Automated backup and restoration support for disaster recovery
4. Admin-facing backup management inside the system

All changes were implemented in the `Backend` project only.

## What Was Implemented

### 1. Secure User Storage Improvements

A new migration was added to improve the `users` table:

- File: `Backend/app/Database/Migrations/2026-04-11-090000_AddSecureUserStorageControls.php`

This migration adds and updates the following:

- Converts `phone` to encrypted-at-rest storage
- Converts `address` to encrypted-at-rest storage
- Adds `phone_last4` for limited lookup/reference use
- Adds `failed_login_attempts`
- Adds `locked_until`
- Adds `last_login_at`
- Adds `password_changed_at`
- Adds supporting indexes for security and user-management queries

These changes improve how sensitive user data is stored and help support login protection features.

### 2. Sensitive Data Encryption

A reusable encryption library was added:

- File: `Backend/app/Libraries/SensitiveDataCipher.php`

This library is used to:

- encrypt phone numbers before storage
- encrypt addresses before storage
- decrypt those values when records are read
- derive the last 4 digits of the phone number for partial reference

The user model was updated to automatically apply this behavior:

- File: `Backend/app/Models/UserModel.php`

The model now:

- encrypts sensitive fields before insert/update
- decrypts them after retrieval
- updates password change timestamps
- supports failed login tracking
- supports temporary lockout logic

### 3. Login Protection

Authentication logic was improved in both web and API login flows:

- File: `Backend/app/Controllers/Auth.php`
- File: `Backend/app/Controllers/API/AuthController.php`

The login process now supports:

- failed login counting
- temporary account lockout after repeated failed attempts
- reset of failed-attempt counters after successful login
- updating `last_login_at` after successful login

This reduces brute-force login risk and strengthens account protection.

### 4. Backup Configuration

A dedicated backup config file was added:

- File: `Backend/app/Config/Backup.php`

This allows configuration of:

- backup storage path
- retention period in days
- `mysqldump` binary path
- `mysql` binary path
- automatic pre-restore backup creation

Related sample environment settings were also added:

- File: `Backend/env.example`

### 5. Automated Backup and Restore Support

A backup manager service was added:

- File: `Backend/app/Libraries/DatabaseBackupManager.php`

This service handles:

- creating SQL backups
- compressing backups into `.sql.gz`
- listing backup files
- removing expired backups based on retention policy
- restoring a selected backup
- creating a pre-restore backup automatically

### 6. CLI Commands for Disaster Recovery

Two CodeIgniter Spark commands were added:

- File: `Backend/app/Commands/DatabaseBackup.php`
- File: `Backend/app/Commands/DatabaseRestore.php`

Available commands:

```bash
php spark db:backup
php spark db:restore <backup-file>
```

These provide a standard backend interface for backup and recovery operations.

### 7. Windows PowerShell Helper Scripts

Two helper scripts were added for easier execution or task scheduling on Windows:

- File: `Backend/scripts/run-database-backup.ps1`
- File: `Backend/scripts/run-database-restore.ps1`

Example usage:

```powershell
.\scripts\run-database-backup.ps1
.\scripts\run-database-restore.ps1 backup_file.sql.gz
```

These can be used with Windows Task Scheduler for automatic backups.

### 8. Admin Backup Management Page

An actual admin page was added inside the backend dashboard so backup management can be used from the system UI instead of only from the terminal.

Files involved:

- File: `Backend/app/Controllers/Dashboard.php`
- File: `Backend/app/Views/dashboard/admin_backups.php`
- File: `Backend/app/Views/layouts/sidebar.php`
- File: `Backend/app/Config/Routes.php`

The admin panel now includes:

- a `Backups` item in the admin sidebar
- a backup management page at `/admin/backups`
- a `Create Backup Now` button
- a list of available backup files
- a `Restore` action for each backup
- summary cards showing backup count, storage size, and latest backup

This makes the backup and restore feature visible and usable directly in the system interface.

## Files Added

- `Backend/app/Config/Backup.php`
- `Backend/app/Libraries/SensitiveDataCipher.php`
- `Backend/app/Libraries/DatabaseBackupManager.php`
- `Backend/app/Commands/DatabaseBackup.php`
- `Backend/app/Commands/DatabaseRestore.php`
- `Backend/app/Database/Migrations/2026-04-11-090000_AddSecureUserStorageControls.php`
- `Backend/app/Views/dashboard/admin_backups.php`
- `Backend/scripts/run-database-backup.ps1`
- `Backend/scripts/run-database-restore.ps1`

## Files Updated

- `Backend/app/Models/UserModel.php`
- `Backend/app/Controllers/Auth.php`
- `Backend/app/Controllers/API/AuthController.php`
- `Backend/app/Controllers/Dashboard.php`
- `Backend/app/Config/Routes.php`
- `Backend/app/Views/layouts/sidebar.php`
- `Backend/.env`
- `Backend/env.example`

## How To Use

### Step 1. Configure encryption

Set a real encryption key in your backend `.env` file:

```env
encryption.key = your-secure-random-key
```

In this project, the encryption key has already been generated in the real `Backend/.env` file.

### Step 2. Ensure database tools are installed

Make sure the following are available in your system PATH:

- `mysqldump`
- `mysql`

If needed, update the backup config values in `.env` or `Backup.php`.

### Step 3. Run the migration

From the `Backend` folder:

```bash
php spark migrate
```

### Step 4. Create a backup

```bash
php spark db:backup
```

Or from the admin system page:

- log in as `admin` or `super_admin`
- open `Admin > Backups`
- click `Create Backup Now`

### Step 5. Restore a backup

```bash
php spark db:restore your_backup_file.sql.gz
```

Or from the admin system page:

- open `Admin > Backups`
- choose a listed backup
- click `Restore`

## Verification Performed

The following checks were completed:

- PHP syntax validation on all added and modified backend PHP files
- verification that `php spark db:backup` is registered
- verification that `php spark db:restore` is registered
- verification that `php spark` loads successfully after fixing `.env` formatting
- verification that the admin backup routes, controller methods, and view files are in place

## Notes

- The implementation was limited to the backend as requested.
- No desktop files were modified.
- Backup commands support MySQL/MariaDB through the configured `MySQLi` connection.
- A real production backup/restore run should still be tested in your local environment after `.env` setup.
- The real `.env` file was also fixed so that invalid dotenv syntax would not block `php spark`.

## Summary

The backend now has:

- more secure user-storage structure
- field-level protection for sensitive user data
- failed-login and lockout support
- automated backup tooling
- restore tooling for disaster recovery
- an admin backup management page inside the dashboard
- Windows-friendly scripts for manual or scheduled execution
