<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class SecurityTest extends BaseCommand
{
    /**
     * The Command's Group
     *
     * @var string
     */
    protected $group = 'App';

    /**
     * The Command's Name
     *
     * @var string
     */
    protected $name = 'security:test';

    /**
     * The Command's Description
     *
     * @var string
     */
    protected $description = 'Test security logging functionality';

    /**
     * The Command's Usage
     *
     * @var string
     */
    protected $usage = 'security:test [arguments] [options]';

    /**
     * The Command's Arguments
     *
     * @var array
     */
    protected $arguments = [];

    /**
     * The Command's Options
     *
     * @var array
     */
    protected $options = [];

    /**
     * Actually execute a command.
     *
     * @param array $params
     */
    public function run(array $params)
    {
        CLI::write('Testing Security System...', 'green');
        
        // Create security controller
        $securityController = new \App\Controllers\SecurityController();
        
        // Test logging a failed login
        CLI::write('Logging test security event...', 'yellow');
        $securityController->logEvent(
            'login_failed',
            'medium',
            'Test failed login from security test command',
            null,
            'test@example.com'
        );

        CLI::write('Logging unauthorized access test event...', 'yellow');
        $securityController->logEvent(
            'unauthorized_access',
            'medium',
            'Test unauthorized access from security test command',
            null,
            'test@example.com'
        );
        
        CLI::write('Event logged successfully!', 'green');
        
        // Get dashboard data
        $dashboardData = $securityController->getDashboardData();
        
        CLI::write('Dashboard Data:', 'cyan');
        CLI::write('Total Events: ' . $dashboardData['total_events'], 'white');
        CLI::write('Failed Logins: ' . $dashboardData['failed_logins'], 'white');
        CLI::write('Blocked IPs: ' . $dashboardData['blocked_ips'], 'white');
        CLI::write('Unread Notifications: ' . $dashboardData['unread_notifications'], 'white');
        
        if (!empty($dashboardData['recent_events'])) {
            CLI::write('Recent Events:', 'cyan');
            foreach ($dashboardData['recent_events'] as $event) {
                CLI::write('- ' . $event['event_type'] . ' at ' . $event['created_at'], 'white');
            }
        }
        
        CLI::write('Security test completed successfully!', 'green');
    }
}
