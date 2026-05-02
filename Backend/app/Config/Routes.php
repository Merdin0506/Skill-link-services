<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', static fn() => redirect()->to('/auth/login'));

// ─────────────────────────────────────────────────────────────────────────────
// PUBLIC ROUTES (no auth required)
// ─────────────────────────────────────────────────────────────────────────────

$routes->get('login', static fn() => redirect()->to('/auth/login'));
$routes->get('auth/login', 'Auth::login');
$routes->post('auth/doLogin', 'Auth::doLogin');
$routes->get('auth/register', 'Auth::register');
$routes->post('auth/doRegister', 'Auth::doRegister');
$routes->get('auth/verify-otp', 'Auth::verifyOtp');
$routes->post('auth/doVerifyOtp', 'Auth::doVerifyOtp');
$routes->post('auth/resendOtp', 'Auth::resendOtp');
$routes->get('logout', 'Auth::logout');

// ─────────────────────────────────────────────────────────────────────────────
// SHARED AUTHENTICATED WEB ROUTES (any logged-in role)
// ─────────────────────────────────────────────────────────────────────────────

$routes->get('dashboard', 'Dashboard::index', ['filter' => ['dashboardauth', 'permission']]);
$routes->post('dashboard/refresh-security-data', 'Dashboard::refreshSecurityData', ['filter' => ['dashboardauth', 'permission']]);
$routes->get('dashboard/security-events', 'Dashboard::securityEvents', ['filter' => ['dashboardauth', 'role:admin,super_admin', 'permission']]);
$routes->post('dashboard/security/block-ip', 'Dashboard::securityBlockIp', ['filter' => ['dashboardauth', 'role:admin,super_admin', 'permission']]);
$routes->get('dashboard/security-events/export', 'Dashboard::securityEventsExport', ['filter' => ['dashboardauth', 'role:admin,super_admin', 'permission']]);
$routes->get('security/dashboard', 'SecurityController::dashboard', ['filter' => ['dashboardauth', 'role:admin,super_admin', 'permission']]);
$routes->get('security/audit-logs', 'SecurityController::auditLogs', ['filter' => ['dashboardauth', 'role:admin,super_admin', 'permission']]);
$routes->get('security/notifications', 'SecurityController::notifications', ['filter' => ['dashboardauth', 'role:admin,super_admin', 'permission']]);
$routes->get('security/reports', 'SecurityController::reports', ['filter' => ['dashboardauth', 'role:admin,super_admin', 'permission']]);
$routes->get('security/blocked-ips', 'SecurityController::blockedIps', ['filter' => ['dashboardauth', 'role:admin,super_admin', 'permission']]);
$routes->post('security/unblock-ip/(:num)', 'SecurityController::unblockIp/$1', ['filter' => ['dashboardauth', 'role:admin,super_admin', 'permission']]);
$routes->get('security/settings', 'SecurityController::settings', ['filter' => ['dashboardauth', 'role:admin,super_admin', 'permission']]);

$routes->group('profile', ['filter' => ['dashboardauth', 'permission']], function ($routes) {
    $routes->get('/', 'Dashboard::profile');
    $routes->get('edit', 'Dashboard::profileEdit');
    $routes->post('update', 'Dashboard::profileUpdate');
    $routes->get('change-password', 'Dashboard::changePassword');
    $routes->post('update-password', 'Dashboard::updatePassword');
    $routes->post('delete-account', 'Dashboard::deleteAccount');
});

$routes->get('settings', 'Dashboard::settings', ['filter' => ['dashboardauth', 'permission']]);
$routes->get('bookings/view/(:num)', 'Bookings::view/$1', ['filter' => ['dashboardauth', 'permission']]);

// ─────────────────────────────────────────────────────────────────────────────
// ADMIN routes  (super_admin + admin)
// ─────────────────────────────────────────────────────────────────────────────

