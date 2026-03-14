# SkillLink Services — RBAC Analysis

> Generated: 2026-03-14  
> Purpose: Full system analysis of implemented features, endpoints, roles, and access control — to be used as the foundation for implementing formal Role-Based Access Control (RBAC).

---

## SYSTEM MODULES

| # | Module | Description |
|---|--------|-------------|
| 1 | **Authentication** | Web session login/logout, API JWT login/register, password change |
| 2 | **User Management** | CRUD for all user types; soft delete; super admin protection |
| 3 | **Service Catalog** | Service listing by category, popular services, CRUD |
| 4 | **Service Booking** | Full booking lifecycle: pending → scheduled → in_progress → completed/cancelled |
| 5 | **Worker Job Actions** | Accept, start, complete jobs; admin worker assignment |
| 6 | **Payment Processing** | Customer payments (GCash), worker payouts, payment status tracking |
| 7 | **Finance Reporting** | Payment reports, payout summaries, revenue analytics |
| 8 | **Service Records** | Transaction records with soft delete, filtering, restore |
| 9 | **Reviews & Ratings** | Post-completion customer reviews, worker rating aggregation |
| 10 | **Role Dashboards** | Per-role stats, analytics, recent activity (super_admin, admin, finance, worker, customer) |
| 11 | **Profile Management** | View/edit profile, change password, delete own account |
| 12 | **Settings** | System settings page (admin only) |
| 13 | **Desktop App** | Electron app — JWT login, profile display, connects to backend API |

---

## CONTROLLERS

### Web Controllers (`Backend/app/Controllers/`)

| Controller | File | Responsibility |
|---|---|---|
| `Auth` | Auth.php | Web login/register forms, session creation, logout |
| `Dashboard` | Dashboard.php | Role-based dashboard, user CRUD, bookings view, payments view, service records, worker views, customer views, profile, settings |
| `Finance` | Finance.php | Finance-only: payment recording, payout recording, financial reports |
| `Bookings` | Bookings.php | Customer booking creation, cancellation, view |
| `WorkerActions` | WorkerActions.php | Worker: accept/start/complete job; Admin: assign worker |
| `Home` | Home.php | Root page redirect |
| `BaseController` | BaseController.php | Shared base for all web controllers |

### API Controllers (`Backend/app/Controllers/API/`)

| Controller | File | Responsibility |
|---|---|---|
| `AuthController` | AuthController.php | REST: register, login (JWT), profile view/update, change password, logout |
| `UsersController` | UsersController.php | REST: user CRUD, worker/customer/staff lists, statistics, search, profile image |
| `ServicesController` | ServicesController.php | REST: service CRUD, categories, popular, by-category |
| `BookingsController` | BookingsController.php | REST: booking CRUD, assign worker, start/complete/cancel, available workers |
| `PaymentsController` | PaymentsController.php | REST: payment list/show, customer payment, worker payout, process, statistics, revenue report |
| `ReviewsController` | ReviewsController.php | REST: review CRUD, worker rating, top workers, flag/unflag, statistics |
| `RecordsController` | RecordsController.php | REST: service record CRUD with soft delete |
| `DashboardController` | DashboardController.php | REST: dashboard data, stats, analytics, bookings summary |

---

## USER ROLES

| Role | Description | Created By |
|------|-------------|------------|
| `super_admin` | Full system access; only one allowed; cannot be deleted or demoted | Seeder only |
| `admin` | User management, booking management, service records, system settings | super_admin |
| `finance` | Payment recording, payout processing, financial reports | super_admin / admin |
| `worker` | Accept/start/complete jobs, view earnings, own job history | Registration or admin |
| `customer` | Create bookings, view own bookings/payments, submit reviews | Self-registration or admin |

---

## ALL API ENDPOINTS

### Authentication — `/api/auth`

| Method | Endpoint | Purpose |
|--------|----------|---------|
| POST | `/api/auth/register` | Register new user (customer or worker only) |
| POST | `/api/auth/login` | Login, returns JWT token |
| GET | `/api/auth/profile` | Get authenticated user's profile |
| PUT | `/api/auth/profile` | Update authenticated user's profile |
| POST | `/api/auth/change-password` | Change password |
| POST | `/api/auth/logout` | Logout (invalidate token client-side) |

