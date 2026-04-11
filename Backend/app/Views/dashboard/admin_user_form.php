<?= view('layouts/page_header', ['pageTitle' => isset($editUser) ? 'Edit User' : 'Create User', 'suppressFlashMessages' => true]) ?>

    <!-- Page Content -->
    <div class="page-content">
        <?php $errors = session('errors') ?? []; ?>
        <div class="row mb-4">
            <div class="col-12">
                <h3 class="mb-0">
                    <i class="fas fa-user-<?= isset($editUser) ? 'edit' : 'plus' ?>"></i> 
                    <?= isset($editUser) ? 'Edit User' : 'Create New User' ?>
                </h3>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <i class="fas fa-info-circle"></i> User Information
            </div>
            <div class="card-body">
                <?php if (session()->has('error')): ?>
                    <div class="compact-form-notice error">
                        <i class="fas fa-circle-exclamation"></i>
                        <span><?= esc(session('error')) ?></span>
                    </div>
                <?php endif; ?>
                <form action="<?= isset($editUser) ? base_url('admin/users/update/' . $editUser['id']) : base_url('admin/users/store') ?>" method="post">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="first_name" class="form-label">First Name *</label>
                            <input type="text" class="form-control <?= isset($errors['first_name']) ? 'is-invalid' : '' ?>" id="first_name" name="first_name" 
                                   value="<?= esc(old('first_name', $editUser['first_name'] ?? '')) ?>" required>
                            <?php if (isset($errors['first_name'])): ?><div class="field-error"><?= esc($errors['first_name']) ?></div><?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <label for="last_name" class="form-label">Last Name *</label>
                            <input type="text" class="form-control <?= isset($errors['last_name']) ? 'is-invalid' : '' ?>" id="last_name" name="last_name" 
                                   value="<?= esc(old('last_name', $editUser['last_name'] ?? '')) ?>" required>
                            <?php if (isset($errors['last_name'])): ?><div class="field-error"><?= esc($errors['last_name']) ?></div><?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label">Email *</label>
                            <input type="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" id="email" name="email" 
                                   value="<?= esc(old('email', $editUser['email'] ?? '')) ?>" required>
                            <?php if (isset($errors['email'])): ?><div class="field-error"><?= esc($errors['email']) ?></div><?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <label for="phone" class="form-label">Phone</label>
                            <input type="text" class="form-control <?= isset($errors['phone']) ? 'is-invalid' : '' ?>" id="phone" name="phone" 
                                   value="<?= esc(old('phone', $editUser['phone'] ?? '')) ?>">
                            <?php if (isset($errors['phone'])): ?><div class="field-error"><?= esc($errors['phone']) ?></div><?php endif; ?>
                        </div>
                        <div class="col-12">
                            <label for="address" class="form-label">Address</label>
                            <textarea class="form-control <?= isset($errors['address']) ? 'is-invalid' : '' ?>" id="address" name="address" rows="2"><?= esc(old('address', $editUser['address'] ?? '')) ?></textarea>
                            <?php if (isset($errors['address'])): ?><div class="field-error"><?= esc($errors['address']) ?></div><?php endif; ?>
                        </div>
                        <div class="col-md-4">
                            <label for="user_type" class="form-label">User Role *</label>
                            <?php $isEditingSuperAdmin = isset($editUser) && (($editUser['user_type'] ?? '') === 'super_admin'); ?>
                            <select class="form-select <?= isset($errors['user_type']) ? 'is-invalid' : '' ?>" id="user_type" name="user_type" required onchange="toggleWorkerFields()">
                                <option value="">Select Role</option>
                                <?php if ($isEditingSuperAdmin): ?>
                                    <option value="super_admin" selected>Super Admin</option>
                                <?php endif; ?>
                                <option value="admin" <?= old('user_type', $editUser['user_type'] ?? '') === 'admin' ? 'selected' : '' ?>>Admin</option>
                                <option value="finance" <?= old('user_type', $editUser['user_type'] ?? '') === 'finance' ? 'selected' : '' ?>>Finance</option>
                                <option value="worker" <?= old('user_type', $editUser['user_type'] ?? '') === 'worker' ? 'selected' : '' ?>>Worker</option>
                                <option value="customer" <?= old('user_type', $editUser['user_type'] ?? '') === 'customer' ? 'selected' : '' ?>>Customer</option>
                            </select>
                            <?php if ($isEditingSuperAdmin): ?>
                                <small class="text-muted">Super admin role is fixed and cannot be reassigned.</small>
                            <?php endif; ?>
                            <?php if (isset($errors['user_type'])): ?><div class="field-error"><?= esc($errors['user_type']) ?></div><?php endif; ?>
                        </div>
                        <div class="col-md-4">
                            <label for="status" class="form-label">Status *</label>
                            <select class="form-select <?= isset($errors['status']) ? 'is-invalid' : '' ?>" id="status" name="status" required>
                                <option value="active" <?= old('status', $editUser['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Active</option>
                                <option value="inactive" <?= old('status', $editUser['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                <option value="suspended" <?= old('status', $editUser['status'] ?? '') === 'suspended' ? 'selected' : '' ?>>Suspended</option>
                            </select>
                            <?php if (isset($errors['status'])): ?><div class="field-error"><?= esc($errors['status']) ?></div><?php endif; ?>
                        </div>
                        <?php if (!isset($editUser)): ?>
                            <div class="col-md-4">
                                <label for="password" class="form-label">Password *</label>
                                <input type="password" class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>" id="password" name="password" required>
                                <?php if (isset($errors['password'])): ?><div class="field-error"><?= esc($errors['password']) ?></div><?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div class="col-md-4">
                                <label class="form-label">Password</label>
                                <input type="text" class="form-control" value="Password updates are disabled for admin user edits." readonly>
                                <small class="text-muted">Users must change their own password in Profile > Change Password.</small>
                            </div>
                        <?php endif; ?>

                        <!-- Worker-specific fields -->
                        <div id="workerFields" style="display: <?= old('user_type', $editUser['user_type'] ?? '') === 'worker' ? 'block' : 'none' ?>;">
                            <div class="col-12 mt-3">
                                <h5><i class="fas fa-tools"></i> Worker Details</h5>
                                <hr>
                            </div>
                            <div class="col-md-6">
                                <label for="skills" class="form-label">Skills (comma separated)</label>
                                <input type="text" class="form-control <?= isset($errors['skills']) ? 'is-invalid' : '' ?>" id="skills_input" name="skills_input" 
                                       value="<?= isset($editUser) && $editUser['skills'] ? esc(implode(', ', json_decode($editUser['skills'], true))) : '' ?>" 
                                       placeholder="e.g., plumbing, electrical, carpentry">
                                <small class="text-muted">Separate skills with commas</small>
                                <?php if (isset($errors['skills'])): ?><div class="field-error"><?= esc($errors['skills']) ?></div><?php endif; ?>
                            </div>
                            <div class="col-md-3">
                                <label for="experience_years" class="form-label">Years of Experience</label>
                                <input type="number" class="form-control <?= isset($errors['experience_years']) ? 'is-invalid' : '' ?>" id="experience_years" name="experience_years" 
                                       min="0" max="50" value="<?= esc(old('experience_years', $editUser['experience_years'] ?? 0)) ?>">
                                <?php if (isset($errors['experience_years'])): ?><div class="field-error"><?= esc($errors['experience_years']) ?></div><?php endif; ?>
                            </div>
                            <div class="col-md-3">
                                <label for="commission_rate" class="form-label">Commission Rate (%)</label>
                                <input type="number" class="form-control <?= isset($errors['commission_rate']) ? 'is-invalid' : '' ?>" id="commission_rate" name="commission_rate" 
                                       min="0" max="100" step="0.01" value="<?= esc(old('commission_rate', $editUser['commission_rate'] ?? 20.00)) ?>">
                                <?php if (isset($errors['commission_rate'])): ?><div class="field-error"><?= esc($errors['commission_rate']) ?></div><?php endif; ?>
                            </div>
                        </div>

                        <div class="col-12 mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> <?= isset($editUser) ? 'Update User' : 'Create User' ?>
                            </button>
                            <a href="<?= base_url('admin/users') ?>" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function toggleWorkerFields() {
            const userType = document.getElementById('user_type').value;
            const workerFields = document.getElementById('workerFields');
            workerFields.style.display = (userType === 'worker') ? 'block' : 'none';
        }

        // Process skills input before form submission
        document.querySelector('form').addEventListener('submit', function(e) {
            const userType = document.getElementById('user_type').value;
            if (userType === 'worker') {
                const skillsInput = document.getElementById('skills_input').value;
                const skillsArray = skillsInput.split(',').map(s => s.trim()).filter(s => s);
                
                // Remove old skills inputs
                document.querySelectorAll('input[name="skills[]"]').forEach(el => el.remove());
                
                // Add new skills as array
                skillsArray.forEach(skill => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'skills[]';
                    input.value = skill;
                    this.appendChild(input);
                });
            }
        });
    </script>

<?= view('layouts/page_footer') ?>
