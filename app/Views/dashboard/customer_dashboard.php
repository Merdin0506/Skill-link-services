<!-- Customer/Owner Dashboard -->
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="stat-card">
                <i class="fas fa-calendar-check stat-icon" style="color: var(--primary-color);"></i>
                <div class="stat-value"><?= formatNumber($stats['active_bookings'] ?? 0) ?></div>
                <div class="stat-label">Active Bookings</div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="stat-card warning">
                <i class="fas fa-hourglass-half stat-icon" style="color: var(--warning-color);"></i>
                <div class="stat-value"><?= formatNumber($stats['pending_bookings'] ?? 0) ?></div>
                <div class="stat-label">Pending Bookings</div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="stat-card success">
                <i class="fas fa-check-circle stat-icon" style="color: var(--success-color);"></i>
                <div class="stat-value"><?= formatNumber($stats['completed_bookings'] ?? 0) ?></div>
                <div class="stat-label">Completed Bookings</div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="stat-card info">
                <i class="fas fa-book stat-icon" style="color: var(--info-color);"></i>
                <div class="stat-value"><?= formatNumber($stats['total_bookings'] ?? 0) ?></div>
                <div class="stat-label">Total Bookings</div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="stat-card success">
                <i class="fas fa-dollar-sign stat-icon" style="color: var(--success-color);"></i>
                <div class="stat-value"><?= formatCurrency($stats['total_spent'] ?? 0) ?></div>
                <div class="stat-label">Total Spent</div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="stat-card">
                <i class="fas fa-star stat-icon" style="color: #ffc107;"></i>
                <div class="stat-value"><?= round($stats['average_rating_given'] ?? 0, 1) ?>/5</div>
                <div class="stat-label">Avg. Rating Given</div>
            </div>
        </div>
    </div>

    <!-- Analytics -->
    <div class="row mb-4">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-chart-line"></i> Spending Trend (Last 30 Days)
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="spendingTrendChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-chart-pie"></i> Service Preferences
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="servicePreferencesChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Bookings -->
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-list"></i> Recent Bookings
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Reference</th>
                                    <th>Title</th>
                                    <th>Status</th>
                                    <th>Scheduled Date</th>
                                    <th>Amount</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentBookings ?? [] as $booking): ?>
                                <tr>
                                    <td><strong><?= $booking['booking_reference'] ?? 'N/A' ?></strong></td>
                                    <td><?= $booking['title'] ?? 'N/A' ?></td>
                                    <td>
                                        <span class="badge badge-<?= $booking['status'] ?? 'pending' ?>">
                                            <?= ucfirst($booking['status'] ?? 'N/A') ?>
                                        </span>
                                    </td>
                                    <td><?= date('M d, Y', strtotime($booking['scheduled_date'] ?? date('Y-m-d'))) ?></td>
                                    <td><?= formatCurrency($booking['total_fee'] ?? 0) ?></td>
                                    <td>
                                        <a href="<?= base_url('customer/booking/' . ($booking['id'] ?? '')) ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($recentBookings)): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        <i class="fas fa-inbox fa-2x mb-2"></i>
                                        <p>No bookings yet. <a href="<?= base_url('customer/services') ?>">Create your first booking</a></p>
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
</div>

<script>
    // Spending Trend Chart
    const spendingCtx = document.getElementById('spendingTrendChart')?.getContext('2d');
    if (spendingCtx) {
        new Chart(spendingCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode(array_keys($analytics['spending_trend'] ?? [])) ?>,
                datasets: [{
                    label: 'Daily Spending',
                    data: <?= json_encode(array_values($analytics['spending_trend'] ?? [])) ?>,
                    borderColor: '#f6c23e',
                    backgroundColor: 'rgba(246, 194, 62, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
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

    // Service Preferences Chart
    const serviceCtx = document.getElementById('servicePreferencesChart')?.getContext('2d');
    if (serviceCtx) {
        new Chart(serviceCtx, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode(array_map(fn($item) => 'Service ' . ($item['service_id'] ?? 'N/A'), $analytics['service_preferences'] ?? [])) ?>,
                datasets: [{
                    data: <?= json_encode(array_map(fn($item) => $item['count'] ?? 0, $analytics['service_preferences'] ?? [])) ?>,
                    backgroundColor: [
                        '#4e73df',
                        '#1cc88a',
                        '#36b9cc',
                        '#f6c23e',
                        '#e74c3c'
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
