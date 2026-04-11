<?= view('layouts/page_header', ['pageTitle' => 'Change Password', 'suppressFlashMessages' => true]) ?>

    <!-- Page Content -->
    <div class="page-content">
        <?php $errors = session('errors') ?? []; ?>
        <style>
            .field-error {
                margin-top: 6px;
                color: #c0392b;
                font-size: 0.88rem;
            }
        </style>
        <div class="row mb-4">
            <div class="col-12">
                <h3 class="mb-0"><i class="fas fa-key"></i> Change Password</h3>
            </div>
        </div>

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
                                    <input type="password" class="form-control <?= isset($errors['current_password']) ? 'is-invalid' : '' ?>" id="current_password" name="current_password" required>
                                    <?php if (isset($errors['current_password'])): ?>
                                        <div class="field-error"><?= esc($errors['current_password']) ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-6">
                                    <label for="new_password" class="form-label">New Password *</label>
                                    <input type="password" class="form-control <?= isset($errors['new_password']) ? 'is-invalid' : '' ?>" id="new_password" name="new_password" required minlength="6">
                                    <small class="text-muted">Minimum 6 characters</small>
                                    <?php if (isset($errors['new_password'])): ?>
                                        <div class="field-error"><?= esc($errors['new_password']) ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-6">
                                    <label for="confirm_password" class="form-label">Confirm New Password *</label>
                                    <input type="password" class="form-control <?= isset($errors['confirm_password']) ? 'is-invalid' : '' ?>" id="confirm_password" name="confirm_password" required minlength="6">
                                    <?php if (isset($errors['confirm_password'])): ?>
                                        <div class="field-error"><?= esc($errors['confirm_password']) ?></div>
                                    <?php endif; ?>
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
