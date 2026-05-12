<?= view('layouts/page_header', ['pageTitle' => 'Edit Profile', 'suppressFlashMessages' => true]) ?>

    <!-- Page Content -->
    <div class="page-content">
        <?php $errors = session('errors') ?? []; ?>
        <div class="row mb-4">
            <div class="col-12">
                <h3 class="mb-0"><i class="fas fa-user-edit"></i> Edit My Profile</h3>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <i class="fas fa-info-circle"></i> Personal Information
            </div>
            <div class="card-body">
                <?php if (session()->has('error')): ?>
                    <div class="compact-form-notice error">
                        <i class="fas fa-circle-exclamation"></i>
                        <span><?= esc(session('error')) ?></span>
                    </div>
                <?php endif; ?>
                <form action="<?= base_url('profile/update') ?>" method="post" enctype="multipart/form-data">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="first_name" class="form-label">First Name *</label>
                            <input type="text" class="form-control <?= isset($errors['first_name']) ? 'is-invalid' : '' ?>" id="first_name" name="first_name" 
                                   value="<?= esc(old('first_name', $user['first_name'] ?? '')) ?>" required>
                            <?php if (isset($errors['first_name'])): ?><div class="field-error"><?= esc($errors['first_name']) ?></div><?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <label for="last_name" class="form-label">Last Name *</label>
                            <input type="text" class="form-control <?= isset($errors['last_name']) ? 'is-invalid' : '' ?>" id="last_name" name="last_name" 
                                   value="<?= esc(old('last_name', $user['last_name'] ?? '')) ?>" required>
                            <?php if (isset($errors['last_name'])): ?><div class="field-error"><?= esc($errors['last_name']) ?></div><?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label">Email *</label>
                            <input type="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" id="email" name="email" 
                                   value="<?= esc(old('email', $user['email'] ?? '')) ?>" required>
                            <?php if (isset($errors['email'])): ?><div class="field-error"><?= esc($errors['email']) ?></div><?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <label for="phone" class="form-label">Phone</label>
                            <input type="text" class="form-control <?= isset($errors['phone']) ? 'is-invalid' : '' ?>" id="phone" name="phone" 
                                   value="<?= esc(old('phone', $user['phone'] ?? '')) ?>">
                            <?php if (isset($errors['phone'])): ?><div class="field-error"><?= esc($errors['phone']) ?></div><?php endif; ?>
                        </div>
                        <div class="col-12">
                            <label for="address" class="form-label">Address</label>
                            <textarea class="form-control <?= isset($errors['address']) ? 'is-invalid' : '' ?>" id="address" name="address" rows="3"><?= esc(old('address', $user['address'] ?? '')) ?></textarea>
                            <?php if (isset($errors['address'])): ?><div class="field-error"><?= esc($errors['address']) ?></div><?php endif; ?>
                        </div>

                        <?php if (isset($user['user_type']) && $user['user_type'] === 'worker'): ?>
                            <div class="col-12 mt-3">
                                <h5><i class="fas fa-tools"></i> Worker Details</h5>
                                <hr>
                            </div>
                            <div class="col-md-8">
                                <label for="skills" class="form-label">Skills (comma separated)</label>
                                <input type="text" class="form-control <?= isset($errors['skills']) ? 'is-invalid' : '' ?>" id="skills_input" name="skills_input" 
                                       value="<?= isset($user['skills']) && $user['skills'] ? esc(implode(', ', json_decode($user['skills'], true))) : '' ?>" 
                                       placeholder="e.g., plumbing, electrical, carpentry">
                                <small class="text-muted">Separate skills with commas</small>
                                <?php if (isset($errors['skills'])): ?><div class="field-error"><?= esc($errors['skills']) ?></div><?php endif; ?>
                            </div>
                            <div class="col-md-4">
                                <label for="experience_years" class="form-label">Years of Experience</label>
                                <input type="number" class="form-control <?= isset($errors['experience_years']) ? 'is-invalid' : '' ?>" id="experience_years" name="experience_years" 
                                       min="0" max="50" value="<?= esc(old('experience_years', $user['experience_years'] ?? 0)) ?>">
                                <?php if (isset($errors['experience_years'])): ?><div class="field-error"><?= esc($errors['experience_years']) ?></div><?php endif; ?>
                            </div>
                            <div class="col-12">
                                <label for="resume_upload" class="form-label">Upload File (Optional)</label>
                                <input type="file" class="form-control" id="resume_upload" name="resume_upload" accept="application/pdf,.pdf">
                                <small class="text-muted">Accepted format: PDF only. Max size: 5MB.</small>
                                <?php
                                    $storedResumePath = (string) ($user['resume_path'] ?? '');
                                    $currentResumePaths = [];
                                    if ($storedResumePath !== '') {
                                        $decodedResumePath = json_decode($storedResumePath, true);
                                        if (is_array($decodedResumePath)) {
                                            $currentResumePaths = array_values(array_filter(array_map('strval', $decodedResumePath)));
                                        } else {
                                            $currentResumePaths = [$storedResumePath];
                                        }
                                    }
                                ?>
                                <?php if (!empty($currentResumePaths)): ?>
                                    <div class="mt-2 text-muted small">Current files:</div>
                                    <div class="d-flex flex-wrap gap-2 mt-1">
                                        <?php foreach ($currentResumePaths as $resumeFilePath): ?>
                                            <span class="badge bg-light text-dark border"><?= esc(basename($resumeFilePath)) ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
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

        // If opened from "Upload File", focus and highlight the upload field.
        if (window.location.hash === '#resume_upload') {
            const resumeInput = document.getElementById('resume_upload');
            if (resumeInput) {
                resumeInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
                resumeInput.classList.add('border', 'border-primary', 'shadow-sm');
                resumeInput.focus();
            }
        }
    </script>
    <?php endif; ?>

<?= view('layouts/page_footer') ?>
