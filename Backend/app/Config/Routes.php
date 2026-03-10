<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');

// Authentication Routes
$routes->get('login', function() { return redirect()->to('/auth/login'); }); // Redirect shorthand
$routes->get('auth/login', 'Auth::login');
$routes->post('auth/doLogin', 'Auth::doLogin');
$routes->get('auth/register', 'Auth::register');
$routes->post('auth/doRegister', 'Auth::doRegister');
$routes->get('logout', 'Auth::logout');

// Dashboard Routes
$routes->get('dashboard', 'Dashboard::index');
$routes->get('admin/users', 'Dashboard::users');
$routes->get('admin/users/create', 'Dashboard::userCreate');
$routes->post('admin/users/store', 'Dashboard::userStore');
$routes->get('admin/users/edit/(:num)', 'Dashboard::userEdit/$1');
$routes->post('admin/users/update/(:num)', 'Dashboard::userUpdate/$1');
$routes->post('admin/users/delete/(:num)', 'Dashboard::userDelete/$1');
$routes->get('admin/bookings', 'Dashboard::bookings');
$routes->get('admin/payments', 'Dashboard::payments');
$routes->get('worker/available-jobs', 'Dashboard::availableJobs');
$routes->get('worker/my-jobs', 'Dashboard::myJobs');
$routes->get('worker/job/(:num)', 'Dashboard::workerJobDetails/$1');
$routes->get('worker/earnings', 'Dashboard::earnings');
$routes->get('customer/bookings', 'Dashboard::myBookings');
$routes->get('customer/services', 'Dashboard::services');
$routes->get('customer/services/(:num)', 'Dashboard::serviceDetails/$1');
$routes->get('customer/payments', 'Dashboard::myPayments');
$routes->get('customer/reviews/create/(:num)', 'Dashboard::createReview/$1');
$routes->post('customer/reviews/store/(:num)', 'Dashboard::storeReview/$1');

// Finance Routes
$routes->get('finance/payments', 'Finance::payments');
$routes->get('finance/payments/record/(:num)', 'Finance::recordPaymentForm/$1');
$routes->post('finance/payments/store/(:num)', 'Finance::storePayment/$1');
$routes->get('finance/payouts', 'Finance::payouts');
$routes->get('finance/payouts/record/(:num)', 'Finance::recordPayoutForm/$1');
$routes->post('finance/payouts/store/(:num)', 'Finance::storePayout/$1');
$routes->get('finance/reports', 'Finance::reports');

$routes->get('profile', 'Dashboard::profile');
$routes->get('profile/edit', 'Dashboard::profileEdit');
$routes->post('profile/update', 'Dashboard::profileUpdate');
$routes->get('profile/change-password', 'Dashboard::changePassword');
$routes->post('profile/update-password', 'Dashboard::updatePassword');
$routes->post('profile/delete-account', 'Dashboard::deleteAccount');
$routes->get('settings', 'Dashboard::settings');

// Booking Routes
$routes->post('bookings/create', 'Bookings::store');
$routes->post('bookings/cancel/(:num)', 'Bookings::cancel/$1');
$routes->get('bookings/view/(:num)', 'Bookings::view/$1');

// Worker Action Routes
$routes->post('worker/accept-job/(:num)', 'WorkerActions::acceptJob/$1');
$routes->post('worker/start-job/(:num)', 'WorkerActions::startJob/$1');
$routes->get('worker/complete-job-form/(:num)', 'WorkerActions::completeJobForm/$1');
$routes->post('worker/complete-job/(:num)', 'WorkerActions::completeJob/$1');

// Admin Action Routes
$routes->post('admin/assign-worker', 'WorkerActions::adminAssign');

// Dashboard API Routes
$routes->group('api', ['namespace' => 'App\Controllers\API'], function($routes) {
    // Dashboard Routes
    $routes->get('dashboard/data', 'DashboardController::data');
    $routes->get('dashboard/stats', 'DashboardController::stats');
    $routes->get('dashboard/analytics', 'DashboardController::analytics');
    $routes->get('dashboard/bookings', 'DashboardController::bookings');
});

