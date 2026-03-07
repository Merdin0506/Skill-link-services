<?= view('layouts/page_header', ['pageTitle' => 'Available Jobs']) ?>

    <!-- Page Content -->
    <div class="page-content">
        <div class="row mb-4">
            <div class="col-12">
                <h3 class="mb-0"><i class="fas fa-briefcase"></i> Available Jobs</h3>
            </div>
        </div>

        <?php if (session()->has('success')): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?= session('success') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (session()->has('error')): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?= session('error') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <i class="fas fa-list"></i> Browse Jobs
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Reference</th>
                                <th>Service</th>
                                <th>Customer</th>
                                <th>Schedule</th>
                                <th>Priority</th>
                                <th>Total Fee</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (!empty($jobs)): ?>
                            <?php foreach ($jobs as $row): ?>
                            <tr onclick="window.location.href='<?= base_url('worker/job/' . ($row['id'] ?? 0)) ?>'" style="cursor: pointer;">
                                <td><strong><?= esc($row['booking_reference'] ?? '-') ?></strong></td>
                                <td><?= esc($row['service_name'] ?? '-') ?></td>
                                <td><?= esc(($row['customer_first_name'] ?? '') . ' ' . ($row['customer_last_name'] ?? '')) ?></td>
                                <td><?= esc(($row['scheduled_date'] ?? '-') . ' ' . ($row['scheduled_time'] ?? '')) ?></td>
                                <td>
                                    <span class="badge bg-<?= ($row['priority'] ?? 'low') === 'urgent' ? 'danger' : (($row['priority'] ?? 'low') === 'high' ? 'warning' : (($row['priority'] ?? 'low') === 'medium' ? 'info' : 'secondary')) ?>">
                                        <?= esc(ucfirst($row['priority'] ?? '-')) ?>
                                    </span>
                                </td>
                                <td><strong>₱<?= number_format((float)($row['total_fee'] ?? 0), 2) ?></strong></td>
                                <td>
                                    <a href="<?= base_url('worker/job/' . ($row['id'] ?? 0)) ?>" class="btn btn-outline-primary btn-sm me-1" onclick="event.stopPropagation();">
                                        <i class="fas fa-eye"></i> Details
                                    </a>
                                    <form action="<?= base_url('worker/accept-job/' . ($row['id'] ?? 0)) ?>" method="POST" style="display:inline;" onsubmit="event.stopPropagation(); return confirm('Accept this job?');">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-success btn-sm">
                                            <i class="fas fa-check"></i> Accept
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="7" class="text-center py-4">No available jobs right now.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

<?= view('layouts/page_footer') ?>
