<?= view('layouts/page_header', ['pageTitle' => 'Available Jobs']) ?>

    <!-- Page Content -->
    <div class="page-content">
        <div class="row mb-4">
            <div class="col-12">
                <h3 class="mb-0"><i class="fas fa-briefcase"></i> Available Jobs</h3>
            </div>
        </div>

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
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (!empty($jobs)): ?>
                            <?php foreach ($jobs as $row): ?>
                            <tr>
                                <td><?= esc($row['booking_reference'] ?? '-') ?></td>
                                <td><?= esc($row['service_name'] ?? '-') ?></td>
                                <td><?= esc(($row['customer_first_name'] ?? '') . ' ' . ($row['customer_last_name'] ?? '')) ?></td>
                                <td><?= esc(($row['scheduled_date'] ?? '-') . ' ' . ($row['scheduled_time'] ?? '')) ?></td>
                                <td><span class="badge bg-warning text-dark"><?= esc($row['priority'] ?? '-') ?></span></td>
                                <td>₱<?= number_format((float)($row['total_fee'] ?? 0), 2) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="6" class="text-center py-4">No available jobs right now.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

<?= view('layouts/page_footer') ?>
