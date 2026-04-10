<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class AccessControl extends BaseConfig
{
    /**
     * Role-based permissions by resource and action.
     * Actions: read, write, update, delete
     *
     * @var array<string, array<string, list<string>>>
     */
    public array $rolePermissions = [
        'super_admin' => [
            '*' => ['read', 'write', 'update', 'delete'],
        ],
        'admin' => [
            '*' => ['read', 'write', 'update', 'delete'],
        ],
        'finance' => [
            'dashboard' => ['read'],
            'profile' => ['read', 'update'],
            'settings' => ['read'],
            'payments' => ['read', 'write', 'update'],
            'payouts' => ['read', 'write', 'update'],
            'reports' => ['read'],
            'bookings' => ['read'],
            'services' => ['read'],
            'reviews' => ['read'],
        ],
        'worker' => [
            'dashboard' => ['read'],
            'profile' => ['read', 'update'],
            'settings' => ['read'],
            'bookings' => ['read', 'update'],
            'services' => ['read'],
            'payments' => ['read'],
            'reviews' => ['read'],
            'jobs' => ['read', 'update'],
            'earnings' => ['read'],
        ],
        'customer' => [
            'dashboard' => ['read'],
            'profile' => ['read', 'update'],
            'settings' => ['read'],
            'bookings' => ['read', 'write', 'update'],
            'services' => ['read'],
            'reviews' => ['read', 'write', 'update'],
            'payments' => ['read', 'write'],
        ],
    ];

    /**
     * URI aliases to normalize routes into policy resources.
     *
     * @var array<string, string>
     */
    public array $resourceAliases = [
        'users' => 'users',
        'user' => 'users',
        'records' => 'records',
        'record' => 'records',
        'bookings' => 'bookings',
        'booking' => 'bookings',
        'payments' => 'payments',
        'payment' => 'payments',
        'payouts' => 'payouts',
        'services' => 'services',
        'service' => 'services',
        'reviews' => 'reviews',
        'review' => 'reviews',
        'dashboard' => 'dashboard',
        'profile' => 'profile',
        'settings' => 'settings',
        'reports' => 'reports',
        'jobs' => 'jobs',
        'earnings' => 'earnings',
        'auth' => 'profile',
    ];
}
