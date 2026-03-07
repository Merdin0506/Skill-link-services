<?php

/**
 * Dashboard and Role-Based Access Control Helper Functions
 */

if (!function_exists('userHasRole')) {
    /**
     * Check if current user has a specific role
     *
     * @param string|array $role Role or array of roles to check
     * @return bool
     */
    function userHasRole($role): bool
    {
        $session = session();
        
        if (!$session->has('user_role')) {
            return false;
        }

        $userRole = $session->get('user_role');

        if (is_array($role)) {
            return in_array($userRole, $role);
        }

        return $userRole === $role;
    }
}

if (!function_exists('isAdmin')) {
    /**
     * Check if current user is admin
     *
     * @return bool
     */
    function isAdmin(): bool
    {
        return userHasRole('admin');
    }
}

if (!function_exists('isWorker')) {
    /**
     * Check if current user is worker
     *
     * @return bool
     */
    function isWorker(): bool
    {
        return userHasRole('worker');
    }
}

if (!function_exists('isCustomer')) {
    /**
     * Check if current user is customer
     *
     * @return bool
     */
    function isCustomer(): bool
    {
        return userHasRole('customer');
    }
}

if (!function_exists('isOwner')) {
    /**
     * Check if current user is owner
     *
     * @return bool
     */
    function isOwner(): bool
    {
        return userHasRole('owner');
    }
}

if (!function_exists('getCurrentUserId')) {
    /**
     * Get current user ID
     *
     * @return int|null
     */
    function getCurrentUserId(): ?int
    {
        $session = session();
        return $session->get('user_id');
    }
}

if (!function_exists('getCurrentUserRole')) {
    /**
     * Get current user role
     *
     * @return string|null
     */
    function getCurrentUserRole(): ?string
    {
        $session = session();
        return $session->get('user_role');
    }
}

if (!function_exists('isAuthenticated')) {
    /**
     * Check if user is authenticated
     *
     * @return bool
     */
    function isAuthenticated(): bool
    {
        $session = session();
        return $session->has('user_id') && $session->has('user_role');
    }
}

if (!function_exists('hasResourceAccess')) {
    /**
     * Check if user has access to a resource based on role
     *
     * @param string $resource Resource name
     * @param string $action Action name (view, edit, delete, etc.)
     * @return bool
     */
    function hasResourceAccess($resource, $action = 'view'): bool
    {
        $userRole = getCurrentUserRole();

        $permissions = [
            'dashboard' => ['admin', 'owner', 'worker', 'customer', 'finance'],
            'users' => ['admin'],
            'bookings' => ['admin', 'owner', 'worker', 'customer'],
            'payments' => ['admin', 'finance', 'customer', 'owner'],
            'reports' => ['admin', 'finance'],
            'profile' => ['admin', 'owner', 'worker', 'customer', 'finance'],
            'settings' => ['admin'],
        ];

        if (!isset($permissions[$resource])) {
            return false;
        }

        return in_array($userRole, $permissions[$resource]);
    }
}

if (!function_exists('getDashboardRoute')) {
    /**
     * Get dashboard route based on user role
     *
     * @return string
     */
    function getDashboardRoute(): string
    {
        $userRole = getCurrentUserRole();

        return match ($userRole) {
            'admin' => '/admin/dashboard',
            'worker' => '/worker/dashboard',
            'customer' => '/customer/dashboard',
            'owner' => '/owner/dashboard',
            'finance' => '/dashboard',
            default => '/dashboard'
        };
    }
}

