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
                <i class="fas fa-peso-sign stat-icon" style="color: var(--warning-color);"></i>
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

    <!-- Security Alerts Section -->
    <div class="row mb-4">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <i class="fas fa-shield-alt"></i> Security Alerts & Login Attempts
                        <span class="badge bg-danger ms-2" id="securityAlertCount">0</span>
                    </div>
                    <div>
                        <span class="badge bg-info me-2" id="lastSyncTime">
                            <i class="fas fa-clock"></i> Never synced
                        </span>
                        <a href="/security/dashboard" class="btn btn-sm btn-outline-primary">View Security Dashboard</a>
                        <button class="btn btn-sm btn-outline-secondary" onclick="refreshSecurityAlerts()">
                            <i class="fas fa-sync-alt"></i> Refresh
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Tabs -->
                    <ul class="nav nav-tabs mb-3" id="securityTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="alerts-tab" data-bs-toggle="tab" data-bs-target="#alerts" type="button" role="tab">
                                <i class="fas fa-bell"></i> Security Alerts
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="login-attempts-tab" data-bs-toggle="tab" data-bs-target="#login-attempts" type="button" role="tab">
                                <i class="fas fa-sign-in-alt"></i> Login Attempts
                            </button>
                        </li>
                    </ul>
                    
                    <!-- Tab Content -->
                    <div class="tab-content" id="securityTabContent">
                        <!-- Security Alerts Tab -->
                        <div class="tab-pane fade show active" id="alerts" role="tabpanel">
                            <div id="securityAlerts">
                                <div class="text-center text-muted py-3">
                                    <i class="fas fa-shield-alt fa-2x mb-2 opacity-50"></i>
                                    <p class="mb-0">Loading security alerts...</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Login Attempts Tab -->
                        <div class="tab-pane fade" id="login-attempts" role="tabpanel">
                            <div id="loginAttempts">
                                <div class="text-center text-muted py-3">
                                    <i class="fas fa-sign-in-alt fa-2x mb-2 opacity-50"></i>
                                    <p class="mb-0">Loading login attempts...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-chart-line"></i> Security Statistics
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6 mb-3">
                            <div class="security-stat">
                                <i class="fas fa-exclamation-triangle text-danger fa-2x mb-2"></i>
                                <h4 id="failedLoginsCount">0</h4>
                                <small class="text-muted">Failed Logins</small>
                            </div>
                        </div>
                        <div class="col-6 mb-3">
                            <div class="security-stat">
                                <i class="fas fa-ban text-warning fa-2x mb-2"></i>
                                <h4 id="blockedIPsCount">0</h4>
                                <small class="text-muted">Blocked IPs</small>
                            </div>
                        </div>
                        <div class="col-6 mb-3">
                            <div class="security-stat">
                                <i class="fas fa-bell text-info fa-2x mb-2"></i>
                                <h4 id="unreadNotificationsCount">0</h4>
                                <small class="text-muted">Unread Alerts</small>
                            </div>
                        </div>
                        <div class="col-6 mb-3">
                            <div class="security-stat">
                                <i class="fas fa-list text-primary fa-2x mb-2"></i>
                                <h4 id="totalEventsCount">0</h4>
                                <small class="text-muted">Total Events</small>
                            </div>
                        </div>
                    </div>
                    <div class="text-center mt-3">
                        <a href="/security/audit-logs" class="btn btn-sm btn-outline-info">View All Logs</a>
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

        const bookingStatusLabels = <?= json_encode(array_keys($analytics['bookings_by_status'] ?? [])) ?>;
        const bookingStatusData = <?= json_encode(array_values($analytics['bookings_by_status'] ?? [])) ?>;
        const bookingPriorityLabels = <?= json_encode(array_keys($analytics['bookings_by_priority'] ?? [])) ?>;
        const bookingPriorityData = <?= json_encode(array_values($analytics['bookings_by_priority'] ?? [])) ?>;
        const revenueTrendLabels = <?= json_encode(array_keys($analytics['revenue_trend'] ?? [])) ?>;
        const revenueTrendData = <?= json_encode(array_values($analytics['revenue_trend'] ?? [])) ?>;

        // Booking Status Chart
        const statusCtx = document.getElementById('bookingStatusChart')?.getContext('2d');
        if (statusCtx && hasAnyData(bookingStatusData)) {
            new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: bookingStatusLabels,
                datasets: [{
                    data: bookingStatusData,
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
        } else {
            showEmptyState('bookingStatusChart', 'fa-chart-pie', 'No booking status data yet.');
        }

        // Booking Priority Chart
        const priorityCtx = document.getElementById('bookingPriorityChart')?.getContext('2d');
        if (priorityCtx && hasAnyData(bookingPriorityData)) {
            new Chart(priorityCtx, {
            type: 'bar',
            data: {
                labels: bookingPriorityLabels,
                datasets: [{
                    label: 'Number of Bookings',
                    data: bookingPriorityData,
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
        } else {
            showEmptyState('bookingPriorityChart', 'fa-chart-bar', 'No booking priority data yet.');
        }

        // Revenue Trend Chart
        const revenueCtx = document.getElementById('revenueTrendChart')?.getContext('2d');
        if (revenueCtx && hasAnyData(revenueTrendData)) {
            new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: revenueTrendLabels,
                datasets: [{
                    label: 'Daily Revenue',
                    data: revenueTrendData,
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
                                return 'PHP ' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
            });
        } else {
            showEmptyState('revenueTrendChart', 'fa-chart-line', 'No revenue trend data yet.');
        }
    });

    // Real-time Security Synchronization
    let lastSyncTime = 0;
    let syncInterval = null;
    let isPolling = false;
    
    // Debug: Log the security data
    const securityData = <?= json_encode($securityData ?? []) ?>;
    console.log('Security Data Loaded:', securityData);
    console.log('Security Data Keys:', Object.keys(securityData));
    console.log('Recent Events Count:', (securityData.recent_events || []).length);
    console.log('Recent Notifications Count:', (securityData.recent_notifications || []).length);
    
    // Check if data is empty or has error
    if (!securityData || Object.keys(securityData).length === 0) {
        console.error('Security data is empty or undefined!');
        // Try to load data manually
        loadSecurityAlerts();
    } else if (securityData.error) {
        console.error('Database connection error:', securityData.error);
        // Show database error message
        showDatabaseError(securityData.error);
    } else {
        console.log('Security data loaded successfully');
        // Initialize real-time sync
        initializeRealtimeSync();
        
        // Start with initial data
        updateSecurityAlerts(securityData);
        lastSyncTime = <?= time() ?>;
        updateLastSyncTime();
    }

    function initializeRealtimeSync() {
        // Show sync status
        showSyncStatus('connecting');
        
        // Start real-time polling
        startRealtimePolling();
    }

    function startRealtimePolling() {
        if (isPolling) return;
        
        isPolling = true;

        if (syncInterval) {
            clearInterval(syncInterval);
        }

        loadSecurityAlerts();
        syncInterval = setInterval(() => {
            loadSecurityAlerts();
        }, 10000);

        showSyncStatus('live');
    }

    function showSyncStatus(status) {
        const syncBadge = document.getElementById('syncStatus');
        if (!syncBadge) return;
        
        syncBadge.style.display = 'inline-block';
        
        switch(status) {
            case 'connecting':
                syncBadge.className = 'badge bg-warning ms-2';
                syncBadge.innerHTML = '<i class="fas fa-sync-alt fa-spin"></i> Connecting...';
                break;
            case 'live':
                syncBadge.className = 'badge bg-success ms-2';
                syncBadge.innerHTML = '<i class="fas fa-sync-alt fa-spin"></i> Live';
                break;
            case 'error':
                syncBadge.className = 'badge bg-danger ms-2';
                syncBadge.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Error';
                break;
            default:
                syncBadge.style.display = 'none';
        }
    }

    function updateLastSyncTime() {
        const lastSyncBadge = document.getElementById('lastSyncTime');
        const now = new Date();
        lastSyncBadge.innerHTML = `<i class="fas fa-clock"></i> Last sync: ${now.toLocaleTimeString()}`;
    }

    function loadSecurityAlerts() {
        // Fetch fresh data from server
        fetch('/dashboard/refresh-security-data', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(result => {
            if (result.status === 'success') {
                updateSecurityAlerts(result.data);
                lastSyncTime = result.timestamp || Date.now() / 1000;
                updateLastSyncTime();
                showSyncStatus('live');
                console.log('Security data loaded via fetch:', result.data);
            } else {
                console.error('Failed to load security data:', result.message);
                showSyncStatus('error');
            }
        })
        .catch(error => {
            console.error('Error loading security alerts:', error);
            showSyncStatus('error');
        });
    }

    function updateSecurityAlerts(data) {
        console.log('updateSecurityAlerts called with data:', data);
        
        // Update statistics
        const alertCount = document.getElementById('securityAlertCount');
        const failedLoginsCount = document.getElementById('failedLoginsCount');
        const blockedIPsCount = document.getElementById('blockedIPsCount');
        const unreadNotificationsCount = document.getElementById('unreadNotificationsCount');
        const totalEventsCount = document.getElementById('totalEventsCount');
        
        if (alertCount) alertCount.textContent = (data.unread_notifications || 0) + (data.critical_alerts || 0);
        if (failedLoginsCount) failedLoginsCount.textContent = data.failed_logins || 0;
        if (blockedIPsCount) blockedIPsCount.textContent = data.blocked_ips || 0;
        if (unreadNotificationsCount) unreadNotificationsCount.textContent = data.unread_notifications || 0;
        if (totalEventsCount) totalEventsCount.textContent = data.total_events || 0;
        
        // Update security alerts list
        updateSecurityAlertsList(data.recent_notifications || []);
        
        // Update login attempts list
        updateLoginAttemptsList(data.recent_events || []);
        
        console.log('Security alerts updated successfully');
    }

    function updateSecurityAlertsList(notifications) {
        const alertsContainer = document.getElementById('securityAlerts');
        if (!alertsContainer) return;
        
        if (notifications.length === 0) {
            alertsContainer.innerHTML = `
                <div class="text-center text-muted py-3">
                    <i class="fas fa-shield-alt fa-2x mb-2 opacity-50"></i>
                    <p class="mb-0">No security alerts at this time.</p>
                </div>
            `;
            return;
        }
        
        let alertsHtml = '<div class="list-group">';
        notifications.forEach(notification => {
            const priorityClass = getPriorityClass(notification.priority || 'medium');
            const iconClass = getPriorityIcon(notification.priority || 'medium');
            const timeAgo = formatTimeAgo(notification.created_at);
            
            alertsHtml += `
                <div class="list-group-item">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <h6 class="mb-1">
                                <i class="fas ${iconClass} ${priorityClass}"></i>
                                ${notification.title || 'Security Alert'}
                            </h6>
                            <p class="mb-1">${notification.message || 'No message'}</p>
                            <small class="text-muted">
                                <i class="fas fa-clock"></i> ${timeAgo}
                                ${notification.ip_address ? `• ${notification.ip_address}` : ''}
                            </small>
                        </div>
                        <span class="badge ${priorityClass}">${notification.priority || 'medium'}</span>
                    </div>
                </div>
            `;
        });
        alertsHtml += '</div>';
        
        alertsContainer.innerHTML = alertsHtml;
    }

    function updateLoginAttemptsList(events) {
        const attemptsContainer = document.getElementById('loginAttempts');
        if (!attemptsContainer) return;
        
        // Filter only login events
        const loginEvents = events.filter(event => 
            event.event_type === 'login_failed' || event.event_type === 'login_success'
        );
        
        if (loginEvents.length === 0) {
            attemptsContainer.innerHTML = `
                <div class="text-center text-muted py-3">
                    <i class="fas fa-sign-in-alt fa-2x mb-2 opacity-50"></i>
                    <p class="mb-0">No login attempts recorded.</p>
                </div>
            `;
            return;
        }
        
        let attemptsHtml = '<div class="table-responsive"><table class="table table-sm">';
        attemptsHtml += `
            <thead>
                <tr>
                    <th>Status</th>
                    <th>User/Email</th>
                    <th>IP Address</th>
                    <th>Time</th>
                    <th>Details</th>
                </tr>
            </thead>
            <tbody>
        `;
        
        loginEvents.forEach(event => {
            const isSuccess = event.event_type === 'login_success';
            const statusBadge = isSuccess ? 
                '<span class="badge bg-success"><i class="fas fa-check"></i> Success</span>' : 
                '<span class="badge bg-danger"><i class="fas fa-times"></i> Failed</span>';
            
            const timeAgo = formatTimeAgo(event.created_at);
            const fullTime = formatFullTime(event.created_at);
            
            attemptsHtml += `
                <tr>
                    <td>${statusBadge}</td>
                    <td>
                        <strong>${event.email || 'Unknown'}</strong>
                        ${event.user_id ? `<br><small class="text-muted">ID: ${event.user_id}</small>` : ''}
                    </td>
                    <td>
                        <code>${event.ip_address || 'N/A'}</code>
                        ${event.user_agent ? `<br><small class="text-muted">${truncateText(event.user_agent, 30)}</small>` : ''}
                    </td>
                    <td>
                        <span title="${fullTime}">${timeAgo}</span>
                    </td>
                    <td>
                        <small class="text-muted">${event.details || 'No details'}</small>
                    </td>
                </tr>
            `;
        });
        
        attemptsHtml += '</tbody></table></div>';
        
        // Add summary info
        const successCount = loginEvents.filter(e => e.event_type === 'login_success').length;
        const failCount = loginEvents.filter(e => e.event_type === 'login_failed').length;
        
        attemptsHtml += `
            <div class="mt-3 text-muted">
                <small>
                    Showing ${loginEvents.length} recent attempts • 
                    <span class="text-success">${successCount} successful</span> • 
                    <span class="text-danger">${failCount} failed</span>
                    <a href="/security/audit-logs" class="float-end">View all logs →</a>
                </small>
            </div>
        `;
        
        attemptsContainer.innerHTML = attemptsHtml;
    }

    function getPriorityClass(priority) {
        switch(priority.toLowerCase()) {
            case 'critical': return 'text-danger';
            case 'high': return 'text-warning';
            case 'medium': return 'text-info';
            case 'low': return 'text-success';
            default: return 'text-secondary';
        }
    }

    function getPriorityIcon(priority) {
        switch(priority.toLowerCase()) {
            case 'critical': return 'fa-exclamation-triangle';
            case 'high': return 'fa-exclamation-circle';
            case 'medium': return 'fa-info-circle';
            case 'low': return 'fa-check-circle';
            default: return 'fa-bell';
        }
    }

    function formatTimeAgo(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const seconds = Math.floor((now - date) / 1000);
        
        if (seconds < 60) return 'Just now';
        if (seconds < 3600) return Math.floor(seconds / 60) + ' minutes ago';
        if (seconds < 86400) return Math.floor(seconds / 3600) + ' hours ago';
        return Math.floor(seconds / 86400) + ' days ago';
    }

    function formatFullTime(dateString) {
        const date = new Date(dateString);
        return date.toLocaleString();
    }

    function truncateText(text, maxLength) {
        if (!text) return 'N/A';
        if (text.length <= maxLength) return text;
        return text.substring(0, maxLength) + '...';
    }

    function showDatabaseError(error) {
        const alertsContainer = document.getElementById('securityAlerts');
        const attemptsContainer = document.getElementById('loginAttempts');
        
        const errorMessage = `
            <div class="alert alert-warning">
                <h5><i class="fas fa-exclamation-triangle"></i> Database Connection Error</h5>
                <p>The security dashboard cannot connect to the database. Please check:</p>
                <ul>
                    <li>XAMPP MySQL service is running</li>
                    <li>Database 'skill_link_services' exists</li>
                    <li>Database credentials are correct</li>
                </ul>
                <p><strong>Error:</strong> ${error}</p>
                <hr>
                <p class="mb-0">
                    <small class="text-muted">
                        To fix this issue:
                        <br>1. Start XAMPP Control Panel
                        <br>2. Start Apache and MySQL services
                        <br>3. Refresh this page
                    </small>
                </p>
            </div>
        `;
        
        if (alertsContainer) alertsContainer.innerHTML = errorMessage;
        if (attemptsContainer) attemptsContainer.innerHTML = errorMessage;
        
        // Update statistics to show error state
        document.getElementById('failedLoginsCount').textContent = '—';
        document.getElementById('blockedIPsCount').textContent = '—';
        document.getElementById('unreadNotificationsCount').textContent = '—';
        document.getElementById('totalEventsCount').textContent = '—';
        document.getElementById('securityAlertCount').textContent = '—';
        
        showSyncStatus('error');
    }

    // Manual refresh function
    window.refreshSecurityAlerts = function() {
        console.log('Manual refresh triggered');
        loadSecurityAlerts();
    };
</script>

