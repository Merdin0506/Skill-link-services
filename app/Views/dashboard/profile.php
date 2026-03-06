<?= view('layouts/page_header', ['pageTitle' => 'Profile']) ?>

    <!-- Page Content -->
    <div class="page-content">
        <div class="row mb-4">
            <div class="col-12">
                <h3 class="mb-0"><i class="fas fa-user-circle"></i> My Profile</h3>
            </div>
        </div>

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
                </div>
            </div>
        </div>
    </div>

<?= view('layouts/page_footer') ?>
