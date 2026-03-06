<!-- Worker Dashboard -->
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="stat-card">
                <i class="fas fa-briefcase stat-icon" style="color: var(--primary-color);"></i>
                <div class="stat-value"><?= formatNumber($stats['available_bookings'] ?? 0) ?></div>
                <div class="stat-label">Available Jobs</div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="stat-card success">
                <i class="fas fa-check-circle stat-icon" style="color: var(--success-color);"></i>
                <div class="stat-value"><?= formatNumber($stats['assigned_bookings'] ?? 0) ?></div>
                <div class="stat-label">Assigned Jobs</div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="stat-card warning">
                <i class="fas fa-spinner stat-icon" style="color: var(--warning-color);"></i>
                <div class="stat-value"><?= formatNumber($stats['in_progress_bookings'] ?? 0) ?></div>
                <div class="stat-label">In Progress</div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="stat-card info">
                <i class="fas fa-tasks stat-icon" style="color: var(--info-color);"></i>
                <div class="stat-value"><?= formatNumber($stats['completed_jobs'] ?? 0) ?></div>
                <div class="stat-label">Completed Jobs</div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="stat-card success">
                <i class="fas fa-wallet stat-icon" style="color: var(--success-color);"></i>
                <div class="stat-value"><?= formatCurrency($stats['total_earnings'] ?? 0) ?></div>
                <div class="stat-label">Total Earnings</div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="stat-card">
                <i class="fas fa-star stat-icon" style="color: #ffc107;"></i>
                <div class="stat-value"><?= round($stats['average_rating'] ?? 0, 1) ?>/5</div>
                <div class="stat-label">Average Rating</div>
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
                            <a href="<?= base_url('worker/available-jobs') ?>" class="btn btn-primary w-100">
                                <i class="fas fa-search"></i> Browse Jobs
                            </a>
                        </div>
                        <div class="col-md-3 mb-2">
                            <a href="<?= base_url('worker/my-jobs') ?>" class="btn btn-info w-100">
                                <i class="fas fa-tasks"></i> My Jobs
                            </a>
                        </div>
                        <div class="col-md-3 mb-2">
                            <a href="<?= base_url('worker/earnings') ?>" class="btn btn-success w-100">
                                <i class="fas fa-wallet"></i> Earnings
                            </a>
                        </div>
                        <div class="col-md-3 mb-2">
                            <a href="<?= base_url('worker/profile') ?>" class="btn btn-secondary w-100">
                                <i class="fas fa-user"></i> Profile
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Analytics -->
    <div class="row mb-4">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-chart-line"></i> Earnings Trend (Last 30 Days)
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="earningsTrendChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-chart-pie"></i> Job Completion Rate
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="completionRateChart"></canvas>
                    </div>
                    <div class="text-center mt-3">
                        <h5 class="mb-0"><?= $analytics['job_completion_rate'] ?? 0 ?>%</h5>
                        <small class="text-muted">Jobs Completed Successfully</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Jobs -->
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-list"></i> Recent Jobs
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
                                    <th>Earnings</th>
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
                                    <td><?= formatCurrency($booking['worker_earnings'] ?? 0) ?></td>
                                    <td>
                                        <a href="<?= base_url('worker/job/' . ($booking['id'] ?? '')) ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
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
    // Earnings Trend Chart
    const earningsCtx = document.getElementById('earningsTrendChart')?.getContext('2d');
    if (earningsCtx) {
        new Chart(earningsCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode(array_keys($analytics['earnings_trend'] ?? [])) ?>,
                datasets: [{
                    label: 'Daily Earnings',
                    data: <?= json_encode(array_values($analytics['earnings_trend'] ?? [])) ?>,
                    borderColor: '#1cc88a',
                    backgroundColor: 'rgba(28, 200, 138, 0.1)',
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

    // Job Completion Rate Chart
    const completionCtx = document.getElementById('completionRateChart')?.getContext('2d');
    if (completionCtx) {
        const completionRate = <?= $analytics['job_completion_rate'] ?? 0 ?>;
        new Chart(completionCtx, {
            type: 'doughnut',
            data: {
                labels: ['Completed', 'Pending/Cancelled'],
                datasets: [{
                    data: [completionRate, 100 - completionRate],
                    backgroundColor: ['#1cc88a', '#e3e6f0']
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
