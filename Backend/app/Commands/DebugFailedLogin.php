<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class DebugFailedLogin extends BaseCommand
{
    protected $group = 'App';
    protected $name = 'debug:failed-login';
    protected $description = 'Debug failed login attempts and database logging';

    public function run(array $params)
    {
        CLI::write('=== Failed Login Debug ===', 'green');
        
        // Step 1: Check database connection and tables
        CLI::write("\n1. Checking Database Status:", 'yellow');
        try {
            $db = \Config\Database::connect();
            CLI::write('✓ Database connection established', 'green');
            
            // Check security_events table
            $totalEvents = $db->table('security_events')->countAllResults();
            $failedLogins = $db->table('security_events')->where('event_type', 'login_failed')->countAllResults();
            $recentFailed = $db->table('security_events')
                ->where('event_type', 'login_failed')
                ->orderBy('created_at', 'DESC')
                ->limit(5)
                ->get()
                ->getResultArray();
            
            CLI::write("Total Events: {$totalEvents}", 'white');
            CLI::write("Failed Logins: {$failedLogins}", 'white');
            CLI::write("Recent Failed Logins:", 'cyan');
            
            foreach ($recentFailed as $event) {
                CLI::write("  - {$event['email']} at {$event['created_at']} from {$event['ip_address']}", 'white');
            }
            
        } catch (Exception $e) {
            CLI::write('✗ Database error: ' . $e->getMessage(), 'red');
        }
        
        // Step 2: Test SecurityController logging
        CLI::write("\n2. Testing Security Event Logging:", 'yellow');
        try {
            $securityController = new \App\Controllers\SecurityController();
            
            // Test logging a failed login
            $testEmail = 'test-debug@example.com';
            $testIP = '127.0.0.1';
            
            $result = $securityController->logEvent(
                'login_failed',
                'medium',
                'Debug test failed login attempt',
                null,
                $testEmail,
                $testIP,
                'Debug Test User Agent'
            );
            
            CLI::write('✓ Security event logged successfully', 'green');
            CLI::write("Test event logged for: {$testEmail}", 'white');
            
        } catch (Exception $e) {
            CLI::write('✗ Security logging failed: ' . $e->getMessage(), 'red');
        }
        
        // Step 3: Check if new event appears in database
        CLI::write("\n3. Verifying New Event in Database:", 'yellow');
        try {
            $db = \Config\Database::connect();
            
            $newEvents = $db->table('security_events')
                ->where('email', 'test-debug@example.com')
                ->orderBy('created_at', 'DESC')
                ->limit(3)
                ->get()
                ->getResultArray();
            
            CLI::write("Found " . count($newEvents) . " test events:", 'white');
            foreach ($newEvents as $event) {
                CLI::write("  - {$event['event_type']} at {$event['created_at']}", 'white');
            }
            
        } catch (Exception $e) {
            CLI::write('✗ Database verification failed: ' . $e->getMessage(), 'red');
        }
        
        // Step 4: Test Dashboard data retrieval
        CLI::write("\n4. Testing Dashboard Data Retrieval:", 'yellow');
        try {
            $dashboard = new \App\Controllers\Dashboard();
            $securityData = $dashboard->getSecurityData();
            
            CLI::write('✓ Dashboard data retrieved', 'green');
            CLI::write("Dashboard shows: {$securityData['failed_logins']} failed logins", 'white');
            CLI::write("Recent events count: " . count($securityData['recent_events']), 'white');
            
            // Check if test event is in recent events
            $testEventsFound = 0;
            foreach ($securityData['recent_events'] as $event) {
                if (isset($event['email']) && $event['email'] === 'test-debug@example.com') {
                    $testEventsFound++;
                }
            }
            CLI::write("Test events in dashboard data: {$testEventsFound}", 'white');
            
        } catch (Exception $e) {
            CLI::write('✗ Dashboard retrieval failed: ' . $e->getMessage(), 'red');
        }
        
        // Step 5: Check Auth controller for failed login logging
        CLI::write("\n5. Checking Auth Controller Login Logic:", 'yellow');
        try {
            $authController = new \App\Controllers\Auth();
            
            // Check if the Auth controller has security logging
            $reflection = new ReflectionClass($authController);
            $methods = $reflection->getMethods();
            
            $hasSecurityLogging = false;
            foreach ($methods as $method) {
                if ($method->getName() === 'doLogin') {
                    $hasSecurityLogging = true;
                    CLI::write('✓ Auth controller has doLogin method', 'green');
                    break;
                }
            }
            
            if (!$hasSecurityLogging) {
                CLI::write('⚠ Auth controller may not have doLogin method', 'yellow');
            }
            
        } catch (Exception $e) {
            CLI::write('✗ Auth controller check failed: ' . $e->getMessage(), 'red');
        }
        
        CLI::write("\n=== Debug Complete ===", 'green');
        CLI::write('If failed logins are not appearing, check:', 'yellow');
        CLI::write('1. Auth controller logging failed login attempts', 'white');
        CLI::write('2. Security middleware being called on login attempts', 'white');
        CLI::write('3. Database table structure and permissions', 'white');
    }
}