if (!function_exists('getSidebarMenu')) {
    /**
     * get Sidebar menu items based on user role
     *
     * @return array
     */
    function getSidebarMenu(): array
    {
        $userRole = getCurrentUserRole();

        $baseMenu = [
            ['label' => 'Dashboard', 'url' => '/dashboard', 'icon' => 'fa-chart-line'],
            ['label' => 'Profile', 'url' => '/profile', 'icon' => 'fa-user-circle'],
            ['label' => 'Settings', 'url' => '/settings', 'icon' => 'fa-cog'],
        ];

        $roleMenu = match ($userRole) {
            'admin' => [
                ['label' => 'Dashboard', 'url' => '/dashboard', 'icon' => 'fa-chart-line'],
                ['label' => 'Users', 'url' => '/admin/users', 'icon' => 'fa-users'],
                ['label' => 'Bookings', 'url' => '/admin/bookings', 'icon' => 'fa-calendar-check'],
                ['label' => 'Payments', 'url' => '/admin/payments', 'icon' => 'fa-credit-card'],
                ['label' => 'Reports', 'url' => '/admin/reports', 'icon' => 'fa-file-alt'],
                ['label' => 'Settings', 'url' => '/settings', 'icon' => 'fa-cog'],
            ],
            'worker' => [
                ['label' => 'Dashboard', 'url' => '/dashboard', 'icon' => 'fa-chart-line'],
                ['label' => 'Available Jobs', 'url' => '/worker/available-jobs', 'icon' => 'fa-briefcase'],
                ['label' => 'My Jobs', 'url' => '/worker/my-jobs', 'icon' => 'fa-tasks'],
                ['label' => 'Earnings', 'url' => '/worker/earnings', 'icon' => 'fa-wallet'],
                ['label' => 'Profile', 'url' => '/profile', 'icon' => 'fa-user-circle'],
            ],
            'customer' => [
                ['label' => 'Dashboard', 'url' => '/dashboard', 'icon' => 'fa-chart-line'],
                ['label' => 'New Booking', 'url' => '/customer/new-booking', 'icon' => 'fa-plus'],
                ['label' => 'My Bookings', 'url' => '/customer/bookings', 'icon' => 'fa-calendar-check'],
                ['label' => 'Services', 'url' => '/customer/services', 'icon' => 'fa-list'],
                ['label' => 'Payments', 'url' => '/customer/payments', 'icon' => 'fa-credit-card'],
                ['label' => 'Profile', 'url' => '/profile', 'icon' => 'fa-user-circle'],
            ],
            'owner' => [
                ['label' => 'Dashboard', 'url' => '/dashboard', 'icon' => 'fa-chart-line'],
                ['label' => 'My Bookings', 'url' => '/owner/bookings', 'icon' => 'fa-calendar-check'],
                ['label' => 'My Services', 'url' => '/owner/services', 'icon' => 'fa-list'],
                ['label' => 'Payments', 'url' => '/owner/payments', 'icon' => 'fa-credit-card'],
                ['label' => 'Profile', 'url' => '/profile', 'icon' => 'fa-user-circle'],
            ],
            'finance' => [
                ['label' => 'Dashboard', 'url' => '/dashboard', 'icon' => 'fa-chart-line'],
                ['label' => 'Payments', 'url' => '/finance/payments', 'icon' => 'fa-credit-card'],
                ['label' => 'Payouts', 'url' => '/finance/payouts', 'icon' => 'fa-wallet'],
                ['label' => 'Reports', 'url' => '/finance/reports', 'icon' => 'fa-file-alt'],
                ['label' => 'Profile', 'url' => '/profile', 'icon' => 'fa-user-circle'],
            ],
            default => $baseMenu
        };

        return $roleMenu;
    }
}

if (!function_exists('formatCurrency')) {
    /**
     * Format currency value
     *
     * @param float $value
     * @return string
     */
    function formatCurrency($value): string
    {
        return '₱' . number_format($value, 2);
    }
}

if (!function_exists('formatNumber')) {
    /**
     * Format number value
     *
     * @param int $value
     * @return string
     */
    function formatNumber($value): string
    {
        return number_format($value);
    }
}

if (!function_exists('canPerformBookingAction')) {
    /**
     * Check if user can perform an action on a booking
     *
     * @param string $action Action to check (view, edit, delete, etc.)
     * @param int $userId User ID who made the booking/owns the booking
     * @return bool
     */
    function canPerformBookingAction($action, $userId): bool
    {
        $currentUserId = getCurrentUserId();
        $userRole = getCurrentUserRole();

        // Admin can do anything
        if ($userRole === 'admin') {
            return true;
        }

        // User can manage their own bookings
        if ($userId === $currentUserId) {
            return true;
        }

        // Workers can view bookings assigned to them
        if ($userRole === 'worker') {
            return $action === 'view';
        }

        return false;
    }
}
