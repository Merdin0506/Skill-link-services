<?php

namespace App\Filters;

use App\Models\SecurityEventModel;
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
            $this->logUnauthorized($request, 'API role check denied access.');
            return service('response')
                ->setStatusCode(403)
                ->setJSON(['status' => 'error', 'message' => 'You do not have permission to perform this action.']);
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Nothing to do.
    }

    private function logUnauthorized(RequestInterface $request, string $details): void
    {
        try {
            (new SecurityEventModel())->insert([
                'user_id' => $request->authUserId ?? null,
                'email' => is_array($request->authUser ?? null) ? ($request->authUser['email'] ?? null) : null,
                'event_type' => 'unauthorized_access',
                'severity' => 'medium',
                'ip_address' => $request->getIPAddress(),
                'user_agent' => method_exists($request, 'getUserAgent') ? (string) $request->getUserAgent() : 'unknown',
                'request_uri' => $request->getUri()->getPath(),
                'request_method' => $request->getMethod(),
                'details' => $details,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            // Ignore logging failures.
        }
    }
}
