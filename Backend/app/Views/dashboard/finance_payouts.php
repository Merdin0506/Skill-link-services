<?= view('layouts/page_header', ['pageTitle' => 'Worker Payouts']) ?>

    <!-- Page Content -->
    <div class="page-content">
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <h3 class="mb-0"><i class="fas fa-hand-holding-usd"></i> Worker Payouts</h3>
                </div>
            </div>
        </div>

        <div class="alert alert-warning">
            <i class="fas fa-info-circle"></i> <strong>Finance Action Required:</strong> 
            Workers complete jobs and collect payments from customers. You need to process their earnings payouts.
            Payouts highlighted in <span class="badge bg-warning">yellow</span> are pending - click "Record" to process payment to the worker.
        </div>

        <div class="card">
            <div class="card-header">
                <i class="fas fa-list"></i> All Worker Payouts
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-payouts mb-0">
                        <thead>
                            <tr>
                                <th>Payment Ref</th>
                                <th>Booking</th>
                                <th>Worker</th>
                                <th>Earnings</th>
                                <th>Method</th>
                                <th>Status</th>
                                <th>Processed By</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (!empty($payouts)): ?>
                            <?php foreach ($payouts as $row): ?>
                                <tr <?= ($row['is_unrecorded'] ?? false) ? 'style="background-color: #fff3cd;"' : '' ?>>
                                    <td>
                                        <?php if ($row['is_unrecorded'] ?? false): ?>
                                            <span class="badge bg-warning"><i class="fas fa-clock"></i> PENDING</span>
                                        <?php else: ?>
                                            <strong><?= esc($row['payment_reference'] ?? '-') ?></strong>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= esc($row['booking_reference'] ?? '-') ?></td>
                                    <td><?= esc(($row['worker_first_name'] ?? '') . ' ' . ($row['worker_last_name'] ?? '')) ?></td>
                                    <td><strong>₱<?= number_format((float)($row['worker_earnings'] ?? $row['amount'] ?? 0), 2) ?></strong></td>
                                    <td>
                                        <?php if ($row['is_unrecorded'] ?? false): ?>
                                            <span class="badge bg-secondary">Not Paid Yet</span>
                                        <?php else: ?>
                                            <span class="badge bg-info"><?= esc(ucfirst($row['payment_method'] ?? '-')) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php 
                                        $status = $row['status'] ?? '';
                                        $badgeClass = match($status) {
                                            'completed' => 'success',
                                            'pending' => 'warning',
                                            'processing' => 'info',
                                            'failed' => 'danger',
                                            default => 'secondary'
                                        };
                                        ?>
                                        <span class="badge bg-<?= $badgeClass ?>"><?= esc(ucfirst($status)) ?></span>
                                    </td>
                                    <td>
                                        <?php if ($row['is_unrecorded'] ?? false): ?>
                                            <span class="text-muted">-</span>
                                        <?php else: ?>
                                            <?= esc(($row['processor_first_name'] ?? '') . ' ' . ($row['processor_last_name'] ?? '')) ?>
                                            <span class="badge bg-primary" title="Processed by Finance"><i class="fas fa-user-tie"></i></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= date('M d, Y H:i', strtotime($row['payment_date'] ?? $row['created_at'] ?? 'now')) ?></td>
                                    <td>
                                        <?php if ($row['is_unrecorded'] ?? false): ?>
                                            <a href="<?= base_url('finance/payouts/record/' . $row['id']) ?>" class="btn btn-sm btn-success" title="Pay worker earnings">
                                                <i class="fas fa-money-bill-wave"></i> Pay Out
                                            </a>
                                        <?php else: ?>
                                            <span class="badge bg-success"><i class="fas fa-check"></i> Paid</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="9" class="text-center py-4">No payouts found.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

<?= view('layouts/page_footer') ?>
