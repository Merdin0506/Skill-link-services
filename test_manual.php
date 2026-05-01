<?php
// Manual test for security logging
echo "Testing security system...\n";

// Initialize CodeIgniter
$app = new CodeIgniter\CodeIgniter();
$app->initialize();
$context = $app->getContext();

// Create security controller
$securityController = new App\Controllers\SecurityController();

// Test logging a failed login
echo "Logging test security event...\n";
$securityController->logEvent(
    'login_failed',
    'medium',
    'Test failed login from manual test script',
    null,
    'test@example.com'
);

echo "Event logged!\n";

// Get dashboard data
$dashboardData = $securityController->getDashboardData();

echo "Dashboard Data:\n";
echo "Total Events: " . $dashboardData['total_events'] . "\n";
echo "Failed Logins: " . $dashboardData['failed_logins'] . "\n";
echo "Blocked IPs: " . $dashboardData['blocked_ips'] . "\n";
echo "Unread Notifications: " . $dashboardData['unread_notifications'] . "\n";

if (!empty($dashboardData['recent_events'])) {
    echo "\nRecent Events:\n";
    foreach ($dashboardData['recent_events'] as $event) {
        echo "- " . $event['event_type'] . " at " . $event['created_at'] . "\n";
    }
}

echo "\nTest completed successfully!\n";
