<?= view('layouts/page_header', ['pageTitle' => 'Users']) ?>

    <!-- Page Content -->
    <div class="page-content">
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <h3 class="mb-0">
                        <i class="fas fa-users"></i> Users Management
                        <?= !empty($show_deleted) ? '<span class="badge bg-secondary">Archived</span>' : '' ?>
                    </h3>
                    <div class="d-flex gap-2">
                        <?php if (!empty($show_deleted)): ?>
                            <a href="<?= base_url('admin/users') ?>" class="btn btn-outline-primary">
                                <i class="fas fa-arrow-left"></i> Back to Active Users
                            </a>
                        <?php else: ?>
                            <a href="<?= base_url('admin/users?show_deleted=1') ?>" class="btn btn-outline-secondary">
                                <i class="fas fa-box-archive"></i> View Archived
                            </a>
                            <a href="<?= base_url('admin/users/create') ?>" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Add New User
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <i class="fas fa-list"></i> <?= !empty($show_deleted) ? 'Archived Users' : 'All Users' ?>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (!empty($users)): ?>
                            <?php foreach ($users as $row): ?>
                                <tr>
                                    <td><?= esc($row['id']) ?></td>
                                    <td><?= esc(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')) ?></td>
                                    <td><?= esc($row['email'] ?? '-') ?></td>
                                    <td><span class="badge bg-primary"><?= esc($row['user_type'] ?? '-') ?></span></td>
                                    <td><span class="badge bg-<?= ($row['status'] ?? '') === 'active' ? 'success' : 'secondary' ?>"><?= esc($row['status'] ?? '-') ?></span></td>
                                    <td><?= esc($row['created_at'] ?? '-') ?></td>
                                    <td>
                                        <?php $isSuperAdmin = (($row['user_type'] ?? '') === 'super_admin'); ?>
                                        <?php if (!empty($show_deleted)): ?>
                                            <form action="<?= base_url('admin/users/restore/' . $row['id']) ?>" method="post" class="d-inline" data-confirm-message="Restore this user?" data-confirm-label="Restore" data-confirm-class="btn-success">
                                                <button type="submit" class="btn btn-sm btn-success" title="Restore">
                                                    <i class="fas fa-rotate-left"></i>
                                                </button>
                                            </form>
                                            <form action="<?= base_url('admin/users/delete-permanent/' . $row['id']) ?>" method="post" class="d-inline" data-confirm-title="Delete user permanently?" data-confirm-message="This cannot be undone." data-confirm-label="Delete permanently" data-confirm-class="btn-danger">
                                                <button type="submit" class="btn btn-sm btn-danger" title="Delete Permanently">
                                                    <i class="fas fa-user-slash"></i>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <a href="<?= base_url('admin/users/edit/' . $row['id']) ?>" class="btn btn-sm btn-warning" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <?php if ($isSuperAdmin): ?>
                                                <button type="button" class="btn btn-sm btn-secondary" title="Super admin cannot be deleted" disabled>
                                                    <i class="fas fa-lock"></i>
                                                </button>
                                            <?php else: ?>
                                                <form action="<?= base_url('admin/users/delete/' . $row['id']) ?>" method="post" class="d-inline" data-confirm-message="Archive this user?" data-confirm-label="Archive" data-confirm-class="btn-danger">
                                                    <button type="submit" class="btn btn-sm btn-danger" title="Archive">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="7" class="text-center py-4">No <?= !empty($show_deleted) ? 'archived' : '' ?> users found.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

<?= view('layouts/page_footer') ?>
