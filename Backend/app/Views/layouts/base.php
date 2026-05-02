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
    
    <!-- Shared Sidebar CSS -->
    <link rel="stylesheet" href="<?= base_url('css/sidebar.css') ?>">
    <link rel="stylesheet" href="<?= base_url('css/dashboard-layout-unified.css') ?>">
    <link rel="stylesheet" href="<?= base_url('css/dashboard-table-unified.css') ?>">
    
    <!-- Additional Page-Specific CSS (if any) -->
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
    </style>
</head>
<body>
    <?php
    // Ensure we have required variables
    $role = session()->get('user_role') ?? 'customer';
    $user = session()->get('user') ?? [
        'first_name' => explode(' ', session()->get('user_name') ?? 'User')[0] ?? 'User',
        'last_name' => explode(' ', session()->get('user_name') ?? 'User')[1] ?? 'User',
        'email' => session()->get('email') ?? '',
    ];
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

        <!-- Page Content -->
        <div class="page-content">
            <?= $this->renderSection('content') ?>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>&copy; <?= date('Y') ?> Skill Link Services. All rights reserved.</p>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Centralized Sidebar Toggle Script -->
    <script src="<?= base_url('js/sidebar-toggle.js') ?>"></script>

    <!-- Additional Page-Specific Scripts (if any) -->
    <?php if (isset($customJs)): ?>
        <script src="<?= base_url($customJs) ?>"></script>
    <?php endif; ?>
</body>
</html>
