<?php

namespace App\Libraries;

use Config\AccessControl as AccessControlConfig;

class AccessControl
{
    private AccessControlConfig $config;

    public function __construct(?AccessControlConfig $config = null)
    {
        $this->config = $config ?? config('AccessControl');
    }

    public function isAllowed(?string $role, ?string $resource, ?string $action): bool
    {
        if (!$role || !$resource || !$action) {
            return false;
        }

        $resource = $this->normalizeResource($resource);
        $action   = strtolower(trim($action));

        $rolePermissions = $this->config->rolePermissions[$role] ?? null;
        if ($rolePermissions === null) {
            return false;
        }

        $globalActions = $rolePermissions['*'] ?? [];
        if (in_array($action, $globalActions, true)) {
            return true;
        }

        $resourceActions = $rolePermissions[$resource] ?? [];

        return in_array($action, $resourceActions, true);
    }

    public function normalizeResource(string $resource): string
    {
        $normalized = strtolower(trim($resource));

        return $this->config->resourceAliases[$normalized] ?? $normalized;
    }

    public function mapMethodToAction(string $method): string
    {
        return match (strtoupper($method)) {
            'GET', 'HEAD', 'OPTIONS' => 'read',
            'POST' => 'write',
            'PUT', 'PATCH' => 'update',
            'DELETE' => 'delete',
            default => 'read',
        };
    }
}
