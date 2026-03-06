<?= view('layouts/page_header', ['pageTitle' => 'Service Records']) ?>

    <!-- Page Content -->
    <div class="page-content">
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <h3 class="mb-0">
                        <i class="fas fa-file-invoice"></i> Service Records 
                        <?= !empty($filters['show_deleted']) ? '<span class="badge bg-secondary">Archived</span>' : '' ?>
                    </h3>
                    <div>
                        <a href="<?= base_url('admin/records/create') ?>" class="btn btn-success btn-sm">
                            <i class="fas fa-plus"></i> New Record
                        </a>
                        <?php if (!empty($filters['show_deleted'])): ?>
                            <a href="<?= base_url('admin/records') ?>" class="btn btn-outline-primary btn-sm">Active Records</a>
                        <?php else: ?>
                            <a href="<?= base_url('admin/records?show_deleted=1') ?>" class="btn btn-outline-secondary btn-sm">View Archived</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <?php if (session()->has('success')): ?>
            <div class="alert alert-success alert-dismissible fade show"><i class="fas fa-check-circle"></i> <?= esc(session('success')) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if (session()->has('error')): ?>
            <div class="alert alert-danger alert-dismissible fade show"><i class="fas fa-exclamation-circle"></i> <?= esc(session('error')) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Search / Filter Bar -->
        <div class="card mb-4">
            <div class="card-body py-2">
                <form method="get" action="<?= base_url('admin/records') ?>" class="row g-2 align-items-end">
                    <?php if (!empty($filters['show_deleted'])): ?>
                        <input type="hidden" name="show_deleted" value="1">
                    <?php endif; ?>
                    <div class="col-md-3">
                        <input type="text" name="q" class="form-control form-control-sm" placeholder="Search name, address, ref..."
                               value="<?= esc($filters['q'] ?? '') ?>">
                    </div>
                    <div class="col-md-2">
                        <select name="status" class="form-select form-select-sm">
                            <option value="">All Status</option>
                            <?php foreach (['pending','scheduled','in_progress','completed','cancelled'] as $st): ?>
                                <option value="<?= $st ?>" <?= ($filters['status'] ?? '') === $st ? 'selected' : '' ?>><?= ucwords(str_replace('_',' ',$st)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="payment_status" class="form-select form-select-sm">
                            <option value="">All Payment</option>
                            <?php foreach (['unpaid','partial','paid','refunded'] as $ps): ?>
                                <option value="<?= $ps ?>" <?= ($filters['payment_status'] ?? '') === $ps ? 'selected' : '' ?>><?= ucfirst($ps) ?></option>
                            <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary btn-sm w-100">Filter</button>
                </div>
                <div class="col-md-2">
                    <a href="<?= base_url('admin/records') . (!empty($filters['show_deleted']) ? '?show_deleted=1' : '') ?>" class="btn btn-outline-secondary btn-sm w-100">Clear</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Customer</th>
                            <th>Provider</th>
                            <th>Service</th>
                            <th>Status</th>
                            <th>Payment</th>
                            <th>Total</th>
                            <th>Scheduled</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (!empty($records)): ?>
                        <?php foreach ($records as $row): ?>
                            <tr>
                                <td><?= esc($row['id']) ?></td>
                                <td><?= esc(($row['customer_first_name'] ?? '') . ' ' . ($row['customer_last_name'] ?? '')) ?></td>
                                <td><?= esc(trim(($row['provider_first_name'] ?? '') . ' ' . ($row['provider_last_name'] ?? '')) ?: '-') ?></td>
                                <td><?= esc($row['service_name'] ?? '-') ?></td>
                                <td>
                                    <?php
                                        $statusColors = ['pending'=>'warning','scheduled'=>'info','in_progress'=>'primary','completed'=>'success','cancelled'=>'danger'];
                                        $color = $statusColors[$row['status'] ?? ''] ?? 'secondary';
                                    ?>
                                    <span class="badge bg-<?= $color ?>"><?= esc(ucwords(str_replace('_', ' ', $row['status'] ?? '-'))) ?></span>
                                </td>
                                <td><span class="badge bg-<?= ($row['payment_status'] ?? '') === 'paid' ? 'success' : (($row['payment_status'] ?? '') === 'refunded' ? 'danger' : 'warning') ?>"><?= esc($row['payment_status'] ?? '-') ?></span></td>
                                <td>₱<?= number_format((float)($row['total_amount'] ?? 0), 2) ?></td>
                                <td><?= esc($row['scheduled_at'] ?? '-') ?></td>
                                <td>
                                    <?php if (!empty($filters['show_deleted'])): ?>
                                        <form method="post" action="<?= base_url('admin/records/restore/' . $row['id']) ?>" class="d-inline" onsubmit="return confirm('Restore this record?')">
                                            <button class="btn btn-outline-success btn-sm">Restore</button>
                                        </form>
                                    <?php else: ?>
                                        <a href="<?= base_url('admin/records/edit/' . $row['id']) ?>" class="btn btn-outline-primary btn-sm">Edit</a>
                                        <form method="post" action="<?= base_url('admin/records/delete/' . $row['id']) ?>" class="d-inline" onsubmit="return confirm('Are you sure you want to archive this record?')">
                                            <button class="btn btn-outline-danger btn-sm">Delete</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="9" class="text-center py-4">No service records found.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<?= view('layouts/page_footer') ?>
