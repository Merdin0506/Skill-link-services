<?= view('layouts/page_header', ['pageTitle' => 'Payments']) ?>

    <!-- Page Content -->
    <div class="page-content">
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <h3 class="mb-0"><i class="fas fa-credit-card"></i> Payments Management</h3>

                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <i class="fas fa-list"></i> All Payments
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-fit table-payments mb-0">
                        <thead>
                            <tr>
                                <th>Reference</th>
                                <th>Booking</th>
                                <th>Method</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Amount</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (!empty($payments)): ?>
                            <?php foreach ($payments as $row): ?>
                                <tr>
                                    <td><?= esc($row['payment_reference'] ?? '-') ?></td>
                                    <td><?= esc($row['booking_reference'] ?? '-') ?></td>
                                    <td><?= esc($row['payment_method'] ?? '-') ?></td>
                                    <td><?= esc($row['payment_type'] ?? '-') ?></td>
                                    <td><span class="badge bg-secondary"><?= esc($row['status'] ?? '-') ?></span></td>
                                    <td>₱<?= number_format((float)($row['amount'] ?? 0), 2) ?></td>
                                    <td><?= esc($row['payment_date'] ?? $row['created_at'] ?? '-') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="7" class="text-center py-4">No payments found.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

<?= view('layouts/page_footer') ?>
