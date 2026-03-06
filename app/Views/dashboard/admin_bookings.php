<?= view('layouts/page_header', ['pageTitle' => 'Bookings']) ?>

    <!-- Page Content -->
    <div class="page-content">
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <h3 class="mb-0"><i class="fas fa-calendar-check"></i> Bookings Management</h3>

                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <i class="fas fa-list"></i> All Bookings
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Reference</th>
                                <th>Title</th>
                                <th>Customer</th>
                                <th>Worker</th>
                                <th>Service</th>
                                <th>Status</th>
                                <th>Total Fee</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (!empty($bookings)): ?>
                            <?php foreach ($bookings as $row): ?>
                                <tr>
                                    <td><?= esc($row['booking_reference'] ?? '-') ?></td>
                                    <td><?= esc($row['title'] ?? '-') ?></td>
                                    <td><?= esc(($row['customer_first_name'] ?? '') . ' ' . ($row['customer_last_name'] ?? '')) ?></td>
                                    <td><?= esc(trim(($row['worker_first_name'] ?? '') . ' ' . ($row['worker_last_name'] ?? '')) ?: '-') ?></td>
                                    <td><?= esc($row['service_name'] ?? '-') ?></td>
                                    <td><span class="badge bg-info text-dark"><?= esc($row['status'] ?? '-') ?></span></td>
                                    <td>₱<?= number_format((float)($row['total_fee'] ?? 0), 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="7" class="text-center py-4">No bookings found.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

<?= view('layouts/page_footer') ?>