$routes->group('admin', ['filter' => ['dashboardauth', 'role:admin,super_admin', 'permission']], function ($routes) {
    $routes->get('users', 'Dashboard::users');
    $routes->get('users/create', 'Dashboard::userCreate');
    $routes->post('users/store', 'Dashboard::userStore');
    $routes->get('users/edit/(:num)', 'Dashboard::userEdit/$1');
    $routes->post('users/update/(:num)', 'Dashboard::userUpdate/$1');
    $routes->post('users/delete/(:num)', 'Dashboard::userDelete/$1');
    $routes->post('users/restore/(:num)', 'Dashboard::userRestore/$1');
    $routes->post('users/delete-permanent/(:num)', 'Dashboard::userPermanentDelete/$1');
    $routes->get('bookings', 'Dashboard::bookings');
    $routes->get('payments', 'Dashboard::payments');
    $routes->get('backups', 'Dashboard::backups');
    $routes->post('backups/create', 'Dashboard::backupCreate');
    $routes->post('backups/restore', 'Dashboard::backupRestore');
    $routes->get('records', 'Dashboard::records');
    $routes->get('records/edit/(:num)', 'Dashboard::recordEdit/$1');
    $routes->post('records/update/(:num)', 'Dashboard::recordUpdate/$1');
    $routes->post('records/delete/(:num)', 'Dashboard::recordDelete/$1');
    $routes->post('records/restore/(:num)', 'Dashboard::recordRestore/$1');
    $routes->post('assign-worker', 'WorkerActions::adminAssign');
});

// ─────────────────────────────────────────────────────────────────────────────
// FINANCE routes
// ─────────────────────────────────────────────────────────────────────────────

$routes->group('finance', ['filter' => ['dashboardauth', 'role:finance', 'permission']], function ($routes) {
    $routes->get('payments', 'Finance::payments');
    $routes->get('payments/record/(:num)', 'Finance::recordPaymentForm/$1');
    $routes->post('payments/store/(:num)', 'Finance::storePayment/$1');
    $routes->get('payouts', 'Finance::payouts');
    $routes->get('payouts/record/(:num)', 'Finance::recordPayoutForm/$1');
    $routes->post('payouts/store/(:num)', 'Finance::storePayout/$1');
    $routes->get('reports', 'Finance::reports');
});

// ─────────────────────────────────────────────────────────────────────────────
// WORKER routes
// ─────────────────────────────────────────────────────────────────────────────

$routes->group('worker', ['filter' => ['dashboardauth', 'role:worker', 'permission']], function ($routes) {
    $routes->get('available-jobs', 'Dashboard::availableJobs');
    $routes->get('my-jobs', 'Dashboard::myJobs');
    $routes->get('job/(:num)', 'Dashboard::workerJobDetails/$1');
    $routes->get('earnings', 'Dashboard::earnings');
    $routes->post('accept-job/(:num)', 'WorkerActions::acceptJob/$1');
    $routes->post('start-job/(:num)', 'WorkerActions::startJob/$1');
    $routes->get('complete-job-form/(:num)', 'WorkerActions::completeJobForm/$1');
    $routes->post('complete-job/(:num)', 'WorkerActions::completeJob/$1');
});

// ─────────────────────────────────────────────────────────────────────────────
// CUSTOMER routes
// ─────────────────────────────────────────────────────────────────────────────

$routes->group('customer', ['filter' => ['dashboardauth', 'role:customer', 'permission']], function ($routes) {
    $routes->get('bookings', 'Dashboard::myBookings');
    $routes->get('services', 'Dashboard::services');
    $routes->get('services/(:num)', 'Dashboard::serviceDetails/$1');
    $routes->get('payments', 'Dashboard::myPayments');
    $routes->get('reviews/create/(:num)', 'Dashboard::createReview/$1');
    $routes->post('reviews/store/(:num)', 'Dashboard::storeReview/$1');
});

$routes->group('bookings', ['filter' => ['dashboardauth', 'role:customer', 'permission']], function ($routes) {
    $routes->post('create', 'Bookings::store');
    $routes->post('cancel/(:num)', 'Bookings::cancel/$1');
});

