<?php

// Simple test script to check security logging
require_once 'vendor/autoload.php';

// Initialize CodeIgniter
$app = new CodeIgniter\CodeIgniter();
$app->initialize();
$context = $app->getContext();

// Test security logging
$securityController = new App\Controllers\SecurityController();

echo "Testing security event logging...\n";

// Test logging a failed login
$securityController->logEvent(
    'login_failed',
    'medium',
    'Test failed login from script',
    null,
    'test@example.com'
);

echo "Security event logged successfully!\n";

// Test getting dashboard data
$dashboardData = $securityController->getDashboardData();
echo "Dashboard data:\n";
print_r($dashboardData);

echo "Test completed!\n";
