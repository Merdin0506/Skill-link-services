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

        // Check if user is logged in
        if (!$session->has('user_id')) {
            return redirect()->to('/login')->with('error', 'Please login first');
        }

        // Check if user has a valid role
        $userRole = $session->get('user_role');
        $validRoles = ['admin', 'owner', 'worker', 'customer', 'cashier'];

        if (!in_array($userRole, $validRoles)) {
            return redirect()->to('/login')->with('error', 'Invalid user role');
        }

        // Store user info in request for use in controllers
        $request->user_id = $session->get('user_id');
        $request->user_role = $userRole;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Do nothing
    }
}
