<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class TestWebLogin extends BaseCommand
{
    protected $group = 'App';
    protected $name = 'test:web-login';
    protected $description = 'Test failed login through web interface simulation';

    public function run(array $params)
    {
        CLI::write('=== Testing Web Login Failed Attempts ===', 'green');
        
        // Simulate a failed login attempt like the web interface would do
        CLI::write("\n1. Simulating failed login attempt:", 'yellow');
        
        try {
            $auth = new \App\Controllers\Auth();
            
            // Mock request data for failed login
            $_POST['email'] = 'test-web-fail@example.com';
            $_POST['password'] = 'wrongpassword';
            
            // Mock request object
            $request = \Config\Services::request();
            $request->setGlobal('post', [
                'email' => 'test-web-fail@example.com',
                'password' => 'wrongpassword'
            ]);
            
            // Call the doLogin method
            CLI::write('Calling Auth::doLogin() with wrong credentials...', 'white');
            
            // This should fail and log the attempt
            $result = $auth->doLogin();
            
            CLI::write('Login attempt completed', 'white');
            
        } catch (Exception $e) {
            CLI::write('Login test error: ' . $e->getMessage(), 'red');
        }
        
        // Check if the failed login was logged to security events
        CLI::write("\n2. Checking if failed login was logged to security events:", 'yellow');
        
        try {
            $db = \Config\Database::connect();
            
            $recentFailures = $db->table('security_events')
                ->where('event_type', 'login_failed')
                ->where('email', 'test-web-fail@example.com')
                ->orderBy('created_at', 'DESC')
                ->limit(3)
                ->get()
                ->getResultArray();
            
            CLI::write("Found " . count($recentFailures) . " failed login events for test-web-fail@example.com:", 'white');
            
            foreach ($recentFailures as $event) {
                CLI::write("  - {$event['event_type']} at {$event['created_at']} from {$event['ip_address']}", 'white');
                CLI::write("    Details: {$event['details']}", 'white');
            }
            
        } catch (Exception $e) {
            CLI::write('Database check failed: ' . $e->getMessage(), 'red');
        }
        
        // Check dashboard data
        CLI::write("\n3. Checking dashboard data:", 'yellow');
        
        try {
            $dashboard = new \App\Controllers\Dashboard();
            $securityData = $dashboard->getSecurityData();
            
            CLI::write("Dashboard shows: {$securityData['failed_logins']} failed logins", 'white');
            
            // Check if our test event is in recent events
            $testEventsFound = 0;
            foreach ($securityData['recent_events'] as $event) {
                if (isset($event['email']) && $event['email'] === 'test-web-fail@example.com') {
                    $testEventsFound++;
                }
            }
            CLI::write("Test events in dashboard data: {$testEventsFound}", 'white');
            
        } catch (Exception $e) {
            CLI::write('Dashboard check failed: ' . $e->getMessage(), 'red');
        }
        
        CLI::write("\n=== Test Complete ===", 'green');
        CLI::write('Now test manually:', 'yellow');
        CLI::write('1. Go to login page: http://localhost:8080/auth/login', 'white');
        CLI::write('2. Try to login with wrong email/password', 'white');
        CLI::write('3. Wait 10 seconds for dashboard auto-refresh', 'white');
        CLI::write('4. Check if failed login appears in dashboard', 'white');
    }
}
