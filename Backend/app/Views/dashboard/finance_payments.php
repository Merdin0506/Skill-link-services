<?= view('layouts/page_header', ['pageTitle' => 'Customer Payments']) ?>

    <!-- Page Content -->
    <div class="page-content">
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <h3 class="mb-0"><i class="fas fa-money-bill-wave"></i> Customer Payments</h3>
                </div>
            </div>
        </div>

        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> <strong>How It Works:</strong> 
            Workers collect payments from customers on-site and record them automatically. 
            Payments highlighted in <span class="badge bg-warning">yellow</span> need manual recording (exceptions only).
        </div>

        <div class="card">
            <div class="card-header">
                <i class="fas fa-list"></i> All Customer Payments
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-fit table-payments mb-0">
                        <thead>
            <tr>
                                <th>Payment Ref</th>
                                <th>Booking</th>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th>Method</th>
                                <th>Status</th>
                                <th>Recorded By</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (!empty($payments)): ?>
                            <?php foreach ($payments as $row): ?>
                                <tr <?= ($row['is_unrecorded'] ?? false) ? 'style="background-color: #fff3cd;"' : '' ?>>
                                    <td>
                                        <?php if ($row['is_unrecorded'] ?? false): ?>
                                            <span class="badge bg-warning"><i class="fas fa-exclamation-triangle"></i> PENDING</span>
                                        <?php else: ?>
                                            <strong><?= esc($row['payment_reference'] ?? '-') ?></strong>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= esc($row['booking_reference'] ?? '-') ?></td>
                                    <td><?= esc(($row['customer_first_name'] ?? '') . ' ' . ($row['customer_last_name'] ?? '')) ?></td>
                                    <td><strong>₱<?= number_format((float)($row['amount'] ?? $row['total_fee'] ?? 0), 2) ?></strong></td>
                                    <td>
                                        <?php if ($row['is_unrecorded'] ?? false): ?>
                                            <span class="badge bg-secondary">Not Recorded</span>
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
                                            'refunded' => 'secondary',
                                            default => 'secondary'
                                        };
                                        ?>
                                        <span class="badge bg-<?= $badgeClass ?>"><?= esc(ucfirst($status)) ?></span>
                                    </td>
                                    <td>
                                        <?php if ($row['is_unrecorded'] ?? false): ?>
                                            <span class="text-muted">-</span>
                                        <?php else: ?>
                                            <?php 
                                            $recorderName = ($row['processor_first_name'] ?? '') . ' ' . ($row['processor_last_name'] ?? '');
                                            $recorderType = $row['processor_type'] ?? '';
                                            ?>
                                            <?= esc($recorderName) ?>
                                            <?php if ($recorderType === 'worker'): ?>
                                                <span class="badge bg-success" title="Collected on-site"><i class="fas fa-user-check"></i> Worker</span>
                                            <?php elseif ($recorderType === 'finance'): ?>
                                                <span class="badge bg-primary" title="Manual entry"><i class="fas fa-edit"></i> Finance</span>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= date('M d, Y H:i', strtotime($row['payment_date'] ?? $row['created_at'] ?? 'now')) ?></td>
                                    <td>
                                        <?php if ($row['is_unrecorded'] ?? false): ?>
                                            <a href="<?= base_url('finance/payments/record/' . $row['id']) ?>" class="btn btn-sm btn-primary" title="Manually record this payment">
                                                <i class="fas fa-plus"></i> Record
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="9" class="text-center py-4">No payments found.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

<?= view('layouts/page_footer') ?>
