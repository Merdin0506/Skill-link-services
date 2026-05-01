<?php
// Test security logging directly
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
var_dump($dashboardData);

echo "Test completed!\n";
