<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');

$routes->group('api', function($routes) {

    $routes->get('records', 'Api\RecordsController::index');
    $routes->get('records/(:num)', 'Api\RecordsController::show/$1');

    $routes->post('records', 'Api\RecordsController::create');
    $routes->put('records/(:num)', 'Api\RecordsController::update/$1');

    $routes->delete('records/(:num)', 'Api\RecordsController::delete/$1');

});
