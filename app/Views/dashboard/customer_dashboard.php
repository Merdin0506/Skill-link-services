<!-- Customer Dashboard -->
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
                <i class="fas fa-peso-sign stat-icon" style="color: var(--success-color);"></i>
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
    window.addEventListener('load', function () {
        if (typeof Chart === 'undefined') {
            console.error('Chart.js is not loaded');
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

        const spendingTrendLabels = <?= json_encode(array_keys($analytics['spending_trend'] ?? [])) ?>;
        const spendingTrendData = <?= json_encode(array_values($analytics['spending_trend'] ?? [])) ?>;

        // Spending Trend Chart
        const spendingCtx = document.getElementById('spendingTrendChart')?.getContext('2d');
        if (spendingCtx && hasAnyData(spendingTrendData)) {
            new Chart(spendingCtx, {
                type: 'line',
                data: {
                    labels: spendingTrendLabels,
                    datasets: [{
                        label: 'Daily Spending',
                        data: spendingTrendData,
                        backgroundColor: 'rgba(246, 194, 62, 0.1)',
                        borderColor: '#f6c23e',
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
            showEmptyState('spendingTrendChart', 'fa-chart-line', 'No spending data yet.');
        }

        // Service Preferences Chart
        const serviceCtx = document.getElementById('servicePreferencesChart')?.getContext('2d');
        if (serviceCtx) {
            const serviceData = <?= json_encode($analytics['service_preferences'] ?? []) ?>;
            
            if (serviceData && serviceData.length > 0) {
                new Chart(serviceCtx, {
                    type: 'doughnut',
                    data: {
                        labels: serviceData.map(item => item.service_name || 'Unknown Service'),
                        datasets: [{
                            data: serviceData.map(item => item.count || 0),
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
            } else {
                // Show "No data" message
                serviceCtx.canvas.parentElement.innerHTML = '<div class="text-center text-muted py-5"><i class="fas fa-chart-pie fa-3x mb-3 opacity-25"></i><p>No service usage data yet.<br>Book a service to see your preferences.</p></div>';
            }
        }
    });
</script>

