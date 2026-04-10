<?php

namespace App\Filters;

use App\Libraries\AccessControl;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class PermissionApiFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $role = $request->authUserRole ?? null;

        [$resource, $action] = $this->resolveResourceAndAction($request, $arguments);

        $accessControl = new AccessControl();
        if ($accessControl->isAllowed($role, $resource, $action)) {
            return;
        }

        log_message('warning', 'Permission denied (api): role={role}, resource={resource}, action={action}, path={path}', [
            'role' => (string) ($role ?? 'unknown'),
            'resource' => $resource,
            'action' => $action,
            'path' => $request->getUri()->getPath(),
        ]);

        return service('response')
            ->setStatusCode(403)
            ->setJSON([
                'status' => 'error',
                'message' => 'Access denied by security policy.',
                'resource' => $resource,
                'action' => $action,
            ]);
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Nothing to do.
    }

    /**
     * @param list<string>|null $arguments
     * @return array{0: string, 1: string}
     */
    private function resolveResourceAndAction(RequestInterface $request, ?array $arguments): array
    {
        $accessControl = new AccessControl();

        if (!empty($arguments)) {
            $resource = $arguments[0] ?? $this->detectApiResource($request);
            $action   = $arguments[1] ?? $this->detectApiAction($request, $accessControl);

            return [$accessControl->normalizeResource($resource), strtolower($action)];
        }

        $resource = $this->detectApiResource($request);
        $action   = $this->detectApiAction($request, $accessControl);

        return [$accessControl->normalizeResource($resource), strtolower($action)];
    }

    private function detectApiResource(RequestInterface $request): string
    {
        $segments = $request->getUri()->getSegments();
        // Expected format: /api/{resource}/...
        return strtolower($segments[1] ?? 'dashboard');
    }

    private function detectApiAction(RequestInterface $request, AccessControl $accessControl): string
    {
        $method = strtoupper($request->getMethod());
        if ($method !== 'POST') {
            return $accessControl->mapMethodToAction($method);
        }

        $path = strtolower($request->getUri()->getPath());

        if (str_contains($path, 'delete')) {
            return 'delete';
        }

        if (str_contains($path, 'update')
            || str_contains($path, 'change-password')
            || str_contains($path, 'logout')
            || str_contains($path, 'cancel')
            || str_contains($path, 'assign')
            || str_contains($path, 'start')
            || str_contains($path, 'complete')
            || str_contains($path, 'process')
            || str_contains($path, 'status')
            || str_contains($path, 'profile-image')) {
            return 'update';
        }

        return 'write';
    }
}
