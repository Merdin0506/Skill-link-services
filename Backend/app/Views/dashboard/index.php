<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Skill Link Services</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('css/sidebar.css') ?>">
    <link rel="stylesheet" href="<?= base_url('css/dashboard-layout-unified.css') ?>">
    <link rel="stylesheet" href="<?= base_url('css/dashboard-table-unified.css') ?>">
    <style>
        :root {
            --primary-color: #1e3c72;
            --secondary-color: #2a5298;
            --success-color: #1cc88a;
            --danger-color: #e74c3c;
            --warning-color: #f6c23e;
            --info-color: #17a2b8;
            --light-bg: #f8f9fa;
            --text-dark: #333;
            --text-muted: #999;
        }

        body {
            background: var(--light-bg);
            color: var(--text-dark);
        }

        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            border: none;
            border-top: 4px solid var(--primary-color);
            height: 100%;
            min-height: 185px;
            display: flex;
            flex-direction: column;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.12);
        }

        .stat-card.success {
            border-top-color: var(--success-color);
        }

        .stat-card.danger {
            border-top-color: var(--danger-color);
        }

        .stat-card.warning {
            border-top-color: var(--warning-color);
        }

        .stat-card.info {
            border-top-color: var(--info-color);
        }

        .stat-card .stat-icon {
            font-size: 36px;
            margin-bottom: 15px;
            opacity: 0.85;
        }

        .stat-card .stat-value {
            font-size: clamp(1.4rem, 2.2vw, 2rem);
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 8px;
            line-height: 1.2;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .stat-card .stat-label {
            font-size: 14px;
            color: var(--text-muted);
            font-weight: 500;
            line-height: 1.25;
            min-height: 34px;
        }

        .stat-card .stat-trend {
            font-size: 12px;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid #e8e8e8;
        }

        .card {
            border: none;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            border-radius: 10px;
            margin-bottom: 25px;
            transition: box-shadow 0.3s ease;
        }

        .card:hover {
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.12);
        }

        .card-header {
            background: white;
            border-bottom: 1px solid #e8e8e8;
            border-radius: 10px 10px 0 0;
            padding: 20px;
            font-weight: 600;
            color: var(--text-dark);
        }

        .card-header i {
            margin-right: 10px;
            color: var(--primary-color);
        }

        .card-body {
            padding: 25px;
        }

        .table-responsive {
            border-radius: 10px;
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
            color: var(--text-dark);
            padding: 15px;
        }

        .table tbody td {
            padding: 15px;
            border-color: #e8e8e8;
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

        .badge-active {
            background: #d1e7dd;
            color: #0f5132;
        }

        .badge-inactive {
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
            border-left: 4px solid var(--primary-color);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 25px;
        }

        .alert-custom.success {
            background: #f0f9ff;
            border-left-color: var(--success-color);
            color: #0f5132;
        }

        .alert-custom.info {
            background: #f0f3ff;
            border-left-color: var(--primary-color);
            color: #084298;
        }

        .alert-custom.warning {
            background: #fffbf0;
            border-left-color: var(--warning-color);
            color: #856404;
        }

        .alert-custom i {
            margin-right: 10px;
        }

        .role-badge {
            display: inline-block;
            padding: 8px 15px;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .topbar {
            background: white;
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #e8e8e8;
            margin-bottom: 30px;
            border-radius: 10px 10px 0 0;
            margin-left: -30px;
            margin-right: -30px;
            margin-top: -30px;
        }

        .welcome {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .welcome h6 {
            color: var(--text-dark);
            font-weight: 600;
            margin: 0;
        }

        .welcome .text-muted {
            color: var(--text-muted);
            font-size: 14px;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 15px;
            text-align: right;
        }

        .user-profile p {
            margin: 0;
            font-weight: 500;
            color: var(--text-dark);
        }

        .user-profile small {
            color: var(--text-muted);
        }

        /* Dashboard Page Content */
        .page-content {
            padding: 30px;
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
            color: var(--text-muted);
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

            .user-profile {
                width: 100%;
                justify-content: space-between;
                text-align: left;
                gap: 10px;
            }

            .user-profile div {
                min-width: 0;
            }

            .user-profile p {
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
                max-width: 180px;
            }

            .card-body,
            .card-header {
                padding: 16px;
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
    <?php
    // Ensure we have required variables
    $role = session()->get('role') ?? 'customer';
    $user = session()->get('user') ?? ['first_name' => '', 'last_name' => '', 'email' => ''];
    ?>

    <!-- Include Sidebar -->
    <?= view('layouts/sidebar', ['role' => $role, 'user' => $user]) ?>

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
            'super_admin' => 'dashboard/admin_dashboard',
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
            const isMainDashboard = path === '/dashboard' || path.endsWith('/dashboard');
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
