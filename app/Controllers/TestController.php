<?php

namespace App\Controllers;

class TestController extends BaseController
{
    public function testSecurity()
    {
        $securityController = new \App\Controllers\SecurityController();
        
        // Test logging a failed login
        $securityController->logEvent(
            'login_failed',
            'medium',
            'Test failed login from test controller',
            null,
            'test@example.com'
        );
        
        // Get dashboard data
        $dashboardData = $securityController->getDashboardData();
        
        return $this->response->setJSON([
            'status' => 'success',
            'message' => 'Security event logged successfully',
            'data' => $dashboardData
        ]);
    }
}
