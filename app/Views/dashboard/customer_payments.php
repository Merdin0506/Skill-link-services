<?= view('layouts/page_header', ['pageTitle' => 'My Payments']) ?>

    <!-- Page Content -->
    <div class="page-content">
        <div class="row mb-4">
            <div class="col-12">
                <h3 class="mb-0"><i class="fas fa-credit-card"></i> My Payments</h3>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <i class="fas fa-receipt"></i> Payment Records
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Payment Reference</th>
                                <th>Booking</th>
                                <th>Service</th>
                                <th>Amount</th>
                                <th>Payment Method</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (!empty($payments)): ?>
                            <?php foreach ($payments as $row): ?>
                                <tr>
                                    <td><code><?= esc($row['payment_reference'] ?? '-') ?></code></td>
                                    <td>
                                        <?= esc($row['booking_title'] ?? $row['booking_reference'] ?? '-') ?>
                                    </td>
                                    <td><?= esc($row['service_name'] ?? '-') ?></td>
                                    <td><strong>₱<?= number_format((float)($row['amount'] ?? 0), 2) ?></strong></td>
                                    <td>
                                        <?php 
                                        $method = $row['payment_method'] ?? 'cash';
                                        $methodIcons = [
                                            'cash' => 'fa-money-bill',
                                            'gcash' => 'fa-mobile-alt',
                                            'card' => 'fa-credit-card',
                                            'bank_transfer' => 'fa-university'
                                        ];
                                        ?>
                                        <i class="fas <?= $methodIcons[$method] ?? 'fa-money-bill' ?>"></i>
                                        <?= esc(ucwords(str_replace('_', ' ', $method))) ?>
                                    </td>
                                    <td>
                                        <?php 
                                        $statusClass = [
                                            'pending' => 'bg-warning',
                                            'completed' => 'bg-success',
                                            'failed' => 'bg-danger',
                                            'refunded' => 'bg-info'
                                        ];
                                        $status = $row['payment_status'] ?? 'pending';
                                        ?>
                                        <span class="badge <?= $statusClass[$status] ?? 'bg-secondary' ?>"><?= esc(ucfirst($status)) ?></span>
                                    </td>
                                    <td><?= !empty($row['created_at']) ? date('M d, Y', strtotime($row['created_at'])) : '-' ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="7" class="text-center py-4">
                                <p class="text-muted mb-0">No payment records found.</p>
                            </td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <?php if (!empty($payments)): ?>
            <div class="card mt-4">
                <div class="card-header">
                    <i class="fas fa-chart-pie"></i> Payment Summary
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-4">
                            <h6 class="text-muted">Total Spent</h6>
                            <h4 class="text-primary">₱<?= number_format(array_sum(array_column($payments, 'amount')), 2) ?></h4>
                        </div>
                        <div class="col-md-4">
                            <h6 class="text-muted">Total Payments</h6>
                            <h4 class="text-info"><?= count($payments) ?></h4>
                        </div>
                        <div class="col-md-4">
                            <h6 class="text-muted">Completed</h6>
                            <h4 class="text-success">
                                <?= count(array_filter($payments, fn($p) => ($p['payment_status'] ?? '') === 'completed')) ?>
                            </h4>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

<?= view('layouts/page_footer') ?>
