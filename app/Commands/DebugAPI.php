<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class DebugAPI extends BaseCommand
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
    protected $name = 'debug:api';

    /**
     * The Command's Description
     *
     * @var string
     */
    protected $description = 'Debug API endpoints for security dashboard';

    /**
     * The Command's Usage
     *
     * @var string
     */
    protected $usage = 'debug:api [arguments] [options]';

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
        CLI::write('Testing Security API Endpoints...', 'green');
        
        // Test 1: Security Dashboard API
        CLI::write("\n1. Testing Security Dashboard API:", 'yellow');
        try {
            $securityController = new \App\Controllers\API\SecurityController();
            $result = $securityController->dashboard();
            CLI::write('✓ Security Dashboard API works', 'green');
            CLI::write('Response: ' . json_encode($result), 'white');
        } catch (Exception $e) {
            CLI::write('✗ Security Dashboard API failed: ' . $e->getMessage(), 'red');
        }
        
        // Test 2: Sync API
        CLI::write("\n2. Testing Sync API:", 'yellow');
        try {
            $syncController = new \App\Controllers\API\SyncController();
            $result = $syncController->initialize();
            CLI::write('✓ Sync API works', 'green');
            CLI::write('Response: ' . json_encode($result), 'white');
        } catch (Exception $e) {
            CLI::write('✗ Sync API failed: ' . $e->getMessage(), 'red');
        }
        
        // Test 3: Database Connection
        CLI::write("\n3. Testing Database Connection:", 'yellow');
        try {
            $db = \Config\Database::connect();
            $events = $db->table('security_events')->countAllResults();
            $notifications = $db->table('security_notifications')->countAllResults();
            CLI::write('✓ Database connection works', 'green');
            CLI::write("Security Events: {$events}", 'white');
            CLI::write("Notifications: {$notifications}", 'white');
        } catch (Exception $e) {
            CLI::write('✗ Database connection failed: ' . $e->getMessage(), 'red');
        }
        
        // Test 4: Security Controller
        CLI::write("\n4. Testing Security Controller:", 'yellow');
        try {
            $securityController = new \App\Controllers\SecurityController();
            $data = $securityController->getDashboardData();
            CLI::write('✓ Security Controller works', 'green');
            CLI::write('Data: ' . json_encode($data), 'white');
        } catch (Exception $e) {
            CLI::write('✗ Security Controller failed: ' . $e->getMessage(), 'red');
        }
        
        CLI::write("\nDebug completed!", 'green');
    }
}
