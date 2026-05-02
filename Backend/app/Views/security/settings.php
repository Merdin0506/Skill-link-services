<?= $this->extend('layouts/security_base') ?>

<?= $this->section('content') ?>
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h3 class="mb-0"><i class="fas fa-cog"></i> Security Settings</h3>
                    <small class="text-muted">Configuration placeholder (policy settings can be added here)</small>
                </div>
            </div>

            <div class="alert alert-info">
                This page is available and protected by admin-only access control. If you want, I can wire real settings here (e.g., brute force thresholds, block durations, email alert toggles).
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Brute Force Threshold</label>
                            <input class="form-control" value="5 failed logins" disabled>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Block Duration</label>
                            <input class="form-control" value="30 minutes" disabled>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Email Alerts</label>
                            <input class="form-control" value="Enabled" disabled>
                        </div>
                    </div>
                </div>
            </div>

<?= $this->endSection() ?>
