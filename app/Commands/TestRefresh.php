<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class TestRefresh extends BaseCommand
{
    protected $group = 'App';
    protected $name = 'test:refresh';
    protected $description = 'Test the refresh endpoint';

    public function run(array $params)
    {
        CLI::write('Testing Refresh Endpoint...', 'green');
        
        try {
            $dashboard = new \App\Controllers\Dashboard();
            
            // Mock AJAX request
            $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
            $_SERVER['HTTP_CONTENT_TYPE'] = 'application/json';
            
            $result = $dashboard->refreshSecurityData();
            
            CLI::write('✓ Refresh endpoint works', 'green');
            CLI::write('Response:', 'white');
            CLI::write(json_encode($result), 'white');
            
        } catch (Exception $e) {
            CLI::write('✗ Refresh endpoint failed: ' . $e->getMessage(), 'red');
        }
    }
}
