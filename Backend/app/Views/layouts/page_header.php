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

        .skilllink-flash-stack {
            display: grid;
            gap: 14px;
            margin-bottom: 24px;
        }

        .skilllink-alert {
            display: grid;
            grid-template-columns: auto 1fr auto;
            align-items: start;
            gap: 14px;
            padding: 16px 18px;
            border: 1px solid rgba(30, 60, 114, 0.08);
            border-left: 5px solid var(--primary-color);
            border-radius: 16px;
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(248, 250, 252, 0.96));
            box-shadow: 0 12px 28px rgba(15, 23, 42, 0.08);
        }

        .skilllink-alert-success {
            border-left-color: var(--success-color);
            background: linear-gradient(180deg, #f6fffb 0%, #edfdf6 100%);
        }

        .skilllink-alert-error {
            border-left-color: var(--danger-color);
            background: linear-gradient(180deg, #fff8f7 0%, #fff1ef 100%);
        }

        .skilllink-alert-icon {
            width: 42px;
            height: 42px;
            display: grid;
            place-items: center;
            border-radius: 12px;
            font-size: 1rem;
            color: #fff;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            box-shadow: 0 10px 18px rgba(30, 60, 114, 0.18);
        }

        .skilllink-alert-success .skilllink-alert-icon {
            background: linear-gradient(135deg, #0f9f6e 0%, var(--success-color) 100%);
        }

        .skilllink-alert-error .skilllink-alert-icon {
            background: linear-gradient(135deg, #c0392b 0%, var(--danger-color) 100%);
        }

        .skilllink-alert-body strong {
            display: block;
            margin-bottom: 4px;
            font-size: 0.98rem;
        }

        .skilllink-alert-body p,
        .skilllink-alert-body li {
            color: #4b5563;
        }

        .skilllink-alert-body ul {
            margin: 8px 0 0;
            padding-left: 18px;
        }

        .compact-form-notice {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            margin-bottom: 16px;
            border-radius: 10px;
            font-size: 0.92rem;
            line-height: 1.4;
        }

        .compact-form-notice.error {
            color: #b9382b;
            background: #fff3f1;
            border: 1px solid #f3c4bd;
        }

        .compact-form-notice.success {
            color: #157347;
            background: #eefaf3;
            border: 1px solid #bfe7cf;
        }

        .compact-form-notice i {
            flex-shrink: 0;
        }

        .field-error {
            margin-top: 6px;
            color: #c0392b;
            font-size: 0.88rem;
        }

        .skilllink-confirm .modal-dialog {
            max-width: 520px;
        }

        .skilllink-confirm .modal-content {
            position: relative;
            border: 0;
            border-radius: 26px;
            overflow: hidden;
            background:
                radial-gradient(circle at top right, rgba(96, 165, 250, 0.22), transparent 34%),
                linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(245, 248, 252, 0.98));
            box-shadow: 0 28px 70px rgba(15, 23, 42, 0.22);
        }

        .skilllink-confirm .modal-content::before {
            content: "";
            position: absolute;
            inset: 0;
            border: 1px solid rgba(255, 255, 255, 0.55);
            border-radius: 26px;
            pointer-events: none;
        }

        .skilllink-confirm .modal-header {
            position: relative;
            padding: 24px 26px 10px;
            border-bottom: 0;
            background: transparent;
            color: var(--text-dark);
            align-items: flex-start;
        }

        .skilllink-confirm .modal-header::after {
            content: "";
            position: absolute;
            top: -72px;
            right: -48px;
            width: 180px;
            height: 180px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(42, 82, 152, 0.18) 0%, rgba(42, 82, 152, 0) 72%);
            pointer-events: none;
        }

        .skilllink-confirm .modal-title {
            display: flex;
            align-items: center;
            gap: 14px;
            font-size: 1.15rem;
            font-weight: 800;
            letter-spacing: 0.01em;
        }

        .skilllink-confirm .confirm-icon-shell {
            width: 54px;
            height: 54px;
            border-radius: 18px;
            display: grid;
            place-items: center;
            color: #fff;
            font-size: 1.2rem;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            box-shadow: 0 16px 28px rgba(30, 60, 114, 0.24);
        }

        .skilllink-confirm[data-tone="danger"] .confirm-icon-shell {
            background: linear-gradient(135deg, #c0392b 0%, var(--danger-color) 100%);
            box-shadow: 0 16px 28px rgba(231, 76, 60, 0.22);
        }

        .skilllink-confirm[data-tone="success"] .confirm-icon-shell {
            background: linear-gradient(135deg, #0f9f6e 0%, var(--success-color) 100%);
            box-shadow: 0 16px 28px rgba(28, 200, 138, 0.22);
        }

        .skilllink-confirm[data-tone="warning"] .confirm-icon-shell {
            background: linear-gradient(135deg, #d97706 0%, #f6c23e 100%);
            box-shadow: 0 16px 28px rgba(246, 194, 62, 0.24);
        }

        .skilllink-confirm .modal-body {
            padding: 8px 26px 22px;
        }

        .skilllink-confirm .confirm-lead {
            margin: 0 0 10px;
            font-size: 1.08rem;
            font-weight: 700;
            color: var(--text-dark);
        }

        .skilllink-confirm .confirm-text {
            margin: 0;
            color: #5b6472;
            line-height: 1.65;
            white-space: pre-line;
            max-width: 42ch;
        }

        .skilllink-confirm .confirm-chip {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 16px;
            padding: 8px 12px;
            border-radius: 999px;
            background: rgba(30, 60, 114, 0.08);
            color: #36507a;
            font-size: 0.82rem;
            font-weight: 700;
            letter-spacing: 0.03em;
            text-transform: uppercase;
        }

        .skilllink-confirm[data-tone="danger"] .confirm-chip {
            background: rgba(231, 76, 60, 0.1);
            color: #b9382b;
        }

        .skilllink-confirm[data-tone="success"] .confirm-chip {
            background: rgba(28, 200, 138, 0.12);
            color: #0f7a55;
        }

        .skilllink-confirm[data-tone="warning"] .confirm-chip {
            background: rgba(246, 194, 62, 0.18);
            color: #9a6700;
        }

        .skilllink-confirm .modal-footer {
            padding: 0 26px 26px;
            border-top: 0;
            gap: 12px;
        }

        .skilllink-confirm .modal-footer .btn {
            min-width: 118px;
            border-radius: 14px;
            padding: 10px 18px;
            font-weight: 700;
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

        <?php if (empty($suppressFlashMessages)): ?>
            <div class="page-content">
                <div class="skilllink-flash-stack">
                    <?= view('layouts/flash_messages') ?>
                </div>
            </div>
        <?php endif; ?>
