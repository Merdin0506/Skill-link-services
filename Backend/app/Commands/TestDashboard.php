<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class TestDashboard extends BaseCommand
{
    protected $group = 'App';
    protected $name = 'test:dashboard';
    protected $description = 'Test dashboard data loading';

    public function run(array $params)
    {
        CLI::write('Testing Dashboard Data Loading...', 'green');
        
        // Test the Dashboard controller's getSecurityData method
        try {
            $dashboard = new \App\Controllers\Dashboard();
            
            // Mock session data
            $session = \Config\Services::session();
            $session->set('user_id', 1);
            $session->set('user_role', 'admin');
            $session->set('user_name', 'Admin User');
            $session->set('email', 'admin@skilllink.com');
            
            // Get security data
            $securityData = $dashboard->getSecurityData();
            
            CLI::write('✓ Security data loaded successfully', 'green');
            CLI::write('Security Data:', 'cyan');
            CLI::write('Total Events: ' . ($securityData['total_events'] ?? 0), 'white');
            CLI::write('Failed Logins: ' . ($securityData['failed_logins'] ?? 0), 'white');
            CLI::write('Blocked IPs: ' . ($securityData['blocked_ips'] ?? 0), 'white');
            CLI::write('Unread Notifications: ' . ($securityData['unread_notifications'] ?? 0), 'white');
            
            if (!empty($securityData['recent_events'])) {
                CLI::write("\nRecent Events:", 'cyan');
                foreach ($securityData['recent_events'] as $event) {
                    CLI::write("- {$event['event_type']} at {$event['created_at']}", 'white');
                }
            }
            
            if (!empty($securityData['recent_notifications'])) {
                CLI::write("\nRecent Notifications:", 'cyan');
                foreach ($securityData['recent_notifications'] as $notification) {
                    CLI::write("- {$notification['title']} ({$notification['priority']})", 'white');
                }
            }
            
            // Test JSON encoding for JavaScript
            $jsonData = json_encode($securityData);
            CLI::write("\nJSON Data Length: " . strlen($jsonData) . ' characters', 'white');
            CLI::write('JSON Valid: ' . (json_last_error() === JSON_ERROR_NONE ? 'Yes' : 'No'), 'white');
            
        } catch (Exception $e) {
            CLI::write('✗ Dashboard test failed: ' . $e->getMessage(), 'red');
            CLI::write('Stack trace:', 'red');
            CLI::write($e->getTraceAsString(), 'red');
        }
        
        CLI::write("\nDashboard test completed!", 'green');
    }
}