// API Routes for SkillLink Services
$routes->group('api', ['namespace' => 'App\Controllers\API'], function($routes) {
    // Authentication Routes
    $routes->post('auth/register', 'AuthController::register');
    $routes->post('auth/login', 'AuthController::login');
    $routes->get('auth/profile', 'AuthController::profile');
    $routes->put('auth/profile', 'AuthController::updateProfile');
    $routes->post('auth/change-password', 'AuthController::changePassword');
    $routes->post('auth/logout', 'AuthController::logout');

    // Services Routes
    $routes->get('services', 'ServicesController::index');
    $routes->post('services', 'ServicesController::store');
    $routes->get('services/(:num)', 'ServicesController::show/$1');
    $routes->put('services/(:num)', 'ServicesController::update/$1');
    $routes->delete('services/(:num)', 'ServicesController::delete/$1');
    $routes->get('services/categories', 'ServicesController::categories');
    $routes->get('services/popular', 'ServicesController::popular');
    $routes->get('services/category/(:segment)', 'ServicesController::byCategory/$1');

    // Users Routes
    $routes->get('users', 'UsersController::index');
    $routes->post('users', 'UsersController::store'); // Admin only
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

    // Bookings Routes
    $routes->get('bookings', 'BookingsController::index');
    $routes->post('bookings', 'BookingsController::store');
    $routes->get('bookings/(:num)', 'BookingsController::show/$1');
    $routes->post('bookings/assign-worker', 'BookingsController::assignWorker');
    $routes->put('bookings/(:num)/start', 'BookingsController::startBooking/$1');
    $routes->put('bookings/(:num)/complete', 'BookingsController::completeBooking/$1');
    $routes->put('bookings/(:num)/cancel', 'BookingsController::cancelBooking/$1');
    $routes->get('bookings/available-workers/(:num)', 'BookingsController::availableWorkers/$1');
    $routes->get('bookings/statistics', 'BookingsController::statistics');

    // Payments Routes
    $routes->get('payments', 'PaymentsController::index');
    $routes->get('payments/(:num)', 'PaymentsController::show/$1');
    $routes->post('payments/customer', 'PaymentsController::createCustomerPayment');
    $routes->post('payments/worker', 'PaymentsController::createWorkerPayout');
    $routes->put('payments/(:num)/process', 'PaymentsController::processPayment/$1');
    $routes->get('payments/methods', 'PaymentsController::paymentMethods');
    $routes->get('payments/statistics', 'PaymentsController::statistics');
    $routes->get('payments/worker-earnings/(:num)', 'PaymentsController::workerEarnings/$1');
    $routes->get('payments/revenue-report', 'PaymentsController::revenueReport');

    // Reviews Routes
    $routes->get('reviews', 'ReviewsController::index');
    $routes->post('reviews', 'ReviewsController::store');
    $routes->get('reviews/(:num)', 'ReviewsController::show/$1');
    $routes->get('reviews/worker/(:num)', 'ReviewsController::workerRating/$1');
    $routes->get('reviews/top-workers', 'ReviewsController::topWorkers');
    $routes->put('reviews/(:num)/status', 'ReviewsController::updateStatus/$1');
    $routes->get('reviews/can-review', 'ReviewsController::canReview');
    $routes->get('reviews/statistics', 'ReviewsController::statistics');
    $routes->get('reviews/recent', 'ReviewsController::recentReviews');
    $routes->get('reviews/flagged', 'ReviewsController::flaggedReviews');

    // Records Routes
    $routes->get('records', 'RecordsController::index');
    $routes->get('records/(:num)', 'RecordsController::show/$1');
    $routes->post('records', 'RecordsController::create');
    $routes->put('records/(:num)', 'RecordsController::update/$1');
    $routes->delete('records/(:num)', 'RecordsController::delete/$1');
});

// Dashboard Records Routes (admin only)
$routes->get('admin/records', 'Dashboard::records');
$routes->get('admin/records/edit/(:num)', 'Dashboard::recordEdit/$1');
$routes->post('admin/records/update/(:num)', 'Dashboard::recordUpdate/$1');
$routes->post('admin/records/delete/(:num)', 'Dashboard::recordDelete/$1');
$routes->post('admin/records/restore/(:num)', 'Dashboard::recordRestore/$1');

