<?= view('layouts/page_header', ['pageTitle' => isset($editUser) ? 'Edit User' : 'Create User']) ?>

    <!-- Page Content -->
    <div class="page-content">
        <div class="row mb-4">
            <div class="col-12">
                <h3 class="mb-0">
                    <i class="fas fa-user-<?= isset($editUser) ? 'edit' : 'plus' ?>"></i> 
                    <?= isset($editUser) ? 'Edit User' : 'Create New User' ?>
                </h3>
            </div>
        </div>

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

        <div class="card">
            <div class="card-header">
                <i class="fas fa-info-circle"></i> User Information
            </div>
            <div class="card-body">
                <form action="<?= isset($editUser) ? base_url('admin/users/update/' . $editUser['id']) : base_url('admin/users/store') ?>" method="post">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="first_name" class="form-label">First Name *</label>
                            <input type="text" class="form-control" id="first_name" name="first_name" 
                                   value="<?= esc(old('first_name', $editUser['first_name'] ?? '')) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="last_name" class="form-label">Last Name *</label>
                            <input type="text" class="form-control" id="last_name" name="last_name" 
                                   value="<?= esc(old('last_name', $editUser['last_name'] ?? '')) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label">Email *</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?= esc(old('email', $editUser['email'] ?? '')) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="phone" class="form-label">Phone</label>
                            <input type="text" class="form-control" id="phone" name="phone" 
                                   value="<?= esc(old('phone', $editUser['phone'] ?? '')) ?>">
                        </div>
                        <div class="col-12">
                            <label for="address" class="form-label">Address</label>
                            <textarea class="form-control" id="address" name="address" rows="2"><?= esc(old('address', $editUser['address'] ?? '')) ?></textarea>
                        </div>
                        <div class="col-md-4">
                            <label for="user_type" class="form-label">User Role *</label>
                            <?php $isEditingSuperAdmin = isset($editUser) && (($editUser['user_type'] ?? '') === 'super_admin'); ?>
                            <select class="form-select" id="user_type" name="user_type" required onchange="toggleWorkerFields()">
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
                        </div>
                        <div class="col-md-4">
                            <label for="status" class="form-label">Status *</label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="active" <?= old('status', $editUser['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Active</option>
                                <option value="inactive" <?= old('status', $editUser['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                <option value="suspended" <?= old('status', $editUser['status'] ?? '') === 'suspended' ? 'selected' : '' ?>>Suspended</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="password" class="form-label">Password <?= isset($editUser) ? '(Leave blank to keep current)' : '*' ?></label>
                            <input type="password" class="form-control" id="password" name="password" 
                                   <?= !isset($editUser) ? 'required' : '' ?>>
                        </div>

                        <!-- Worker-specific fields -->
                        <div id="workerFields" style="display: <?= old('user_type', $editUser['user_type'] ?? '') === 'worker' ? 'block' : 'none' ?>;">
                            <div class="col-12 mt-3">
                                <h5><i class="fas fa-tools"></i> Worker Details</h5>
                                <hr>
                            </div>
                            <div class="col-md-6">
                                <label for="skills" class="form-label">Skills (comma separated)</label>
                                <input type="text" class="form-control" id="skills_input" name="skills_input" 
                                       value="<?= isset($editUser) && $editUser['skills'] ? esc(implode(', ', json_decode($editUser['skills'], true))) : '' ?>" 
                                       placeholder="e.g., plumbing, electrical, carpentry">
                                <small class="text-muted">Separate skills with commas</small>
                            </div>
                            <div class="col-md-3">
                                <label for="experience_years" class="form-label">Years of Experience</label>
                                <input type="number" class="form-control" id="experience_years" name="experience_years" 
                                       min="0" max="50" value="<?= esc(old('experience_years', $editUser['experience_years'] ?? 0)) ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="commission_rate" class="form-label">Commission Rate (%)</label>
                                <input type="number" class="form-control" id="commission_rate" name="commission_rate" 
                                       min="0" max="100" step="0.01" value="<?= esc(old('commission_rate', $editUser['commission_rate'] ?? 20.00)) ?>">
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
