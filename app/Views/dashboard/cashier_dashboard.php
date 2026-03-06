<!-- Cashier Dashboard -->
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="stat-card success">
                <i class="fas fa-dollar-sign stat-icon" style="color: var(--success-color);"></i>
                <div class="stat-value"><?= formatCurrency($stats['total_collected'] ?? 0) ?></div>
                <div class="stat-label">Total Collected</div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="stat-card info">
                <i class="fas fa-check-circle stat-icon" style="color: var(--info-color);"></i>
                <div class="stat-value"><?= formatNumber($stats['completed_payments'] ?? 0) ?></div>
                <div class="stat-label">Completed Payments</div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="stat-card warning">
                <i class="fas fa-hourglass-half stat-icon" style="color: var(--warning-color);"></i>
                <div class="stat-value"><?= formatNumber($stats['pending_payments'] ?? 0) ?></div>
                <div class="stat-label">Pending Payments</div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="stat-card success">
                <i class="fas fa-calendar-day stat-icon" style="color: var(--success-color);"></i>
                <div class="stat-value"><?= formatCurrency($stats['today_collections'] ?? 0) ?></div>
                <div class="stat-label">Today's Collection</div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row mb-4">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-lightning-bolt"></i> Quick Actions
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 mb-2">
                            <a href="<?= base_url('cashier/payments') ?>" class="btn btn-primary w-100">
                                <i class="fas fa-credit-card"></i> Manage Payments
                            </a>
                        </div>
                        <div class="col-md-3 mb-2">
                            <a href="<?= base_url('cashier/reports') ?>" class="btn btn-info w-100">
                                <i class="fas fa-file-alt"></i> Reports
                            </a>
                        </div>
                        <div class="col-md-3 mb-2">
                            <a href="<?= base_url('cashier/reconciliation') ?>" class="btn btn-warning w-100">
                                <i class="fas fa-calculator"></i> Reconciliation
                            </a>
                        </div>
                        <div class="col-md-3 mb-2">
                            <a href="<?= base_url('cashier/print-receipt') ?>" class="btn btn-secondary w-100">
                                <i class="fas fa-print"></i> Print Receipt
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Analytics -->
    <div class="row mb-4">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-chart-line"></i> Daily Collections (Last 7 Days)
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
                    <i class="fas fa-list"></i> Recent Payments
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Payment ID</th>
                                    <th>Booking Reference</th>
                                    <th>Amount</th>
                                    <th>Payment Method</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentBookings ?? [] as $booking): ?>
                                <tr>
                                    <td><strong>#<?= substr(md5($booking['id'] ?? ''), 0, 8) ?></strong></td>
                                    <td><?= $booking['booking_reference'] ?? 'N/A' ?></td>
                                    <td><?= formatCurrency($booking['total_fee'] ?? 0) ?></td>
                                    <td><span class="badge bg-light text-dark">Cash/Card</span></td>
                                    <td>
                                        <span class="badge" style="background-color: #d1e7dd; color: #0f5132;">
                                            Completed
                                        </span>
                                    </td>
                                    <td><?= date('M d, Y H:i', strtotime($booking['created_at'] ?? date('Y-m-d H:i:s'))) ?></td>
                                    <td>
                                        <a href="<?= base_url('cashier/receipt/' . ($booking['id'] ?? '')) ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-download"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($recentBookings)): ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">
                                        No payments recorded yet
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

    <!-- Daily Summary -->
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-receipt"></i> Daily Summary
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-4 mb-3">
                            <h6 class="text-muted">Total Transactions</h6>
                            <h3><?= formatNumber($stats['total_payments'] ?? 0) ?></h3>
                        </div>
                        <div class="col-md-4 mb-3">
                            <h6 class="text-muted">Average Payment</h6>
                            <h3><?= formatCurrency(($stats['total_collected'] ?? 0) / max(($stats['completed_payments'] ?? 1), 1)) ?></h3>
                        </div>
                        <div class="col-md-4 mb-3">
                            <h6 class="text-muted">Success Rate</h6>
                            <h3><?= round(($stats['completed_payments'] ?? 0) / max(($stats['total_payments'] ?? 1), 1) * 100, 2) ?>%</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Daily Collections Chart
    const collectionsCtx = document.getElementById('dailyCollectionsChart')?.getContext('2d');
    if (collectionsCtx) {
        new Chart(collectionsCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_keys($analytics['daily_collections'] ?? [])) ?>,
                datasets: [{
                    label: 'Daily Collections',
                    data: <?= json_encode(array_values($analytics['daily_collections'] ?? [])) ?>,
                    backgroundColor: '#1cc88a'
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
                                return '$' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
    }

    // Payment Methods Chart
    const methodsCtx = document.getElementById('paymentMethodsChart')?.getContext('2d');
    if (methodsCtx) {
        new Chart(methodsCtx, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode(array_map(fn($item) => ucfirst($item['payment_method'] ?? 'Unknown'), $analytics['payment_methods'] ?? [])) ?>,
                datasets: [{
                    data: <?= json_encode(array_map(fn($item) => $item['count'] ?? 0, $analytics['payment_methods'] ?? [])) ?>,
                    backgroundColor: [
                        '#4e73df',
                        '#1cc88a',
                        '#36b9cc',
                        '#f6c23e'
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
    }
</script>
