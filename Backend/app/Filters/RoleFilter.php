<?php

namespace App\Filters;

use App\Models\SecurityEventModel;
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
            $this->logUnauthorized($request, 'Role check denied access.');
            // Redirect user back to their last allowed page where they had permission, if available.
            $session = session();
            $last = $session->get('last_allowed_page') ?? '/dashboard';
            // Ensure we only redirect within the app (simple sanity check)
            if (is_string($last) && strpos($last, '/') === 0) {
                return redirect()->to($last)->with('error', 'You do not have permission to access that page.');
            }

            return redirect()->to('/dashboard')->with('error', 'You do not have permission to access that page.');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Nothing to do after the response.
    }

    private function logUnauthorized(RequestInterface $request, string $details): void
    {
        try {
            $session = session();
            (new SecurityEventModel())->insert([
                'user_id' => $session->get('user_id') ?: null,
                'email' => $session->get('email') ?: null,
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
