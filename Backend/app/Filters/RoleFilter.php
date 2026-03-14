<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * RoleFilter — Web session role-based access control.
 *
 * Usage in routes (applied after DashboardAuth):
 *   'filter' => 'dashboardauth|role:admin,super_admin'
 *
 * $arguments receives the comma-separated roles listed after the colon.
 */
class RoleFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        // $arguments is an array of allowed roles passed from the route definition.
        if (empty($arguments)) {
            return; // No role restriction configured — allow through.
        }

        $userRole = session()->get('user_role');

        if (!in_array($userRole, $arguments, true)) {
            return redirect()->to('/dashboard')->with('error', 'You do not have permission to access that page.');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Nothing to do after the response.
    }
}
