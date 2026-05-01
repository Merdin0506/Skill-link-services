<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class DebugDashboard extends BaseCommand
{
    protected $group = 'App';
    protected $name = 'debug:dashboard';
    protected $description = 'Debug dashboard data flow step by step';

    public function run(array $params)
    {
        CLI::write('=== Dashboard Data Flow Debug ===', 'green');
        
        // Step 1: Check database tables
        CLI::write("\n1. Checking Database Tables:", 'yellow');
        try {
            $db = \Config\Database::connect();
            $tables = $db->listTables();
            
            $requiredTables = ['security_events', 'security_notifications', 'blocked_ips'];
            foreach ($requiredTables as $table) {
                if (in_array($table, $tables)) {
                    $count = $db->table($table)->countAllResults();
                    CLI::write("✓ {$table}: {$count} records", 'green');
                } else {
                    CLI::write("✗ {$table}: Table not found", 'red');
                }
            }
        } catch (Exception $e) {
            CLI::write('✗ Database error: ' . $e->getMessage(), 'red');
        }
        
        // Step 2: Check SecurityController
        CLI::write("\n2. Testing SecurityController:", 'yellow');
        try {
            $securityController = new \App\Controllers\SecurityController();
            $dashboardData = $securityController->getDashboardData();
            
            CLI::write('✓ SecurityController works', 'green');
            CLI::write('Data keys: ' . implode(', ', array_keys($dashboardData)), 'white');
            
            foreach ($dashboardData as $key => $value) {
                if (is_array($value)) {
                    CLI::write("  {$key}: " . count($value) . ' items', 'white');
                } else {
                    CLI::write("  {$key}: {$value}", 'white');
                }
            }
        } catch (Exception $e) {
            CLI::write('✗ SecurityController failed: ' . $e->getMessage(), 'red');
        }
        
        // Step 3: Check Dashboard controller
        CLI::write("\n3. Testing Dashboard Controller:", 'yellow');
        try {
            $dashboard = new \App\Controllers\Dashboard();
            $securityData = $dashboard->getSecurityData();
            
            CLI::write('✓ Dashboard controller works', 'green');
            CLI::write('Security data keys: ' . implode(', ', array_keys($securityData)), 'white');
        } catch (Exception $e) {
            CLI::write('✗ Dashboard controller failed: ' . $e->getMessage(), 'red');
        }
        
        // Step 4: Check view data
        CLI::write("\n4. Testing View Data:", 'yellow');
        try {
            // Simulate what happens in the dashboard controller
            $dashboard = new \App\Controllers\Dashboard();
            
            // Mock session
            $session = \Config\Services::session();
            $session->set('user_id', 1);
            $session->set('user_role', 'admin');
            $session->set('user_name', 'Admin User');
            $session->set('email', 'admin@skilllink.com');
            
            // Get data like the controller does
            $securityData = $dashboard->getSecurityData();
            
            // Test JSON encoding (what happens in the view)
            $jsonData = json_encode($securityData);
            CLI::write('✓ View data encoding works', 'green');
            CLI::write('JSON length: ' . strlen($jsonData) . ' characters', 'white');
            CLI::write('JSON valid: ' . (json_last_error() === JSON_ERROR_NONE ? 'Yes' : 'No'), 'white');
            
            // Check if data is empty
            if (empty($securityData)) {
                CLI::write('⚠ Security data is empty!', 'yellow');
            } else {
                CLI::write('✓ Security data has content', 'green');
                $recentEvents = isset($securityData['recent_events']) ? $securityData['recent_events'] : [];
                $recentNotifications = isset($securityData['recent_notifications']) ? $securityData['recent_notifications'] : [];
                CLI::write('Recent events count: ' . count($recentEvents), 'white');
                CLI::write('Recent notifications count: ' . count($recentNotifications), 'white');
            }
            
        } catch (Exception $e) {
            CLI::write('✗ View data test failed: ' . $e->getMessage(), 'red');
        }
        
        // Step 5: Check recent events specifically
        CLI::write("\n5. Checking Recent Events:", 'yellow');
        try {
            $db = \Config\Database::connect();
            $recentEvents = $db->table('security_events')
                ->orderBy('created_at', 'DESC')
                ->limit(5)
                ->get()
                ->getResultArray();
                
            CLI::write('✓ Recent events query works', 'green');
            CLI::write('Found ' . count($recentEvents) . ' recent events:', 'white');
            
            foreach ($recentEvents as $event) {
                $email = isset($event['email']) ? $event['email'] : 'Unknown';
                CLI::write("  - {$event['event_type']} at {$event['created_at']} ({$email})", 'white');
            }
            
        } catch (Exception $e) {
            CLI::write('✗ Recent events query failed: ' . $e->getMessage(), 'red');
        }
        
        // Step 6: Check recent notifications
        CLI::write("\n6. Checking Recent Notifications:", 'yellow');
        try {
            $db = \Config\Database::connect();
            $recentNotifications = $db->table('security_notifications')
                ->orderBy('created_at', 'DESC')
                ->limit(5)
                ->get()
                ->getResultArray();
                
            CLI::write('✓ Recent notifications query works', 'green');
            CLI::write('Found ' . count($recentNotifications) . ' recent notifications:', 'white');
            
            foreach ($recentNotifications as $notification) {
                CLI::write("  - {$notification['title']} ({$notification['priority']})", 'white');
            }
            
        } catch (Exception $e) {
            CLI::write('✗ Recent notifications query failed: ' . $e->getMessage(), 'red');
        }
        
        CLI::write("\n=== Debug Complete ===", 'green');
        CLI::write('If all checks pass but UI still shows "Loading...",', 'yellow');
        CLI::write('the issue is likely in the JavaScript or view rendering.', 'yellow');
    }
}
