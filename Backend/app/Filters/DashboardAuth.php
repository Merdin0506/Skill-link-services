<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class DashboardAuth implements FilterInterface
{
    /**
     * Check if user is authenticated and has dashboard access
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();
        $sessionTracker = service('sessiontracker');

        // Check if user is logged in
        if (!$session->has('user_id')) {
            return redirect()->to('/login')->with('error', 'Please login first');
        }

        // Check if user has a valid role
        $userRole = $session->get('user_role');
        $validRoles = ['super_admin', 'admin', 'finance', 'worker', 'customer'];

        if (!in_array($userRole, $validRoles)) {
            return redirect()->to('/login')->with('error', 'Invalid user role');
        }

        /* 
        if (!$sessionTracker->getCurrentSessionSummary()) {
            $session->destroy();
            return redirect()->to('/login')->with('error', 'Your session has expired. Please login again.');
        }
        */

        // Store user info in request for use in controllers
        $request->user_id = $session->get('user_id');
        $request->user_role = $userRole;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Track last allowed page for the logged-in user to support safe redirects
        try {
            $session = session();
            if ($session->has('user_id')) {
                $path = $request->getUri()->getPath();
                // Keep only web dashboard paths
                if (strpos($path, '/api') === false) {
                    $session->set('last_allowed_page', $path ?: '/dashboard');
                }
            }
        } catch (\Throwable $e) {
            // ignore session failures
        }
    }
}
