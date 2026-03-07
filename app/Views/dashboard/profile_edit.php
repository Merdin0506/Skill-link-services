<?= view('layouts/page_header', ['pageTitle' => 'Edit Profile']) ?>

    <!-- Page Content -->
    <div class="page-content">
        <div class="row mb-4">
            <div class="col-12">
                <h3 class="mb-0"><i class="fas fa-user-edit"></i> Edit My Profile</h3>
            </div>
        </div>

        <?php if (session()->has('success')): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle"></i> <?= esc(session('success')) ?>
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

        <div class="card">
            <div class="card-header">
                <i class="fas fa-info-circle"></i> Personal Information
            </div>
            <div class="card-body">
                <form action="<?= base_url('profile/update') ?>" method="post">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="first_name" class="form-label">First Name *</label>
                            <input type="text" class="form-control" id="first_name" name="first_name" 
                                   value="<?= esc(old('first_name', $user['first_name'] ?? '')) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="last_name" class="form-label">Last Name *</label>
                            <input type="text" class="form-control" id="last_name" name="last_name" 
                                   value="<?= esc(old('last_name', $user['last_name'] ?? '')) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label">Email *</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?= esc(old('email', $user['email'] ?? '')) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="phone" class="form-label">Phone</label>
                            <input type="text" class="form-control" id="phone" name="phone" 
                                   value="<?= esc(old('phone', $user['phone'] ?? '')) ?>">
                        </div>
                        <div class="col-12">
                            <label for="address" class="form-label">Address</label>
                            <textarea class="form-control" id="address" name="address" rows="3"><?= esc(old('address', $user['address'] ?? '')) ?></textarea>
                        </div>

                        <?php if (isset($user['user_type']) && $user['user_type'] === 'worker'): ?>
                            <div class="col-12 mt-3">
                                <h5><i class="fas fa-tools"></i> Worker Details</h5>
                                <hr>
                            </div>
                            <div class="col-md-8">
                                <label for="skills" class="form-label">Skills (comma separated)</label>
                                <input type="text" class="form-control" id="skills_input" name="skills_input" 
                                       value="<?= isset($user['skills']) && $user['skills'] ? esc(implode(', ', json_decode($user['skills'], true))) : '' ?>" 
                                       placeholder="e.g., plumbing, electrical, carpentry">
                                <small class="text-muted">Separate skills with commas</small>
                            </div>
                            <div class="col-md-4">
                                <label for="experience_years" class="form-label">Years of Experience</label>
                                <input type="number" class="form-control" id="experience_years" name="experience_years" 
                                       min="0" max="50" value="<?= esc(old('experience_years', $user['experience_years'] ?? 0)) ?>">
                            </div>
                        <?php endif; ?>

                        <div class="col-12 mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Changes
                            </button>
                            <a href="<?= base_url('profile') ?>" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php if (isset($user['user_type']) && $user['user_type'] === 'worker'): ?>
    <script>
        // Process skills input before form submission
        document.querySelector('form').addEventListener('submit', function(e) {
            const skillsInput = document.getElementById('skills_input');
            if (skillsInput) {
                const skillsArray = skillsInput.value.split(',').map(s => s.trim()).filter(s => s);
                
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
    <?php endif; ?>

<?= view('layouts/page_footer') ?>
