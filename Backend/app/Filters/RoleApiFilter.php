<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * RoleApiFilter — API role-based access control.
 *
 * Reads `$request->authUserRole` populated by JWTAuthFilter and compares
 * it against the allowed roles passed as filter arguments.
 *
 * Must always be chained AFTER jwtauth:
 *   'filter' => 'jwtauth|roleapi:admin,super_admin'
 */
class RoleApiFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (empty($arguments)) {
            return; // No role restriction — allow any authenticated user.
        }

        $userRole = $request->authUserRole ?? null;

        if (!in_array($userRole, $arguments, true)) {
            return service('response')
                ->setStatusCode(403)
                ->setJSON(['status' => 'error', 'message' => 'You do not have permission to perform this action.']);
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Nothing to do.
    }
}
