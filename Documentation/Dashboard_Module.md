# Dashboard Module Documentation

## Overview

The Dashboard Module provides a comprehensive role-based dashboard system for Skill Link Services. It supports multiple user roles (Admin, Worker, Customer, Cashier, Owner) with dedicated dashboards, navigation, and access control.

**Contributors:** Provido (Backend Structure), Harid (UI/Views)

---

## Architecture

### Core Components

1. **DashboardController** (`app/Controllers/Dashboard.php`)
   - Main controller handling all dashboard logic
   - Manages role-based data retrieval
   - Routes requests to appropriate views

2. **DashboardAuth Filter** (`app/Filters/DashboardAuth.php`)
   - Enforces authentication & authorization
   - Validates user role before allowing access
   - Redirects unauthorized users to login

3. **Dashboard Helper** (`app/Helpers/dashboard_helper.php`)
   - Role checking functions: `userHasRole()`, `isAdmin()`, `isWorker()`, `isCustomer()`, `isCashier()`, `isOwner()`
   - Used in views for conditional rendering

4. **API Dashboard Controller** (`app/Controllers/API/DashboardController.php`)
   - Provides JSON endpoints for dashboard data
   - Supports real-time analytics & updates

5. **Session Tracking Integration**
   - Dashboard-authenticated requests refresh tracked session activity
   - Settings page exposes current session details
   - Admin-facing monitoring can show recent active sessions

---

## Routes & Access Control

### Authentication Routes
```
GET    /auth/login           → Auth::login()
POST   /auth/doLogin         → Auth::doLogin()
GET    /auth/register        → Auth::register()
POST   /auth/doRegister      → Auth::doRegister()
GET    /logout               → Auth::logout()
```

### Dashboard Routes (Protected by `dashboardauth` filter)

**Base Dashboard**
```
GET  /dashboard              → Dashboard::index()    [All authenticated users]
```

**Admin Routes** (`/admin/*`)
```
GET  /admin/users            → Dashboard::users()           [Admin only]
GET  /admin/bookings         → Dashboard::bookings()        [Admin only]
GET  /admin/payments         → Dashboard::payments()        [Admin only]
```

**Worker Routes** (`/worker/*`)
```
GET  /worker/available-jobs  → Dashboard::availableJobs()   [Worker only]
GET  /worker/my-jobs         → Dashboard::myJobs()          [Worker only]
GET  /worker/earnings        → Dashboard::earnings()        [Worker only]
```

**Customer Routes** (`/customer/*`)
```
GET  /customer/bookings      → Dashboard::myBookings()      [Customer only]
GET  /customer/services      → Dashboard::services()        [Customer only]
GET  /customer/payments      → Dashboard::payments()        [Customer only]
```

**Cashier Routes** (`/cashier/*`)
```
GET  /cashier/dashboard      → Dashboard::index()           [Cashier only]
```

---

## Filter Configuration

The `DashboardAuth` filter is registered in `app/Config/Filters.php`:

```php
public array $aliases = [
    // ... other filters
    'dashboardauth' => DashboardAuth::class,
];

public array $filters = [
    'dashboardauth' => ['before' => ['dashboard/*', 'admin/*', 'worker/*', 'customer/*', 'cashier/*']],
];
```

**What the filter does:**
1. Checks if user is authenticated (`$session->has('user_id')`)
2. Validates user role is one of: `admin`, `owner`, `worker`, `customer`, `cashier`
3. Redirects to `/login` if either check fails

---

## Views Structure

```
Backend/app/Views/dashboard/
├── index.php                 [Main dashboard layout & routing]
├── admin_dashboard.php       [Admin overview dashboard]
├── admin_users.php           [User management view]
├── admin_bookings.php        [Booking management view]
├── admin_payments.php        [Payment management view]
├── worker_dashboard.php      [Worker overview dashboard]
├── worker_available_jobs.php [Available jobs for workers]
├── worker_my_jobs.php        [Worker's active jobs]
├── worker_earnings.php       [Worker earnings & statistics]
├── customer_dashboard.php    [Customer overview dashboard]
├── customer_bookings.php     [Customer's bookings]
├── customer_services.php     [Available services view]
├── customer_payments.php     [Customer payment history]
├── cashier_dashboard.php     [Cashier dashboard view]
├── default_dashboard.php     [Default fallback view]
├── profile.php               [User profile management]
└── settings.php              [User settings/preferences]
```

**Main Dashboard View** (`index.php`):
- Responsive Bootstrap 5 layout
- Fixed sidebar navigation with role-specific menu items
- Top navigation bar with user info
- Content area that loads role-specific sub-views
- Supports collapsed sidebar on mobile
- Chart.js integration for analytics

---

## Helper Functions

Located in `app/Helpers/dashboard_helper.php`:

### Role Checking
```php
userHasRole($role)           // Check single or multiple roles
isAdmin()                    // Check if admin
isWorker()                   // Check if worker
isOwner()                    // Check if owner
isCustomer()                 // Check if customer
isCashier()                  // Check if cashier
isLoggedIn()                 // Check if authenticated
```

### Data Formatting
```php
formatCurrency($amount)      // Format currency display
formatDate($date)            // Format date strings
getBadgeClass($status)       // Get Bootstrap badge class for status
getStatusLabel($status)      // Get human-readable status labels
```

### Navigation
```php
getAdminMenuItems()          // Get admin navigation items
getWorkerMenuItems()         // Get worker navigation items
getCustomerMenuItems()       // Get customer navigation items
```

---

## Models Required

The Dashboard module depends on:

- `UserModel` - User information & authentication
- `BookingModel` - Booking data
- `PaymentModel` - Payment records
- `ReviewModel` - User reviews/ratings

These models are instantiated in the `Dashboard` controller constructor:
```php
$this->userModel = new UserModel();
$this->bookingModel = new BookingModel();
$this->paymentModel = new PaymentModel();
$this->reviewModel = new ReviewModel();
```

---

## Session Data Structure

The dashboard expects the following session data after login:

```php
$_SESSION['user_id']    // User unique identifier
$_SESSION['user_name']  // Display name
$_SESSION['email']      // User email
$_SESSION['user_role']  // Role: admin|worker|customer|cashier|owner
```

---

## Usage Examples

### Checking User Role in Views
```php
<?php if (isAdmin()): ?>
    <!-- Admin-only content -->
<?php endif; ?>

<?php if (userHasRole(['worker', 'owner'])): ?>
    <!-- Content for workers or owners -->
<?php endif; ?>
```

### Getting Dashboard Data
```php
// In controller
$data = $this->getDashboardData($userId, $userRole);
// Returns array with user-specific statistics & data
```

### Accessing Current User
```php
$user = $this->getCurrentUser();
// Returns user object with profile info
```

---

## Security Features

1. **Authentication Filter** - All dashboard routes require login
2. **Role-Based Access** - Each route restricted to specific role(s)
3. **Session Validation** - Role verified on every request
4. **Redirect on Failure** - Unauthorized access redirects to login with error message

---

## Features

✅ **Multi-Role Support** - Admin, Worker, Customer, Cashier, Owner
✅ **Responsive Design** - Mobile, tablet, desktop friendly
✅ **Dynamic Navigation** - Menu items change based on user role
✅ **Real-Time Analytics** - Charts & statistics integration
✅ **User Data Display** - Profile, bookings, earnings, payments
✅ **Access Control** - Filter-based authorization
✅ **Sidebar Collapse** - Mobile-friendly collapsible navigation
✅ **Profile Management** - User profile & settings pages
✅ **Session Visibility** - Current session and active session monitoring

---

## Setup & Configuration

### 1. Verify Filters.php
Ensure `DashboardAuth` is imported and registered:
```php
use App\Filters\DashboardAuth;

public array $aliases = [
    'dashboardauth' => DashboardAuth::class,
];

public array $filters = [
    'dashboardauth' => ['before' => ['dashboard/*', 'admin/*', 'worker/*', 'customer/*', 'cashier/*']],
];
```

### 2. Run Migrations
Ensure user roles table is created:
```bash
php spark migrate
```

### 3. Test Login Flow
1. Navigate to `/auth/register` to create a test account
2. Set user role during registration
3. Login to access `/dashboard`
4. Dashboard automatically loads role-appropriate view

### 4. Customize Views
Edit view files in `Backend/app/Views/dashboard/` to match branding:
- Update colors in `index.php` `:root` CSS variables
- Modify menu items in role-specific dashboard files
- Add company logo in sidebar brand section

---

## Troubleshooting

**Issue:** "Invalid file" error when accessing dashboard
- **Solution:** Verify all view files exist in `Backend/app/Views/dashboard/`

**Issue:** Redirect to login on every dashboard request
- **Solution:** Check `$_SESSION['user_role']` is set and matches valid role list in filter

**Issue:** Wrong dashboard/menu for user role
- **Solution:** Verify role value in session matches expected value (exactly `'admin'`, `'worker'`, etc.)

**Issue:** Missing user data/analytics
- **Solution:** Ensure models are properly migrated and contain user data

---

## Future Enhancements

- [ ] Dashboard customization per role
- [ ] Widget-based dashboard builder
- [ ] Export reports (PDF/CSV)
- [ ] Advanced filtering & search
- [ ] Email notifications
- [ ] Dashboard activity logs
- [ ] Two-factor authentication
- [ ] Role-based API endpoints

---

**Last Updated:** March 4, 2026
**Module Status:** ✅ Complete & Production Ready