### Users — `/api/users`

| Method | Endpoint | Purpose |
|--------|----------|---------|
| GET | `/api/users` | List users (filterable by type/status) |
| POST | `/api/users` | Create user (admin only) |
| GET | `/api/users/:id` | Get single user |
| PUT | `/api/users/:id` | Update user |
| DELETE | `/api/users/:id` | Soft-delete user |
| GET | `/api/users/workers` | List workers |
| GET | `/api/users/customers` | List customers |
| GET | `/api/users/admin-staff` | List admin/finance/super_admin staff |
| GET | `/api/users/dashboard/:id` | Get dashboard data for a user |
| GET | `/api/users/statistics` | User count statistics |
| GET | `/api/users/search` | Search users by name/email |
| PUT | `/api/users/:id/profile-image` | Update profile image |

### Services — `/api/services`

| Method | Endpoint | Purpose |
|--------|----------|---------|
| GET | `/api/services` | List all services |
| POST | `/api/services` | Create service |
| GET | `/api/services/:id` | Get service details |
| PUT | `/api/services/:id` | Update service |
| DELETE | `/api/services/:id` | Delete service |
| GET | `/api/services/categories` | Get all categories |
| GET | `/api/services/popular` | Get popular services |
| GET | `/api/services/category/:slug` | Get services by category |

### Bookings — `/api/bookings`

| Method | Endpoint | Purpose |
|--------|----------|---------|
| GET | `/api/bookings` | List bookings |
| POST | `/api/bookings` | Create booking |
| GET | `/api/bookings/:id` | Get booking details |
| POST | `/api/bookings/assign-worker` | Assign worker to booking |
| PUT | `/api/bookings/:id/start` | Mark booking as in_progress |
| PUT | `/api/bookings/:id/complete` | Mark booking as completed |
| PUT | `/api/bookings/:id/cancel` | Cancel booking |
| GET | `/api/bookings/available-workers/:id` | Get available workers for a booking |
| GET | `/api/bookings/statistics` | Booking count statistics |

### Payments — `/api/payments`

| Method | Endpoint | Purpose |
|--------|----------|---------|
| GET | `/api/payments` | List payments |
| GET | `/api/payments/:id` | Get payment details |
| POST | `/api/payments/customer` | Record customer payment |
| POST | `/api/payments/worker` | Record worker payout |
| PUT | `/api/payments/:id/process` | Process/confirm payment |
| GET | `/api/payments/methods` | Get available payment methods |
| GET | `/api/payments/statistics` | Payment statistics |
| GET | `/api/payments/worker-earnings/:id` | Get worker earnings |
| GET | `/api/payments/revenue-report` | Full revenue report |

### Reviews — `/api/reviews`

| Method | Endpoint | Purpose |
|--------|----------|---------|
| GET | `/api/reviews` | List reviews |
| POST | `/api/reviews` | Submit a review |
| GET | `/api/reviews/:id` | Get review details |
| GET | `/api/reviews/worker/:id` | Get worker rating |
| GET | `/api/reviews/top-workers` | Top-rated workers |
| PUT | `/api/reviews/:id/status` | Update review status (flag/unflag) |
| GET | `/api/reviews/can-review` | Check if user can review a booking |
| GET | `/api/reviews/statistics` | Review statistics |
| GET | `/api/reviews/recent` | Recent reviews |
| GET | `/api/reviews/flagged` | Flagged reviews |

### Service Records — `/api/records`

| Method | Endpoint | Purpose |
|--------|----------|---------|
| GET | `/api/records` | List service records (with filters, pagination) |
| GET | `/api/records/:id` | Get single record |
| POST | `/api/records` | Create record |
| PUT | `/api/records/:id` | Update record |
| DELETE | `/api/records/:id` | Soft-delete record |

### Dashboard — `/api/dashboard`

