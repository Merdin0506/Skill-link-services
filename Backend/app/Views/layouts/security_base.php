<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? $pageTitle . ' - Skill Link Services' : 'Skill Link Services' ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <link rel="stylesheet" href="<?= base_url('css/sidebar.css') ?>">
    <link rel="stylesheet" href="<?= base_url('css/dashboard-layout-unified.css') ?>">
    <link rel="stylesheet" href="<?= base_url('css/dashboard-table-unified.css') ?>">

    <?php if (isset($customCss)): ?>
        <link rel="stylesheet" href="<?= base_url($customCss) ?>">
    <?php endif; ?>

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

        .security-top-nav {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: #fff;
            border-radius: 12px;
        }

        .security-top-nav .nav-link {
            color: rgba(255, 255, 255, 0.85);
            border-radius: 10px;
            padding: 10px 14px;
            margin: 0 4px;
            font-weight: 600;
        }

        .security-top-nav .nav-link:hover {
            color: #fff;
            background: rgba(255, 255, 255, 0.12);
        }

        .security-top-nav .nav-link.active {
            color: #fff;
            background: rgba(255, 255, 255, 0.18);
        }

        .security-top-nav .badge {
            font-weight: 700;
        }
    </style>
</head>
<body>
    <?php
    $role = session()->get('user_role') ?? 'customer';
    $user = session()->get('user') ?? [
        'first_name' => explode(' ', session()->get('user_name') ?? 'User')[0] ?? 'User',
        'last_name' => explode(' ', session()->get('user_name') ?? 'User')[1] ?? 'User',
        'email' => session()->get('email') ?? '',
    ];

    $securityNavActive = $securityNavActive ?? '';
    ?>

    <!-- Include shared Sidebar -->
    <?= view('layouts/sidebar', ['role' => $role, 'user' => $user]) ?>

    <div class="main-content" id="mainContent">
            <div class="topbar">
            <div class="welcome">
                <button class="btn btn-sm btn-light" id="toggleSidebar">
                    <i class="fas fa-bars"></i>
                </button>
                <a class="btn btn-sm btn-light ms-2" href="<?= base_url('dashboard') ?>">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <div>
                    <h6 class="mb-0">Security</h6>
                    <p class="mb-0 text-muted">Skill Link Services</p>
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

        <div class="page-content">
            <div class="security-top-nav p-2 mb-4">
                <div class="d-flex flex-wrap align-items-center justify-content-between">
                    <div class="d-flex align-items-center px-2 py-1">
                        <i class="fas fa-shield-alt me-2"></i>
                        <strong>Security Module</strong>
                    </div>

                    <ul class="nav nav-pills px-2 py-1">
                        <li class="nav-item">
                            <a class="nav-link <?= $securityNavActive === 'dashboard' ? 'active' : '' ?>" href="<?= base_url('security/dashboard') ?>">
                                <i class="fas fa-tachometer-alt me-1"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $securityNavActive === 'audit' ? 'active' : '' ?>" href="<?= base_url('security/audit-logs') ?>">
                                <i class="fas fa-list me-1"></i> Audit Logs
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $securityNavActive === 'notifications' ? 'active' : '' ?>" href="<?= base_url('security/notifications') ?>">
                                <i class="fas fa-bell me-1"></i> Notifications
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $securityNavActive === 'reports' ? 'active' : '' ?>" href="<?= base_url('security/reports') ?>">
                                <i class="fas fa-chart-bar me-1"></i> Reports
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $securityNavActive === 'blocked' ? 'active' : '' ?>" href="<?= base_url('security/blocked-ips') ?>">
                                <i class="fas fa-ban me-1"></i> Blocked IPs
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $securityNavActive === 'settings' ? 'active' : '' ?>" href="<?= base_url('security/settings') ?>">
                                <i class="fas fa-cog me-1"></i> Settings
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <?= $this->renderSection('content') ?>
        </div>

        <div class="footer">
            <p>&copy; <?= date('Y') ?> Skill Link Services. All rights reserved.</p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Centralized Sidebar Toggle Script -->
    <script src="<?= base_url('js/sidebar-toggle.js') ?>"></script>

    <?php if (isset($customJs)): ?>
        <script src="<?= base_url($customJs) ?>"></script>
    <?php endif; ?>
</body>
</html>
