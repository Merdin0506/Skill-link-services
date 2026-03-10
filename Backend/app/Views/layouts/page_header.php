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
            overflow-x: hidden;
            overflow-y: hidden;
        }

        .table {
            margin-bottom: 0;
        }

        .table-fit {
            table-layout: auto;
            width: 100%;
        }

        .table-fit th,
        .table-fit td {
            white-space: normal;
            word-break: normal;
            overflow-wrap: break-word;
        }

        .table-payouts th,
        .table-payouts td {
            white-space: normal;
            word-break: normal;
            overflow-wrap: break-word;
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

        .alert-custom {
            border: none;
            border-left: 4px solid var(--primary-color);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 25px;
        }

        .topbar {
            background: white;
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #e8e8e8;
            margin-bottom: 30px;
            border-radius: 10px;
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

        /* Page Content */
        .page-content {
            padding: 30px;
        }

        /* Responsive table and layout behavior is centralized in:
           - public/css/dashboard-layout-unified.css
           - public/css/dashboard-table-unified.css */

        /* Footer */
        .footer {
            text-align: center;
            padding: 20px;
            color: var(--text-muted);
            font-size: 12px;
            margin-top: 30px;
        }
    </style>

    <link rel="stylesheet" href="<?= base_url('css/dashboard-table-unified.css') ?>">
    
    <?php if (isset($customCss)): ?>
        <link rel="stylesheet" href="<?= base_url($customCss) ?>">
    <?php endif; ?>
</head>
<body>
    <?php
    // Use passed variables if available, otherwise fall back to session
    $role = $role ?? session()->get('user_role') ?? 'customer';
    $user = $user ?? session()->get('user') ?? ['first_name' => '', 'last_name' => '', 'email' => ''];
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

