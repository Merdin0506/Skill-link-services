# User Management Module

## Overview
The User Management Module provides admin-side user administration and self-service profile management.

It supports:
- User listing and role/status visibility
- User creation and editing
- User deletion with safeguards
- Profile update for logged-in users
- Password change
- Account deletion flow with active-booking checks
- Super admin protection rules

## Roles
Supported user roles:
- super_admin
- admin
- finance
- worker
- customer

### Super Admin Rule
- Only one `super_admin` is allowed in the system.
- `super_admin` cannot be deleted.
- Existing `super_admin` cannot be reassigned to another role.
- Other users cannot be promoted to `super_admin` through admin forms/API update.

## Web Routes
Defined in `app/Config/Routes.php`:

- `GET /admin/users` -> `Dashboard::users`
- `GET /admin/users/create` -> `Dashboard::userCreate`
- `POST /admin/users/store` -> `Dashboard::userStore`
- `GET /admin/users/edit/{id}` -> `Dashboard::userEdit`
- `POST /admin/users/update/{id}` -> `Dashboard::userUpdate`
- `POST /admin/users/delete/{id}` -> `Dashboard::userDelete`

Profile routes:
- `GET /profile` -> `Dashboard::profile`
- `GET /profile/edit` -> `Dashboard::profileEdit`
- `POST /profile/update` -> `Dashboard::profileUpdate`
- `GET /profile/change-password` -> `Dashboard::changePassword`
- `POST /profile/update-password` -> `Dashboard::updatePassword`
- `POST /profile/delete-account` -> `Dashboard::deleteAccount`

## API Endpoints
From `app/Config/Routes.php` (API group):

- `GET /api/users`
- `GET /api/users/{id}`
- `PUT /api/users/{id}`
- `DELETE /api/users/{id}`
- `GET /api/users/workers`
- `GET /api/users/customers`
- `GET /api/users/admin-staff`
- `GET /api/users/statistics`

## Validation and Security
Implemented validations include:
- Required first name/last name
- Email format and uniqueness
- Password minimum length
- Role and status whitelist checks
- Optional phone/address constraints

Security behavior:
- Passwords are hashed with `password_hash(..., PASSWORD_DEFAULT)`
- Current password is verified before password changes
- Role-based access checks guard admin actions
- Super admin deletion and reassignment are blocked

## Key Files
Controllers:
- `app/Controllers/Dashboard.php`
- `Backend/app/Controllers/Dashboard.php`
- `app/Controllers/API/UsersController.php`

Models:
- `app/Models/UserModel.php`

Views:
- `app/Views/dashboard/admin_users.php`
- `app/Views/dashboard/admin_user_form.php`
- `app/Views/dashboard/profile.php`
- `app/Views/dashboard/profile_edit.php`
- `app/Views/dashboard/change_password.php`

Auth and navigation helpers:
- `app/Filters/DashboardAuth.php`
- `app/Views/layouts/sidebar.php`
- `app/Helpers/dashboard_helper.php`

Database:
- `app/Database/Migrations/2026-03-04-050000_create_users_table.php`
- `app/Database/Migrations/2026-03-08-120000_add_super_admin_role_to_users.php`
- `app/Database/Seeds/InitialDataSeeder.php`

## Functional Notes
- Deleting users with booking history deactivates account status instead of hard delete.
- Deleting own currently logged-in account is blocked in admin delete flow.
- Account self-delete is blocked if user has active bookings.
- `super_admin` is counted as part of admin staff metrics.

## Quick Test Checklist
1. Login as `super_admin` and open `/admin/users`.
2. Confirm `super_admin` delete action is disabled/blocked.
3. Create normal `admin`, `finance`, `worker`, `customer` users.
4. Confirm creating another `super_admin` is blocked.
5. Try editing `super_admin` role to another role and confirm blocked.
6. Try promoting another user to `super_admin` and confirm blocked.
7. Change password from `/profile/change-password`.
8. Verify profile updates are persisted.