| Method | Endpoint | Purpose |
|--------|----------|---------|
| GET | `/api/dashboard/data` | Role-specific dashboard data |
| GET | `/api/dashboard/stats` | Summary statistics |
| GET | `/api/dashboard/analytics` | Analytics data |
| GET | `/api/dashboard/bookings` | Recent bookings summary |

---

### Web Routes (non-API)

| Route | Controller Method | Allowed Roles |
|-------|-------------------|---------------|
| `GET /dashboard` | Dashboard::index | All authenticated |
| `GET /admin/users` | Dashboard::users | admin, super_admin |
| `GET /admin/users/create` | Dashboard::userCreate | admin, super_admin |
| `POST /admin/users/store` | Dashboard::userStore | admin, super_admin |
| `GET /admin/users/edit/:id` | Dashboard::userEdit | admin, super_admin |
| `POST /admin/users/update/:id` | Dashboard::userUpdate | admin, super_admin |
| `POST /admin/users/delete/:id` | Dashboard::userDelete | admin, super_admin |
| `GET /admin/bookings` | Dashboard::bookings | admin, super_admin |
| `GET /admin/payments` | Dashboard::payments | admin, super_admin |
| `GET /admin/records` | Dashboard::records | admin, super_admin |
| `GET /admin/records/edit/:id` | Dashboard::recordEdit | admin, super_admin |
| `POST /admin/records/update/:id` | Dashboard::recordUpdate | admin, super_admin |
| `POST /admin/records/delete/:id` | Dashboard::recordDelete | admin, super_admin |
| `POST /admin/records/restore/:id` | Dashboard::recordRestore | admin, super_admin |
| `POST /admin/assign-worker` | WorkerActions::adminAssign | admin only |
| `GET /finance/payments` | Finance::payments | finance only |
| `GET /finance/payments/record/:id` | Finance::recordPaymentForm | finance only |
| `POST /finance/payments/store/:id` | Finance::storePayment | finance only |
| `GET /finance/payouts` | Finance::payouts | finance only |
| `GET /finance/payouts/record/:id` | Finance::recordPayoutForm | finance only |
| `POST /finance/payouts/store/:id` | Finance::storePayout | finance only |
| `GET /finance/reports` | Finance::reports | finance only |
| `GET /worker/available-jobs` | Dashboard::availableJobs | worker only |
| `GET /worker/my-jobs` | Dashboard::myJobs | worker only |
| `GET /worker/job/:id` | Dashboard::workerJobDetails | worker only |
| `GET /worker/earnings` | Dashboard::earnings | worker only |
| `POST /worker/accept-job/:id` | WorkerActions::acceptJob | worker only |
| `POST /worker/start-job/:id` | WorkerActions::startJob | worker only |
| `GET /worker/complete-job-form/:id` | WorkerActions::completeJobForm | worker only |
| `POST /worker/complete-job/:id` | WorkerActions::completeJob | worker only |
| `GET /customer/bookings` | Dashboard::myBookings | customer only |
| `GET /customer/services` | Dashboard::services | customer only |
| `GET /customer/services/:id` | Dashboard::serviceDetails | customer only |
| `GET /customer/payments` | Dashboard::myPayments | customer only |
| `GET /customer/reviews/create/:id` | Dashboard::createReview | customer only |
| `POST /customer/reviews/store/:id` | Dashboard::storeReview | customer only |
| `POST /bookings/create` | Bookings::store | customer (implicit) |
| `POST /bookings/cancel/:id` | Bookings::cancel | customer (implicit) |
| `GET /bookings/view/:id` | Bookings::view | any authenticated |
| `GET /profile` | Dashboard::profile | any authenticated |
| `GET /profile/edit` | Dashboard::profileEdit | any authenticated |
| `POST /profile/update` | Dashboard::profileUpdate | any authenticated |
| `GET /profile/change-password` | Dashboard::changePassword | any authenticated |
| `POST /profile/update-password` | Dashboard::updatePassword | any authenticated |
| `POST /profile/delete-account` | Dashboard::deleteAccount | any authenticated |
| `GET /settings` | Dashboard::settings | any authenticated |

---

## CURRENT ACCESS CONTROL

### What is already implemented

