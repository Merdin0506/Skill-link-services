<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');

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

    // Legacy Records Routes (for backward compatibility)
    $routes->get('records', 'RecordsController::index');
    $routes->get('records/(:num)', 'RecordsController::show/$1');
    $routes->post('records', 'RecordsController::create');
    $routes->put('records/(:num)', 'RecordsController::update/$1');
    $routes->delete('records/(:num)', 'RecordsController::delete/$1');
});

