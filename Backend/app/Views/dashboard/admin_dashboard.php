<!-- Admin Dashboard -->
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="stat-card">
                <i class="fas fa-users stat-icon" style="color: var(--primary-color);"></i>
                <div class="stat-value"><?= formatNumber($stats['total_users'] ?? 0) ?></div>
                <div class="stat-label">Total Users</div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="stat-card success">
                <i class="fas fa-calendar-check stat-icon" style="color: var(--success-color);"></i>
                <div class="stat-value"><?= formatNumber($stats['total_bookings'] ?? 0) ?></div>
                <div class="stat-label">Total Bookings</div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="stat-card warning">
                <i class="fas fa-dollar-sign stat-icon" style="color: var(--warning-color);"></i>
                <div class="stat-value"><?= formatCurrency($stats['total_revenue'] ?? 0) ?></div>
                <div class="stat-label">Total Revenue</div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="stat-card info">
                <i class="fas fa-user-tie stat-icon" style="color: var(--info-color);"></i>
                <div class="stat-value"><?= formatNumber($stats['active_workers'] ?? 0) ?></div>
                <div class="stat-label">Active Workers</div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="stat-card danger">
                <i class="fas fa-hourglass-half stat-icon" style="color: var(--danger-color);"></i>
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
    </div>

    <!-- Analytics Section -->
    <div class="row mb-4">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-chart-pie"></i> Bookings by Status
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="bookingStatusChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-chart-pie"></i> Bookings by Priority
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="bookingPriorityChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-chart-line"></i> Revenue Trend (Last 30 Days)
                </div>
                <div class="card-body">
                    <div class="chart-container" style="height: 350px;">
                        <canvas id="revenueTrendChart"></canvas>
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
                                    <th>Priority</th>
                                    <th>Amount</th>
                                    <th>Date</th>
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
                                    <td>
                                        <span class="badge bg-<?= ($booking['priority'] ?? 'low') === 'urgent' ? 'danger' : (($booking['priority'] ?? 'low') === 'high' ? 'warning' : 'secondary') ?>">
                                            <?= ucfirst($booking['priority'] ?? 'N/A') ?>
                                        </span>
                                    </td>
                                    <td><?= formatCurrency($booking['total_fee'] ?? 0) ?></td>
                                    <td><?= date('M d, Y', strtotime($booking['created_at'] ?? date('Y-m-d'))) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Booking Status Chart
    const statusCtx = document.getElementById('bookingStatusChart')?.getContext('2d');
    if (statusCtx) {
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode(array_keys($analytics['bookings_by_status'] ?? [])) ?>,
                datasets: [{
                    data: <?= json_encode(array_values($analytics['bookings_by_status'] ?? [])) ?>,
                    backgroundColor: [
                        '#fff3cd',
                        '#d1ecf1',
                        '#cfe2ff',
                        '#d1e7dd',
                        '#f8d7da',
                        '#e2e3e5'
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

    // Booking Priority Chart
    const priorityCtx = document.getElementById('bookingPriorityChart')?.getContext('2d');
    if (priorityCtx) {
        new Chart(priorityCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_keys($analytics['bookings_by_priority'] ?? [])) ?>,
                datasets: [{
                    label: 'Number of Bookings',
                    data: <?= json_encode(array_values($analytics['bookings_by_priority'] ?? [])) ?>,
                    backgroundColor: [
                        '#d1e7dd',
                        '#fff3cd',
                        '#cfe2ff',
                        '#f8d7da'
                    ]
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
                        beginAtZero: true
                    }
                }
            }
        });
    }

    // Revenue Trend Chart
    const revenueCtx = document.getElementById('revenueTrendChart')?.getContext('2d');
    if (revenueCtx) {
        new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode(array_keys($analytics['revenue_trend'] ?? [])) ?>,
                datasets: [{
                    label: 'Daily Revenue',
                    data: <?= json_encode(array_values($analytics['revenue_trend'] ?? [])) ?>,
                    borderColor: '#4e73df',
                    backgroundColor: 'rgba(78, 115, 223, 0.1)',
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
</script>
