<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Skill Link Services</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('css/dashboard-layout-unified.css') ?>">
    <link rel="stylesheet" href="<?= base_url('css/dashboard-table-unified.css') ?>">
    <style>
        :root {
            --primary-color: #4e73df;
            --secondary-color: #858796;
            --success-color: #1cc88a;
            --danger-color: #e74c3c;
            --warning-color: #f6c23e;
            --info-color: #17a2b8;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .sidebar {
            background: linear-gradient(135deg, var(--primary-color) 0%, #224abe 100%);
            min-height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            width: 250px;
            color: white;
            overflow-y: auto;
            transition: all 0.3s ease;
            z-index: 1000;
        }

        .sidebar.collapsed {
            width: 60px;
        }

        .sidebar .brand {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar .brand img {
            max-width: 40px;
            margin-bottom: 10px;
        }

        .sidebar .brand h5 {
            margin: 0;
            font-size: 14px;
        }

        .sidebar.collapsed .brand h5 {
            display: none;
        }

        .sidebar-nav {
            padding: 20px 0;
        }

        .sidebar-nav a {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: rgba(255, 255, 255, 0.85);
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
        }

        .sidebar-nav a:hover,
        .sidebar-nav a.active {
            background: rgba(255, 255, 255, 0.1);
            border-left-color: var(--warning-color);
            color: white;
        }

        .sidebar-nav a i {
            width: 25px;
            text-align: center;
            margin-right: 15px;
        }

        .sidebar.collapsed .sidebar-nav a span {
            display: none;
        }

        .sidebar.collapsed .sidebar-nav a i {
            margin-right: 0;
        }

        .main-content {
            margin-left: 250px;
            transition: all 0.3s ease;
            padding: 30px;
        }

        .main-content.expanded {
            margin-left: 60px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                width: 250px;
                min-width: 250px;
                max-width: 250px;
                position: fixed;
                left: 0;
                top: 0;
                min-height: 100vh;
                max-height: none;
            }

            .sidebar.collapsed {
                width: 60px;
                min-width: 60px;
                max-width: 60px;
                max-height: none;
            }

            .main-content {
                margin-left: 250px;
            }

            .main-content.expanded {
                margin-left: 60px;
            }
        }

        .topbar {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .topbar .welcome {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .topbar .user-profile {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .topbar .user-profile img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }

        .stat-card {
            background: white;
            border-radius: 8px;
            padding: 25px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border-left: 4px solid var(--primary-color);
            height: 100%;
            min-height: 185px;
            display: flex;
            flex-direction: column;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .stat-card.success {
            border-left-color: var(--success-color);
        }

        .stat-card.danger {
            border-left-color: var(--danger-color);
        }

        .stat-card.warning {
            border-left-color: var(--warning-color);
        }

        .stat-card.info {
            border-left-color: var(--info-color);
        }

        .stat-card .stat-icon {
            font-size: 32px;
            margin-bottom: 10px;
            opacity: 0.8;
        }

        .stat-card .stat-value {
            font-size: clamp(1.4rem, 2.2vw, 2rem);
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
            line-height: 1.2;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .stat-card .stat-label {
            font-size: 14px;
            color: #999;
            line-height: 1.25;
            min-height: 34px;
        }

        .card {
            border: none;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            margin-bottom: 25px;
        }

        .card-header {
            background: white;
            border-bottom: 1px solid #e3e6f0;
            border-radius: 8px 8px 0 0;
            padding: 20px;
            font-weight: 600;
            color: #333;
        }

        .card-body {
            padding: 25px;
        }

        .table-responsive {
            border-radius: 8px;
            overflow-x: auto;
            overflow-y: hidden;
            -webkit-overflow-scrolling: touch;
        }

        .table {
            margin-bottom: 0;
        }

        .table thead th {
            background: #f8f9fa;
            border: none;
            font-weight: 600;
            color: #333;
            padding: 15px;
        }

        .table tbody td {
            padding: 15px;
            border-color: #e3e6f0;
            vertical-align: middle;
        }

        .badge {
            padding: 8px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }

        .badge-pending {
            background: #fff3cd;
            color: #856404;
        }

        .badge-assigned {
            background: #d1ecf1;
            color: #0c5460;
        }

        .badge-in_progress {
            background: #cfe2ff;
            color: #084298;
        }

        .badge-completed {
            background: #d1e7dd;
            color: #0f5132;
        }

        .badge-cancelled {
            background: #f8d7da;
            color: #842029;
        }

        .chart-container {
            position: relative;
            height: 300px;
            margin-bottom: 20px;
        }

        .alert-custom {
            border: none;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .role-badge {
            display: inline-block;
            padding: 6px 12px;
            background: var(--primary-color);
            color: white;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        /* Loading Animation */
        .spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(0, 0, 0, 0.1);
            border-radius: 50%;
            border-top-color: var(--primary-color);
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Animations */
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .stat-card {
            animation: slideIn 0.5s ease-out;
        }

        .stat-card:nth-child(2) { animation-delay: 0.1s; }
        .stat-card:nth-child(3) { animation-delay: 0.2s; }
        .stat-card:nth-child(4) { animation-delay: 0.3s; }
        .stat-card:nth-child(5) { animation-delay: 0.4s; }
        .stat-card:nth-child(6) { animation-delay: 0.5s; }

        /* Footer */
        .footer {
            text-align: center;
            padding: 20px;
            color: #999;
            font-size: 12px;
            margin-top: 30px;
        }
        
        @media (max-width: 992px) {
            .page-content {
                padding: 20px;
            }

            .topbar {
                margin-left: -20px;
                margin-right: -20px;
                margin-top: -20px;
                padding: 15px 20px;
            }
        }

        @media (max-width: 768px) {
            .main-content {
                padding: 15px;
            }

            .page-content {
                padding: 15px;
            }

            .topbar {
                margin-left: -15px;
                margin-right: -15px;
                margin-top: -15px;
                border-radius: 0;
                gap: 10px;
            }

            .topbar .user-profile {
                width: 100%;
                justify-content: space-between;
                text-align: left;
                gap: 10px;
            }

            .topbar .user-profile p {
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
                max-width: 180px;
            }

            .stat-card {
                min-height: 165px;
                padding: 18px;
            }

            .stat-card .stat-icon {
                font-size: 30px;
                margin-bottom: 10px;
            }

            .stat-card .stat-value {
                font-size: clamp(1.25rem, 5vw, 1.7rem);
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar Navigation -->
    <div class="sidebar" id="sidebar">
        <div class="brand">
            <i class="fas fa-link" style="font-size: 28px;"></i>
            <h5>SkillLink</h5>
        </div>
        <nav class="sidebar-nav">
            <a href="<?= base_url('dashboard') ?>" class="active">
                <i class="fas fa-chart-line"></i>
                <span>Dashboard</span>
            </a>
            <?php if (in_array($role, ['admin', 'super_admin'], true)): ?>
                <a href="<?= base_url('admin/users') ?>">
                    <i class="fas fa-users"></i>
                    <span>Users</span>
                </a>
                <a href="<?= base_url('admin/bookings') ?>">
                    <i class="fas fa-calendar-check"></i>
                    <span>Bookings</span>
                </a>
                <a href="<?= base_url('admin/payments') ?>">
                    <i class="fas fa-credit-card"></i>
                    <span>Payments</span>
                </a>
                <a href="<?= base_url('admin/records') ?>">
                    <i class="fas fa-file-invoice"></i>
                    <span>Service Records</span>
                </a>
            <?php elseif ($role === 'worker'): ?>
                <a href="<?= base_url('worker/available-jobs') ?>">
                    <i class="fas fa-briefcase"></i>
                    <span>Available Jobs</span>
                </a>
                <a href="<?= base_url('worker/my-jobs') ?>">
                    <i class="fas fa-tasks"></i>
                    <span>My Jobs</span>
                </a>
                <a href="<?= base_url('worker/earnings') ?>">
                    <i class="fas fa-wallet"></i>
                    <span>Earnings</span>
                </a>
            <?php elseif ($role === 'customer'): ?>
                <a href="<?= base_url('customer/bookings') ?>">
                    <i class="fas fa-calendar-check"></i>
                    <span>My Bookings</span>
                </a>
                <a href="<?= base_url('customer/services') ?>">
                    <i class="fas fa-list"></i>
                    <span>Services</span>
                </a>
                <a href="<?= base_url('customer/payments') ?>">
                    <i class="fas fa-credit-card"></i>
                    <span>Payments</span>
                </a>
            <?php elseif ($role === 'finance'): ?>
                <a href="<?= base_url('finance/payments') ?>">
                    <i class="fas fa-money-bill-wave"></i>
                    <span>Payments</span>
                </a>
                <a href="<?= base_url('finance/payouts') ?>">
                    <i class="fas fa-hand-holding-usd"></i>
                    <span>Worker Payouts</span>
                </a>
                <a href="<?= base_url('finance/reports') ?>">
                    <i class="fas fa-chart-bar"></i>
                    <span>Financial Reports</span>
                </a>
            <?php endif; ?>
            <a href="<?= base_url('profile') ?>">
                <i class="fas fa-user-circle"></i>
                <span>Profile</span>
            </a>
            <a href="<?= base_url('settings') ?>">
                <i class="fas fa-cog"></i>
                <span>Settings</span>
            </a>
            <a href="<?= base_url('logout') ?>" style="margin-top: 20px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 20px;">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content" id="mainContent">
        <!-- Topbar -->
        <div class="topbar">
            <div class="welcome">
                <button class="btn btn-sm btn-light" id="toggleSidebar">
                    <i class="fas fa-bars"></i>
                </button>
                <div>
                    <h6 class="mb-0">Welcome back!</h6>
                    <p class="mb-0 text-muted"><?= $user['first_name'] . ' ' . $user['last_name'] ?></p>
                </div>
            </div>
            <div class="user-profile">
                <span class="role-badge"><?= ucfirst($role) ?></span>
                <div>
                    <p class="mb-0"><?= $user['email'] ?></p>
                    <small class="text-muted">Last login: Today</small>
                </div>
            </div>
        </div>

        <!-- Load Role-Specific Dashboard -->
        <?php 
        $dashboardView = match($role) {
            'admin' => 'dashboard/admin_dashboard',
            'worker' => 'dashboard/worker_dashboard',
            'customer' => 'dashboard/customer_dashboard',
            'finance' => 'dashboard/finance_dashboard',
            default => 'dashboard/default_dashboard'
        };
        
        echo view($dashboardView, get_defined_vars());
        ?>

        <!-- Footer -->
        <div class="footer">
            <p>&copy; <?= date('Y') ?> Skill Link Services. All rights reserved.</p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    
    <!-- Centralized Sidebar Toggle Script -->
    <script src="<?= base_url('js/sidebar-toggle.js') ?>"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Keep dashboard data fresh by auto-refreshing the main dashboard view.
            const path = window.location.pathname.replace(/\/+$/, '');
            const isMainDashboard = path === '/dashboard' || path.endsWith('/dashboard') || path.endsWith('/index.php/dashboard');
            if (isMainDashboard) {
                setInterval(function() {
                    if (!document.hidden) {
                        window.location.reload();
                    }
                }, 30000);
            }
        });

        // Format currency
        function formatCurrency(value) {
            return new Intl.NumberFormat('en-PH', {
                style: 'currency',
                currency: 'PHP'
            }).format(value);
        }

        // Format numbers
        function formatNumber(value) {
            return new Intl.NumberFormat('en-US').format(value);
        }
    </script>
</body>
</html>
