<?php

namespace App\Filters;

use App\Libraries\AccessControl;
use App\Models\SecurityEventModel;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class PermissionFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $role = session()->get('user_role');
        $uri  = $request->getUri()->getSegments();

        [$resource, $action] = $this->resolveResourceAndAction($request, $uri, $arguments);

        $accessControl = new AccessControl();
        if ($accessControl->isAllowed($role, $resource, $action)) {
            return;
        }

        log_message('warning', 'Permission denied (web): role={role}, resource={resource}, action={action}, path={path}', [
            'role' => (string) ($role ?? 'unknown'),
            'resource' => $resource,
            'action' => $action,
            'path' => $request->getUri()->getPath(),
        ]);

        $this->logUnauthorized($request, 'Permission policy denied access.');

        return redirect()->to('/dashboard')->with('error', 'Access denied by security policy.');
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Nothing to do.
    }

    /**
     * @param list<string>|null $arguments
     * @param list<string> $segments
     * @return array{0: string, 1: string}
     */
    private function resolveResourceAndAction(RequestInterface $request, array $segments, ?array $arguments): array
    {
        $accessControl = new AccessControl();

        if (!empty($arguments)) {
            $resource = $arguments[0] ?? ($segments[0] ?? 'dashboard');
            $action   = $arguments[1] ?? $accessControl->mapMethodToAction($request->getMethod());

            return [$accessControl->normalizeResource($resource), strtolower($action)];
        }

        $resource = $this->detectWebResource($segments);
        $action   = $this->detectWebAction($request, $segments, $accessControl);

        return [$accessControl->normalizeResource($resource), $action];
    }

    /**
     * @param list<string> $segments
     */
    private function detectWebResource(array $segments): string
    {
        if (empty($segments)) {
            return 'dashboard';
        }

        $first = strtolower($segments[0]);

        if (in_array($first, ['admin', 'finance', 'worker', 'customer'], true)) {
            return strtolower($segments[1] ?? $first);
        }

        return $first;
    }

    /**
     * @param list<string> $segments
     */
    private function detectWebAction(RequestInterface $request, array $segments, AccessControl $accessControl): string
    {
        $method = strtoupper($request->getMethod());
        if ($method !== 'POST') {
            return $accessControl->mapMethodToAction($method);
        }

        $path = strtolower(implode('/', $segments));

        if (str_contains($path, 'delete-account')) {
            return 'update';
        }

        if (str_contains($path, 'delete')) {
            return 'delete';
        }

        if (str_contains($path, 'update')
            || str_contains($path, 'edit')
            || str_contains($path, 'change-password')
            || str_contains($path, 'accept')
            || str_contains($path, 'cancel')
            || str_contains($path, 'assign')
            || str_contains($path, 'restore')
            || str_contains($path, 'start')
            || str_contains($path, 'complete')
            || str_contains($path, 'process')) {
            return 'update';
        }

        return 'write';
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
