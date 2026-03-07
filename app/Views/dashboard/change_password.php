<?= view('layouts/page_header', ['pageTitle' => 'Change Password']) ?>

    <!-- Page Content -->
    <div class="page-content">
        <div class="row mb-4">
            <div class="col-12">
                <h3 class="mb-0"><i class="fas fa-key"></i> Change Password</h3>
            </div>
        </div>

        <?php if (session()->has('success')): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle"></i> <?= esc(session('success')) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (session()->has('error')): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle"></i> <?= esc(session('error')) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (session()->has('errors')): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle"></i> <strong>Please fix the following errors:</strong>
                <ul class="mb-0 mt-2">
                    <?php foreach (session('errors') as $error): ?>
                        <li><?= esc($error) ?></li>
                    <?php endforeach; ?>
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-lock"></i> Update Your Password
                    </div>
                    <div class="card-body">
                        <form action="<?= base_url('profile/update-password') ?>" method="post">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label for="current_password" class="form-label">Current Password *</label>
                                    <input type="password" class="form-control" id="current_password" name="current_password" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="new_password" class="form-label">New Password *</label>
                                    <input type="password" class="form-control" id="new_password" name="new_password" required minlength="6">
                                    <small class="text-muted">Minimum 6 characters</small>
                                </div>
                                <div class="col-md-6">
                                    <label for="confirm_password" class="form-label">Confirm New Password *</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="6">
                                </div>
                                <div class="col-12 mt-4">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Change Password
                                    </button>
                                    <a href="<?= base_url('profile') ?>" class="btn btn-secondary">
                                        <i class="fas fa-times"></i> Cancel
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card mt-4 border-danger">
                    <div class="card-header bg-danger text-white">
                        <i class="fas fa-exclamation-triangle"></i> Danger Zone
                    </div>
                    <div class="card-body">
                        <h5>Delete Account</h5>
                        <p class="text-muted">Once you delete your account, there is no going back. This action is permanent and cannot be undone.</p>
                        <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteAccountModal">
                            <i class="fas fa-trash"></i> Delete My Account
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Account Confirmation Modal -->
    <div class="modal fade" id="deleteAccountModal" tabindex="-1" aria-labelledby="deleteAccountModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteAccountModalLabel">
                        <i class="fas fa-exclamation-triangle"></i> Confirm Account Deletion
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p><strong>Are you absolutely sure you want to delete your account?</strong></p>
                    <p>This action will:</p>
                    <ul>
                        <li>Permanently deactivate your account</li>
                        <li>Remove access to all your data</li>
                        <li>Log you out immediately</li>
                    </ul>
                    <p class="text-danger"><strong>This action cannot be undone!</strong></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form action="<?= base_url('profile/delete-account') ?>" method="post" class="d-inline">
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash"></i> Yes, Delete My Account
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

<?= view('layouts/page_footer') ?>
