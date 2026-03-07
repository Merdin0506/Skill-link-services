<!-- Finance Dashboard -->
<div class="container-fluid">
    <div class="alert alert-info mb-4">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h5><i class="fas fa-info-circle"></i> Finance Dashboard Overview</h5>
                <p class="mb-0"><strong>Payment Flow:</strong> Workers collect payments from customers on-site -> You process worker payouts</p>
            </div>
            <div class="col-md-4 text-end">
                <a href="<?= base_url('finance/payments') ?>" class="btn btn-sm btn-primary me-2"><i class="fas fa-eye"></i> View Payments</a>
                <a href="<?= base_url('finance/payouts') ?>" class="btn btn-sm btn-success"><i class="fas fa-hand-holding-usd"></i> Process Payouts</a>
            </div>
        </div>
    </div>
    
    <div class="row mb-4">
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="stat-card success">
                <i class="fas fa-peso-sign stat-icon" style="color: var(--success-color);"></i>
                <div class="stat-value"><?= formatCurrency($stats['total_collected'] ?? 0) ?></div>
                <div class="stat-label">Total Revenue</div>
                <small class="text-muted">All completed bookings</small>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="stat-card info">
                <i class="fas fa-check-circle stat-icon" style="color: var(--info-color);"></i>
                <div class="stat-value"><?= formatNumber($stats['completed_payments'] ?? 0) ?></div>
                <div class="stat-label">Recorded Payments</div>
                <small class="text-muted">Worker-collected</small>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="stat-card warning">
                <i class="fas fa-hourglass-half stat-icon" style="color: var(--warning-color);"></i>
                <div class="stat-value"><?= formatNumber($stats['pending_payments'] ?? 0) ?></div>
                <div class="stat-label">Pending Payments</div>
                <small class="text-muted">Needs recording</small>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="stat-card danger">
                <i class="fas fa-hand-holding-usd stat-icon" style="color: var(--danger-color);"></i>
                <div class="stat-value"><?= formatCurrency($stats['pending_payouts'] ?? 0) ?></div>
                <div class="stat-label">Pending Worker Payouts</div>
                <small class="text-danger"><strong>Warning: Action Required</strong></small>
            </div>
        </div>
    </div>

    <!-- Payment Analytics -->
    <div class="row mb-4">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-chart-line"></i> Revenue Trend (Last 7 Days)
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="dailyCollectionsChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-chart-pie"></i> Payment Methods
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="paymentMethodsChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Payments -->
    <div class="row mb-4">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-list"></i> Recent Transactions
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Transaction ID</th>
                                    <th>Booking Reference</th>
                                    <th>Amount</th>
                                    <th>Payment Method</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentBookings ?? [] as $transaction): ?>
                                <tr>
                                    <td><strong>#<?= $transaction['payment_reference'] ?? ($transaction['booking_reference'] ?? 'N/A') ?></strong></td>
                                    <td><?= $transaction['booking_reference'] ?? 'N/A' ?></td>
                                    <td><?= formatCurrency($transaction['amount'] ?? ($transaction['total_fee'] ?? 0)) ?></td>
                                    <td>
                                        <span class="badge bg-light text-dark">
                                            <?= ucfirst(str_replace('_', ' ', $transaction['payment_method'] ?? 'Unpaid')) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                        $statusColors = [
                                            'completed' => 'background-color: #d1e7dd; color: #0f5132;',
                                            'pending' => 'background-color: #fff3cd; color: #856404;',
                                            'processing' => 'background-color: #cfe2ff; color: #084298;',
                                            'failed' => 'background-color: #f8d7da; color: #842029;',
                                            'refunded' => 'background-color: #e2e3e5; color: #41464b;'
                                        ];
                                        $status = $transaction['payment_status'] ?? (($transaction['booking_status'] ?? '') === 'completed' ? 'pending' : ($transaction['booking_status'] ?? 'pending'));
                                        $style = $statusColors[$status] ?? 'background-color: #e2e3e5; color: #41464b;';
                                        ?>
                                        <span class="badge" style="<?= $style ?>">
                                            <?= ucfirst($status) ?>
                                        </span>
                                    </td>
                                    <td><?= date('M d, Y H:i', strtotime($transaction['transaction_created_at'] ?? date('Y-m-d H:i:s'))) ?></td>
                                    <td>
                                        <a href="<?= base_url('bookings/view/' . ($transaction['booking_id'] ?? '')) ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($recentBookings)): ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">
                                        No transactions recorded yet
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Financial Summary -->
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-receipt"></i> Financial Summary
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-4 mb-3">
                            <h6 class="text-muted">Total Transactions</h6>
                            <h3><?= formatNumber($stats['total_payments'] ?? 0) ?></h3>
                        </div>
                        <div class="col-md-4 mb-3">
                            <h6 class="text-muted">Average Transaction Value</h6>
                            <h3><?= formatCurrency(($stats['total_collected'] ?? 0) / max(($stats['completed_payments'] ?? 1), 1)) ?></h3>
                        </div>
                        <div class="col-md-4 mb-3">
                            <h6 class="text-muted">Payment Success Rate</h6>
                            <h3><?= round(($stats['completed_payments'] ?? 0) / max(($stats['total_payments'] ?? 1), 1) * 100, 2) ?>%</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    window.addEventListener('load', function () {
        if (typeof Chart === 'undefined') {
            return;
        }

        const showEmptyState = function (canvasId, icon, message) {
            const canvas = document.getElementById(canvasId);
            if (!canvas || !canvas.parentElement) {
                return;
            }
            canvas.parentElement.innerHTML = '<div class="text-center text-muted py-5"><i class="fas ' + icon + ' fa-2x mb-2 opacity-50"></i><p class="mb-0">' + message + '</p></div>';
        };

        const hasAnyData = function (values) {
            return Array.isArray(values) && values.some(function (value) {
                return Number(value) > 0;
            });
        };

        const dailyCollectionsLabels = <?= json_encode(array_keys($analytics['daily_collections'] ?? [])) ?>;
        const dailyCollectionsData = <?= json_encode(array_values($analytics['daily_collections'] ?? [])) ?>;
        const paymentMethodLabels = <?= json_encode(array_map(fn($item) => ucfirst($item['payment_method'] ?? 'Unknown'), $analytics['payment_methods'] ?? [])) ?>;
        const paymentMethodData = <?= json_encode(array_map(fn($item) => $item['count'] ?? 0, $analytics['payment_methods'] ?? [])) ?>;

        // Daily Collections Chart
        const collectionsCtx = document.getElementById('dailyCollectionsChart')?.getContext('2d');
        if (collectionsCtx && hasAnyData(dailyCollectionsData)) {
            new Chart(collectionsCtx, {
            type: 'line',
            data: {
                labels: dailyCollectionsLabels,
                datasets: [{
                    label: 'Revenue',
                    data: dailyCollectionsData,
                    backgroundColor: 'rgba(28, 200, 138, 0.1)',
                    borderColor: '#1cc88a',
                    borderWidth: 2,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'PHP ' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
            });
        } else {
            showEmptyState('dailyCollectionsChart', 'fa-chart-line', 'No revenue data yet.');
        }

        // Payment Methods Chart
        const methodsCtx = document.getElementById('paymentMethodsChart')?.getContext('2d');
        if (methodsCtx && hasAnyData(paymentMethodData)) {
            new Chart(methodsCtx, {
            type: 'doughnut',
            data: {
                labels: paymentMethodLabels,
                datasets: [{
                    data: paymentMethodData,
                    backgroundColor: [
                        '#4e73df',
                        '#1cc88a',
                        '#36b9cc',
                        '#f6c23e',
                        '#e74a3b'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
            });
        } else {
            showEmptyState('paymentMethodsChart', 'fa-chart-pie', 'No payment method data yet.');
        }
    });
</script>

