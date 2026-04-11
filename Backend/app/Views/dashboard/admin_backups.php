<?= view('layouts/page_header', ['pageTitle' => 'Backup Management']) ?>

<div class="page-content">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <h3 class="mb-0">
                    <i class="fas fa-database"></i> Backup Management
                </h3>
                <form action="<?= base_url('admin/backups/create') ?>" method="post" class="d-inline">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Create Backup Now
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small">Total Backups</div>
                        <h2 class="mb-0"><?= esc((string) ($backupSummary['count'] ?? 0)) ?></h2>
                    </div>
                    <i class="fas fa-box-archive fa-2x text-primary"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card info">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small">Stored Size</div>
                        <h2 class="mb-0"><?= esc(number_format(((int) ($backupSummary['total_size'] ?? 0)) / 1024, 2)) ?> KB</h2>
                    </div>
                    <i class="fas fa-hard-drive fa-2x text-info"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card success">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small">Latest Backup</div>
                        <h6 class="mb-1"><?= esc($backupSummary['latest_backup']['filename'] ?? 'No backups yet') ?></h6>
                        <small class="text-muted">
                            <?= !empty($backupSummary['latest_backup']['modified_at']) ? esc(date('Y-m-d H:i:s', (int) $backupSummary['latest_backup']['modified_at'])) : 'Create your first backup' ?>
                        </small>
                    </div>
                    <i class="fas fa-clock-rotate-left fa-2x text-success"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-shield-halved"></i> Restore Guidance
        </div>
        <div class="card-body">
            <p class="mb-2">Restoring a backup will replace the current database contents with the selected backup file.</p>
            <p class="mb-0">For safety, the system creates a pre-restore backup automatically before starting the restore process.</p>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <i class="fas fa-list"></i> Available Backups
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Filename</th>
                            <th>Created</th>
                            <th>Size</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (!empty($backups)): ?>
                        <?php foreach ($backups as $backup): ?>
                            <tr>
                                <td><?= esc($backup['filename']) ?></td>
                                <td><?= esc(date('Y-m-d H:i:s', (int) $backup['modified_at'])) ?></td>
                                <td><?= esc(number_format(((int) $backup['size']) / 1024, 2)) ?> KB</td>
                                <td>
                                    <form action="<?= base_url('admin/backups/restore') ?>" method="post" class="d-inline" data-confirm-title="Restore backup?" data-confirm-message="This will replace the current database with the selected backup." data-confirm-label="Restore backup" data-confirm-class="btn-warning">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="backup_file" value="<?= esc($backup['filename']) ?>">
                                        <button type="submit" class="btn btn-sm btn-warning">
                                            <i class="fas fa-rotate-left"></i> Restore
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center py-4">No backup files found yet.</td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?= view('layouts/page_footer') ?>