#### 1. Session Authentication Filter — `DashboardAuth`

File: `Backend/app/Filters/DashboardAuth.php`  
Configured in: `Backend/app/Config/Filters.php`

```php
'dashboardauth' => ['before' => ['dashboard/*', 'admin/*', 'worker/*', 'customer/*', 'finance/*']]
```

This filter runs **before every web route** under those prefixes. It checks:
- `session.user_id` exists (user is logged in)
- `session.user_role` is one of the 5 valid roles

If either fails → redirect to `/login`.

**Gap:** The filter only checks *authentication* (are you logged in?), not *authorization* (are you allowed here?). A logged-in `customer` can manually navigate to `/admin/users` and the filter will not block them — the block happens inside the controller method itself.

---

#### 2. Per-Method Role Checks (Manual RBAC — inline)

Each controller method contains its own `if (!in_array(...))` guard:

**Pattern used in Dashboard.php (admin-only routes):**
```php
if (!in_array($this->session->get('user_role'), ['admin', 'super_admin'], true)) {
    return redirect()->to('/dashboard');
}
```

**Pattern used in Dashboard.php (worker-only routes):**
```php
if ($this->session->get('user_role') !== 'worker') {
    return redirect()->to('/dashboard');
}
```

**Pattern used in Finance.php (finance-only routes):**
```php
if ($this->session->get('user_role') !== 'finance') {
    return redirect()->to('/dashboard');
}
```

**Pattern used in WorkerActions.php:**
```php
if ($this->session->get('user_role') !== 'worker') { ... }  // worker actions
if ($this->session->get('user_role') !== 'admin') { ... }   // adminAssign()
```

#### 3. Business Logic Protections

Beyond routing, the following domain-level guards exist:

| Rule | Location |
|------|----------|
| Only one super_admin allowed | Dashboard::userStore, Dashboard::userUpdate, API UsersController::update |
| Super admin cannot be deleted | Dashboard::userDelete, API UsersController::delete |
| Admin cannot delete own account | Dashboard::userDelete |
| API registration limited to customer/worker | API AuthController::register |
| JWT required for API profile endpoints | API AuthController::profile, updateProfile, changePassword |

#### 4. API Layer

The REST API (`/api/*`) has **no middleware/filter enforcing authentication or role checks** on most endpoints. The `AuthController::profile` endpoint manually checks the `Authorization: Bearer` JWT header, but other API controllers (`UsersController`, `BookingsController`, etc.) have **no per-request token verification**. This is a known gap to address during RBAC implementation.

---

## GAPS TO ADDRESS FOR RBAC

| # | Gap | Recommendation |
|---|-----|----------------|
| 1 | `DashboardAuth` only checks login, not role-per-route | Extend filter or create role-specific filters (e.g., `AdminOnly`, `FinanceOnly`) |
| 2 | Role checks are duplicated inline in every method | Centralize into a `BaseController::requireRole()` helper or use CI4 filter `$arguments` |
| 3 | API endpoints have no authentication middleware | Add a `JWTAuth` filter to all `/api/*` routes except `auth/login` and `auth/register` |
| 4 | No role enforcement on API endpoints (any token can call any endpoint) | Add role checks inside API controllers or via route filters with `$arguments` |
| 5 | `customer` role in Dashboard serves as both service requester and reviewer — no scoping | Define explicit route groups per role at the router level |

---

## DATABASE SCHEMA SUMMARY

| Table | Key Columns | Notes |
|-------|-------------|-------|
| `users` | id, first_name, last_name, email, password, user_type, status, deleted_at | Soft delete enabled (2026-03-14) |
| `services` | id, name, category, base_price, estimated_duration, status | 5 categories |
| `bookings` | id, customer_id, worker_id, service_id, status, scheduled_at, started_at, completed_at | Full lifecycle |
| `payments` | id, booking_id, amount, payment_type, payment_method, status | customer + worker payout |
| `reviews` | id, booking_id, customer_id, worker_id, rating, comment, status | Post-completion |
| `service_records` | id, booking_id, customer_id, worker_id, status, payment_status, deleted_at | Soft delete |