// ─────────────────────────────────────────────────────────────────────────────
// API — PUBLIC (no auth required)
// ─────────────────────────────────────────────────────────────────────────────

$routes->group('api', ['namespace' => 'App\Controllers\API', 'filter' => ['cors']], function ($routes) {
    $routes->post('auth/register', 'AuthController::register');
    $routes->post('auth/login', 'AuthController::login');
    $routes->post('auth/verify-otp', 'AuthController::verifyOtp');
    $routes->post('auth/resend-otp', 'AuthController::resendOtp');
    $routes->get('health', static function () {
        return service('response')->setJSON([
            'status' => 'success',
            'message' => 'Backend is reachable',
            'timestamp' => date('c'),
        ]);
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// API — AUTHENTICATED (any valid JWT)
// ─────────────────────────────────────────────────────────────────────────────

$routes->group('api', ['namespace' => 'App\Controllers\API', 'filter' => ['cors', 'jwtauth', 'permissionapi']], function ($routes) {
    $routes->get('auth/profile', 'AuthController::profile');
    $routes->put('auth/profile', 'AuthController::updateProfile');
    $routes->post('auth/change-password', 'AuthController::changePassword');
    $routes->post('auth/logout', 'AuthController::logout');
    $routes->get('dashboard/data', 'DashboardController::data');
    $routes->get('dashboard/stats', 'DashboardController::stats');
    $routes->get('dashboard/analytics', 'DashboardController::analytics');
    $routes->get('dashboard/bookings', 'DashboardController::bookings');
    $routes->get('services', 'ServicesController::index');
    $routes->get('services/categories', 'ServicesController::categories');
    $routes->get('services/popular', 'ServicesController::popular');
    $routes->get('services/category/(:segment)', 'ServicesController::byCategory/$1');
    $routes->get('services/(:num)', 'ServicesController::show/$1');
    $routes->get('reviews', 'ReviewsController::index');
    $routes->get('reviews/(:num)', 'ReviewsController::show/$1');
    $routes->get('reviews/worker/(:num)', 'ReviewsController::workerRating/$1');
    $routes->get('reviews/top-workers', 'ReviewsController::topWorkers');
    $routes->get('reviews/recent', 'ReviewsController::recentReviews');
    $routes->get('reviews/can-review', 'ReviewsController::canReview');
});

$routes->group('api', ['namespace' => 'App\Controllers\API', 'filter' => ['cors', 'jwtauth', 'roleapi:admin,super_admin', 'permissionapi']], function ($routes) {
    $routes->get('security/dashboard', 'SecurityController::dashboard');
    $routes->get('security/events', 'SecurityController::events');
    $routes->get('security/events/export', 'SecurityController::exportEvents');
    $routes->post('security/block-ip', 'SecurityController::blockIP');
    $routes->get('security/blocked-ips', 'SecurityController::blockedIPs');
    $routes->post('security/unblock-ip/(:num)', 'SecurityController::unblockIP/$1');
    $routes->get('security/statistics', 'SecurityController::statistics');
    $routes->get('security/report', 'SecurityController::report');
});

// ─────────────────────────────────────────────────────────────────────────────
// API — CUSTOMER endpoints
// ─────────────────────────────────────────────────────────────────────────────

$routes->group('api', ['namespace' => 'App\Controllers\API', 'filter' => ['cors', 'jwtauth', 'roleapi:customer,admin,super_admin', 'permissionapi']], function ($routes) {
    $routes->post('bookings', 'BookingsController::store');
    $routes->put('bookings/(:num)/cancel', 'BookingsController::cancelBooking/$1');
    $routes->post('reviews', 'ReviewsController::store');
    $routes->post('payments/customer', 'PaymentsController::createCustomerPayment');
});

// ─────────────────────────────────────────────────────────────────────────────
// API — WORKER endpoints
// ─────────────────────────────────────────────────────────────────────────────

$routes->group('api', ['namespace' => 'App\Controllers\API', 'filter' => ['cors', 'jwtauth', 'roleapi:worker,admin,super_admin', 'permissionapi']], function ($routes) {
    $routes->get('bookings/available', 'BookingsController::availableJobs');
    $routes->put('bookings/(:num)/accept', 'BookingsController::acceptBooking/$1');
    $routes->put('bookings/(:num)/start', 'BookingsController::startBooking/$1');
    $routes->post('bookings/(:num)/complete-with-payment', 'BookingsController::completeBookingWithPayment/$1');
    $routes->put('bookings/(:num)/complete', 'BookingsController::completeBooking/$1');
    $routes->get('payments/worker-earnings/(:num)', 'PaymentsController::workerEarnings/$1');
});

// ─────────────────────────────────────────────────────────────────────────────
// API — FINANCE + ADMIN endpoints
// ─────────────────────────────────────────────────────────────────────────────

$routes->group('api', ['namespace' => 'App\Controllers\API', 'filter' => ['cors', 'jwtauth', 'roleapi:finance,admin,super_admin', 'permissionapi']], function ($routes) {
    $routes->get('payments', 'PaymentsController::index');
    $routes->get('payments/(:num)', 'PaymentsController::show/$1');
    $routes->put('payments/(:num)/process', 'PaymentsController::processPayment/$1');
    $routes->post('payments/worker', 'PaymentsController::createWorkerPayout');
    $routes->get('payments/methods', 'PaymentsController::paymentMethods');
    $routes->get('payments/statistics', 'PaymentsController::statistics');
    $routes->get('payments/revenue-report', 'PaymentsController::revenueReport');
});

// ─────────────────────────────────────────────────────────────────────────────
// API — ADMIN-only endpoints
// ─────────────────────────────────────────────────────────────────────────────

$routes->group('api', ['namespace' => 'App\Controllers\API', 'filter' => ['cors', 'jwtauth', 'roleapi:admin,super_admin', 'permissionapi']], function ($routes) {
    $routes->get('users', 'UsersController::index');
    $routes->post('users', 'UsersController::store');
    $routes->get('users/(:num)', 'UsersController::show/$1');
    $routes->put('users/(:num)', 'UsersController::update/$1');
    $routes->delete('users/(:num)', 'UsersController::delete/$1');
    $routes->get('users/workers', 'UsersController::workers');
    $routes->get('users/customers', 'UsersController::customers');
    $routes->get('users/admin-staff', 'UsersController::adminStaff');
    $routes->get('users/dashboard/(:num)', 'UsersController::dashboard/$1');
    $routes->get('users/statistics', 'UsersController::statistics');
    $routes->get('users/search', 'UsersController::search');
    $routes->put('users/(:num)/profile-image', 'UsersController::updateProfileImage/$1');
    $routes->get('bookings', 'BookingsController::index');
    $routes->get('bookings/(:num)', 'BookingsController::show/$1');
    $routes->post('bookings/assign-worker', 'BookingsController::assignWorker');
    $routes->get('bookings/available-workers/(:num)', 'BookingsController::availableWorkers/$1');
    $routes->get('bookings/statistics', 'BookingsController::statistics');
    $routes->post('services', 'ServicesController::store');
    $routes->put('services/(:num)', 'ServicesController::update/$1');
    $routes->delete('services/(:num)', 'ServicesController::delete/$1');
    $routes->get('records', 'RecordsController::index');
    $routes->get('records/(:num)', 'RecordsController::show/$1');
    $routes->post('records', 'RecordsController::create');
    $routes->put('records/(:num)', 'RecordsController::update/$1');
    $routes->delete('records/(:num)', 'RecordsController::delete/$1');
    $routes->put('reviews/(:num)/status', 'ReviewsController::updateStatus/$1');
    $routes->get('reviews/flagged', 'ReviewsController::flaggedReviews');
    $routes->get('reviews/statistics', 'ReviewsController::statistics');
});
