<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class LoginTest extends BaseCommand
{
    protected $group = 'App';
    protected $name = 'security:login-test';
    protected $description = 'Test both successful and failed login attempts';

    public function run(array $params)
    {
        CLI::write('Testing Login Attempts Display...', 'green');
        
        $securityController = new \App\Controllers\SecurityController();
        
        // Test failed login attempts
        CLI::write('Creating failed login attempts...', 'yellow');
        for ($i = 1; $i <= 3; $i++) {
            $securityController->logEvent(
                'login_failed',
                'medium',
                "Test failed login attempt #{$i} from test command",
                null,
                "test{$i}@example.com"
            );
        }
        
        // Test successful login
        CLI::write('Creating successful login attempt...', 'yellow');
        $securityController->logEvent(
            'login_success',
            'low',
            'Test successful login from test command',
            1,
            'admin@skilllink.com'
        );
        
        // Test another failed login
        CLI::write('Creating another failed login attempt...', 'yellow');
        $securityController->logEvent(
            'login_failed',
            'medium',
            'Another test failed login attempt',
            null,
            'failed@example.com'
        );
        
        // Get dashboard data
        $dashboardData = $securityController->getDashboardData();
        
        CLI::write('Dashboard Data:', 'cyan');
        CLI::write('Total Events: ' . $dashboardData['total_events'], 'white');
        CLI::write('Failed Logins: ' . $dashboardData['failed_logins'], 'white');
        CLI::write('Blocked IPs: ' . $dashboardData['blocked_ips'], 'white');
        CLI::write('Unread Notifications: ' . $dashboardData['unread_notifications'], 'white');
        
        CLI::write("\nRecent Login Attempts:", 'cyan');
        foreach ($dashboardData['recent_events'] as $event) {
            if (in_array($event['event_type'], ['login_success', 'login_failed'])) {
                $status = $event['event_type'] === 'login_success' ? 'SUCCESS' : 'FAILED';
                $icon = $event['event_type'] === 'login_success' ? '✓' : '✗';
                CLI::write("{$icon} [{$status}] {$event['email']} at {$event['created_at']} from {$event['ip_address']}", 'white');
            }
        }
        
        CLI::write("\nLogin test completed! Check your admin dashboard to see the login attempts table.", 'green');
        CLI::write("URL: http://localhost:8080/dashboard", 'cyan');
    }
}
