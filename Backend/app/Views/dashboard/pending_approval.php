<?php
// Ensure we have required variables
$user = $user ?? [];
$currentUser = $currentUser ?? [];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pending Approval - Skill Link Services</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= base_url('css/sidebar.css') ?>">
    <link rel="stylesheet" href="<?= base_url('css/dashboard-layout-unified.css') ?>">
    <style>
        :root {
            --primary-color: #1e3c72;
            --secondary-color: #2a5298;
            --success-color: #1cc88a;
            --warning-color: #f6c23e;
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
            min-height: 160px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .stat-card .stat-icon {
            font-size: 32px;
            margin-bottom: 12px;
            opacity: 0.85;
        }

        .stat-card .stat-value {
            font-size: 1.6rem;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .stat-card .stat-label {
            font-size: 14px;
            color: var(--text-muted);
            font-weight: 500;
        }

        .stat-card.warning {
            border-top-color: var(--warning-color);
        }

        .stat-card.opacity-75 {
            opacity: 0.65;
        }

        .card {
            border: none;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .card-header {
            background: white;
            border-bottom: 1px solid #e8e8e8;
            padding: 18px 20px;
            font-weight: 600;
            color: var(--text-dark);
            border-radius: 10px 10px 0 0;
        }

        .card-header i {
            margin-right: 8px;
            color: var(--primary-color);
        }

        .card-body {
            padding: 20px;
        }

        .status-badge {
            display: inline-block;
            background: rgba(246, 194, 62, 0.1);
            color: #856404;
            padding: 10px 20px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 14px;
            border: 1px solid #ffc107;
            margin-bottom: 20px;
        }

        .profile-row {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 15px;
        }

        .profile-item {
            display: flex;
            flex-direction: column;
        }

        .profile-label {
            font-size: 12px;
            color: var(--text-muted);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
        }

        .profile-value {
            font-size: 15px;
            color: var(--text-dark);
            font-weight: 500;
        }

        .skills-container {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 10px;
        }

        .skill-badge {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 500;
        }

        .feature-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 12px 0;
            border-bottom: 1px solid #e8e8e8;
        }

        .feature-item:last-child {
            border-bottom: none;
        }

        .feature-icon {
            font-size: 24px;
            color: #bbb;
        }

        .feature-info {
            flex: 1;
        }

        .feature-name {
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 3px;
        }

        .feature-status {
            font-size: 12px;
            color: var(--text-muted);
        }

        .alert-pending {
            background: linear-gradient(135deg, rgba(246, 194, 62, 0.1), rgba(255, 193, 7, 0.1));
            border: 1px solid #ffc107;
            border-radius: 10px;
            padding: 16px;
            margin-bottom: 24px;
            display: flex;
            gap: 15px;
            align-items: flex-start;
        }

        .alert-pending-icon {
            font-size: 28px;
            color: #f6c23e;
            flex-shrink: 0;
        }

        .alert-pending-content h5 {
            margin: 0 0 5px 0;
            color: #856404;
            font-weight: 600;
        }

        .alert-pending-content p {
            margin: 0;
            color: #856404;
            font-size: 14px;
        }

        .edit-btn {
            display: inline-block;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            margin-top: 15px;
            transition: all 0.3s ease;
        }

        .edit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(30, 60, 114, 0.3);
        }

        @media (max-width: 768px) {
            .profile-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Include Sidebar -->
    <?= view('layouts/sidebar', ['role' => session()->get('role') ?? 'worker', 'user' => $user]) ?>

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
                    <p class="mb-0 text-muted"><?= htmlspecialchars(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?></p>
                </div>
            </div>
            <div class="user-profile">
                <span class="role-badge"><?= ucfirst(session()->get('role') ?? 'Worker') ?></span>
                <div>
                    <p class="mb-0"><?= htmlspecialchars($user['email'] ?? '') ?></p>
                    <small class="text-muted">Pending Approval</small>
                </div>
            </div>
        </div>

        <!-- Page Content -->
        <div class="page-content">
            <div class="container-fluid">
                <!-- Status Banner -->
                <div class="alert-pending">
                    <div class="alert-pending-icon">
                        <i class="fas fa-hourglass-half"></i>
                    </div>
                    <div class="alert-pending-content">
                        <h5>Your account is under review</h5>
                        <p>Please wait for admin approval. Some features are currently disabled.</p>
                    </div>
                </div>

                <!-- Status Card -->
                <div class="row mb-4">
                    <div class="col-md-6 col-lg-4 mb-3">
                        <div class="stat-card warning">
                            <i class="fas fa-hourglass-half stat-icon"></i>
                            <div class="stat-value">Pending</div>
                            <div class="stat-label">Application Status</div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-4 mb-3">
                        <div class="stat-card opacity-75" style="border-top-color: var(--primary-color);">
                            <i class="fas fa-briefcase stat-icon"></i>
                            <div class="stat-value">—</div>
                            <div class="stat-label">Available Jobs</div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-4 mb-3">
                        <div class="stat-card opacity-75" style="border-top-color: var(--success-color);">
                            <i class="fas fa-wallet stat-icon"></i>
                            <div class="stat-value">—</div>
                            <div class="stat-label">Earnings</div>
                        </div>
                    </div>
                </div>

                <!-- Main Content Grid -->
                <div class="row">
                    <!-- Profile Section -->
                    <div class="col-lg-6 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <i class="fas fa-user-circle"></i> Your Profile
                            </div>
                            <div class="card-body">
                                <div class="profile-row">
                                    <div class="profile-item">
                                        <div class="profile-label">First Name</div>
                                        <div class="profile-value"><?= htmlspecialchars($user['first_name'] ?? 'N/A') ?></div>
                                    </div>
                                    <div class="profile-item">
                                        <div class="profile-label">Last Name</div>
                                        <div class="profile-value"><?= htmlspecialchars($user['last_name'] ?? 'N/A') ?></div>
                                    </div>
                                </div>

                                <div class="profile-row">
                                    <div class="profile-item">
                                        <div class="profile-label">Email</div>
                                        <div class="profile-value"><?= htmlspecialchars($user['email'] ?? 'N/A') ?></div>
                                    </div>
                                    <div class="profile-item">
                                        <div class="profile-label">Phone</div>
                                        <div class="profile-value"><?= htmlspecialchars($user['phone'] ?? 'N/A') ?></div>
                                    </div>
                                </div>

                                <div class="profile-row">
                                    <div class="profile-item">
                                        <div class="profile-label">Experience</div>
                                        <div class="profile-value"><?= htmlspecialchars($user['experience_years'] ?? 0) ?> years</div>
                                    </div>
                                </div>

                                <?php if (!empty($user['skills'])): ?>
                                <div class="profile-item">
                                    <div class="profile-label">Skills</div>
                                    <div class="skills-container">
                                        <?php 
                                        $skills = is_array($user['skills']) ? $user['skills'] : json_decode($user['skills'] ?? '[]', true);
                                        $skillLabels = [
                                            'web_design' => 'Web Design',
                                            'graphic_design' => 'Graphic Design',
                                            'video_editing' => 'Video Editing',
                                            'social_media' => 'Social Media',
                                            'writing' => 'Writing',
                                            'translation' => 'Translation',
                                            'coding' => 'Coding',
                                            'data_entry' => 'Data Entry',
                                            'virtual_assistant' => 'Virtual Assistant',
                                            'digital_marketing' => 'Digital Marketing',
                                        ];
                                        foreach ($skills as $skill):
                                            $label = $skillLabels[$skill] ?? ucfirst(str_replace('_', ' ', $skill));
                                        ?>
                                            <span class="skill-badge"><?= htmlspecialchars($label) ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <div class="d-flex gap-2 flex-wrap">
                                    <button class="edit-btn">
                                        <i class="fas fa-edit me-2"></i> Edit Profile
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Application Status & Tips -->
                    <div class="col-lg-6 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <i class="fas fa-tasks"></i> Application Details
                            </div>
                            <div class="card-body">
                                <div class="status-badge">
                                    <i class="fas fa-hourglass-half me-2"></i> Under Admin Review
                                </div>

                                <div class="profile-item" style="margin-bottom: 20px;">
                                    <div class="profile-label">Submitted On</div>
                                    <div class="profile-value">
                                        <?php 
                                        $createdAt = $user['created_at'] ?? date('Y-m-d H:i:s');
                                        echo date('F j, Y', strtotime($createdAt));
                                        ?>
                                    </div>
                                </div>

                                <h6 style="color: var(--text-dark); margin-bottom: 15px; font-weight: 600;">What Happens Next?</h6>
                                <ul style="margin: 0; padding-left: 20px; color: var(--text-muted); line-height: 1.8; font-size: 14px;">
                                    <li>Admin will review your profile and qualifications</li>
                                    <li>You'll receive an email once approved or if changes are needed</li>
                                    <li>Approval typically takes 24-48 hours</li>
                                    <li>You can edit your profile while waiting</li>
                                </ul>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header">
                                <i class="fas fa-lightbulb"></i> Tips to Improve Approval
                            </div>
                            <div class="card-body">
                                <ul style="margin: 0; padding-left: 20px; color: var(--text-dark); line-height: 1.8; font-size: 14px;">
                                    <li><strong>Complete Profile:</strong> Ensure all details are filled</li>
                                    <li><strong>Upload Resume:</strong> Add a professional resume</li>
                                    <li><strong>Add Skills:</strong> Showcase all your expertise</li>
                                    <li><strong>File Quality:</strong> Keep your uploaded file updated and complete</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Disabled Features Section -->
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header">
                                <i class="fas fa-lock"></i> Disabled Features (Available After Approval)
                            </div>
                            <div class="card-body">
                                <div class="feature-item">
                                    <div class="feature-icon">
                                        <i class="fas fa-briefcase"></i>
                                    </div>
                                    <div class="feature-info">
                                        <div class="feature-name">Apply for Jobs</div>
                                        <div class="feature-status">Browse and apply for available job listings</div>
                                    </div>
                                </div>

                                <div class="feature-item">
                                    <div class="feature-icon">
                                        <i class="fas fa-list"></i>
                                    </div>
                                    <div class="feature-info">
                                        <div class="feature-name">View Job Listings</div>
                                        <div class="feature-status">See all available job opportunities</div>
                                    </div>
                                </div>

                                <div class="feature-item">
                                    <div class="feature-icon">
                                        <i class="fas fa-chart-line"></i>
                                    </div>
                                    <div class="feature-info">
                                        <div class="feature-name">Analytics Dashboard</div>
                                        <div class="feature-status">Track your performance and earnings</div>
                                    </div>
                                </div>

                                <div class="feature-item">
                                    <div class="feature-icon">
                                        <i class="fas fa-wallet"></i>
                                    </div>
                                    <div class="feature-info">
                                        <div class="feature-name">Earnings & Withdrawals</div>
                                        <div class="feature-status">View earnings and manage payouts</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>&copy; <?= date('Y') ?> Skill Link Services. All rights reserved.</p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= base_url('js/sidebar-toggle.js') ?>"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelector('.edit-btn')?.addEventListener('click', function() {
                window.location.href = '<?= base_url('/profile/edit') ?>';
            });
        });
    </script>
</body>
</html>