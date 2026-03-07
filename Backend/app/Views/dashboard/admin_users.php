<?= view('layouts/page_header', ['pageTitle' => 'Users']) ?>

    <!-- Page Content -->
    <div class="page-content">
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <h3 class="mb-0"><i class="fas fa-users"></i> Users Management</h3>
                    <a href="<?= base_url('admin/users/create') ?>" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add New User
                    </a>
                </div>
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

        <div class="card">
            <div class="card-header">
                <i class="fas fa-list"></i> All Users
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
                                        <a href="<?= base_url('admin/users/edit/' . $row['id']) ?>" class="btn btn-sm btn-warning" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php if ($isSuperAdmin): ?>
                                            <button type="button" class="btn btn-sm btn-secondary" title="Super admin cannot be deleted" disabled>
                                                <i class="fas fa-lock"></i>
                                            </button>
                                        <?php else: ?>
                                            <form action="<?= base_url('admin/users/delete/' . $row['id']) ?>" method="post" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this user?')">
                                                <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="7" class="text-center py-4">No users found.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

<?= view('layouts/page_footer') ?>
