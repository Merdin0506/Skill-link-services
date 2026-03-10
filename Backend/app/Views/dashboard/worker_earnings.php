<?= view('layouts/page_header', ['pageTitle' => 'Earnings']) ?>

    <!-- Page Content -->
    <div class="page-content">
        <div class="row mb-4">
            <div class="col-12">
                <h3 class="mb-0"><i class="fas fa-wallet"></i> Earnings</h3>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-chart-line"></i> Total Earnings
            </div>
            <div class="card-body">
                <h5 class="mb-1">Total Completed Earnings</h5>
                <h2 class="mb-0 text-success">₱<?= number_format((float)($totalEarnings ?? 0), 2) ?></h2>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <i class="fas fa-history"></i> Recent Payouts
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Reference</th>
                                <th>Status</th>
                                <th>Method</th>
                                <th>Amount</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (!empty($recentPayouts)): ?>
                            <?php foreach ($recentPayouts as $row): ?>
                            <tr>
                                <td><?= esc($row['payment_reference'] ?? '-') ?></td>
                                <td><span class="badge bg-secondary"><?= esc($row['status'] ?? '-') ?></span></td>
                                <td><?= esc($row['payment_method'] ?? '-') ?></td>
                                <td>₱<?= number_format((float)($row['amount'] ?? 0), 2) ?></td>
                                <td><?= esc($row['payment_date'] ?? $row['created_at'] ?? '-') ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="text-center py-4">No payout records yet.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

<?= view('layouts/page_footer') ?>
