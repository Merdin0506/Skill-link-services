<?php

namespace App\Filters;

use App\Models\UserModel;
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

        $currentUser = (new UserModel())->find((int) $session->get('user_id'));
        // Allow pending workers to access dashboard but they'll see restricted pending-approval view
        // if ($currentUser && ($currentUser['status'] ?? '') === 'pending' && ($currentUser['user_type'] ?? '') === 'worker') {
        //     return redirect()->to('/dashboard')->with('info', 'Your account is under review.');
        // }

        if ($currentUser && ($currentUser['status'] ?? '') === 'rejected') {
            $session->destroy();

            return redirect()->to('/login')->with('warning', 'Your worker application was rejected. Please check your email for the reason and any next steps, or contact support.');
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
