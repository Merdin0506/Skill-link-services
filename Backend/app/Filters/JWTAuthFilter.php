<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use App\Models\UserModel;

/**
 * JWTAuthFilter — Validates Bearer JWT tokens on API routes.
 *
 * On success, attaches `$request->authUser` (user array) and
 * `$request->authUserId` / `$request->authUserRole` for use in controllers.
 *
 * On failure, returns a 401 JSON response immediately.
 *
 * Usage in routes:
 *   'filter' => 'jwtauth'
 *   'filter' => 'jwtauth|role:admin,super_admin'   (combined with RoleFilter)
 *
 * Role enforcement for API routes is handled by RoleApiFilter (reads authUserRole
 * set by this filter).
 */
class JWTAuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $key = getenv('JWT_SECRET');

        $header = $request->getHeaderLine('Authorization');
        $token  = null;

        if ($header && str_starts_with($header, 'Bearer ')) {
            $token = substr($header, 7);
        }

        if (!$token) {
            return service('response')
                ->setStatusCode(401)
                ->setJSON(['status' => 'error', 'message' => 'Authentication token required.']);
        }

        try {
            $decoded = JWT::decode($token, new Key($key, 'HS256'));
        } catch (\Exception $e) {
            return service('response')
                ->setStatusCode(401)
                ->setJSON(['status' => 'error', 'message' => 'Invalid or expired token.']);
        }

        $userModel = new UserModel();
        $user = $userModel->find($decoded->sub ?? null);

        if (!$user || ($user['status'] ?? '') !== 'active') {
            return service('response')
                ->setStatusCode(401)
                ->setJSON(['status' => 'error', 'message' => 'User not found or inactive.']);
        }

        // Attach resolved user data to the request for downstream controllers/filters.
        $request->authUser     = $user;
        $request->authUserId   = $user['id'];
        $request->authUserRole = $user['user_type'];
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Nothing to do after the response.
    }
}
