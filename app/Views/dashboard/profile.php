<?= view('layouts/page_header', ['pageTitle' => 'Profile']) ?>

    <!-- Page Content -->
    <div class="page-content">
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <h3 class="mb-0"><i class="fas fa-user-circle"></i> My Profile</h3>
                    <div>
                        <a href="<?= base_url('profile/edit') ?>" class="btn btn-primary btn-sm">
                            <i class="fas fa-edit"></i> Edit Profile
                        </a>
                        <a href="<?= base_url('profile/change-password') ?>" class="btn btn-warning btn-sm">
                            <i class="fas fa-key"></i> Change Password
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <?php if (session()->has('success')): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle"></i> <?= esc(session('success')) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <i class="fas fa-info-circle"></i> Profile Information
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6"><strong>Name:</strong> <?= esc(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?></div>
                    <div class="col-md-6"><strong>Email:</strong> <?= esc($user['email'] ?? '-') ?></div>
                    <div class="col-md-6"><strong>Role:</strong> <?= esc(ucfirst($role ?? ($user['user_type'] ?? ''))) ?></div>
                    <div class="col-md-6"><strong>Phone:</strong> <?= esc($user['phone'] ?? '-') ?></div>
                    <div class="col-12"><strong>Address:</strong> <?= esc($user['address'] ?? '-') ?></div>
                    
                    <?php if (isset($user['user_type']) && $user['user_type'] === 'worker'): ?>
                        <div class="col-12 mt-3">
                            <h5><i class="fas fa-tools"></i> Worker Details</h5>
                            <hr>
                        </div>
                        <div class="col-md-6">
                            <strong>Skills:</strong> 
                            <?php if (isset($user['skills']) && $user['skills']): ?>
                                <?php $skills = json_decode($user['skills'], true); ?>
                                <?php if (is_array($skills) && count($skills) > 0): ?>
                                    <?php foreach ($skills as $skill): ?>
                                        <span class="badge bg-primary me-1"><?= esc($skill) ?></span>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <strong>Experience:</strong> <?= esc($user['experience_years'] ?? 0) ?> years
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

<?= view('layouts/page_footer') ?>
