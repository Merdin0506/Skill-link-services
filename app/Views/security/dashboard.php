<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security Dashboard - SkillLink</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --success-color: #27ae60;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
            --dark-bg: #1a1a1a;
            --dark-sidebar: #2c3e50;
            --light-bg: #f8f9fa;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--light-bg);
        }
        
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, var(--dark-sidebar) 0%, #34495e 100%);
            color: white;
            position: fixed;
            width: 250px;
            z-index: 1000;
            transition: all 0.3s;
        }
        
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 12px 20px;
            border-radius: 5px;
            margin: 2px 10px;
            transition: all 0.3s;
        }
        
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
        }
        
        .sidebar .nav-link i {
            width: 20px;
            margin-right: 10px;
        }
        
        .main-content {
            margin-left: 250px;
            padding: 20px;
            min-height: 100vh;
        }
        
        .stat-card {
            border-radius: 10px;
            border: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            overflow: hidden;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }
        
        .stat-card .card-body {
            padding: 20px;
        }
        
        .stat-card .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
            margin-bottom: 15px;
        }
        
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: var(--danger-color);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: bold;
        }
        
        .alert-item {
            border-left: 4px solid;
            margin-bottom: 10px;
            padding: 15px;
            border-radius: 5px;
            background: white;
        }
        
        .alert-item.critical {
            border-left-color: var(--danger-color);
        }
        
        .alert-item.high {
            border-left-color: var(--warning-color);
        }
        
        .alert-item.medium {
            border-left-color: var(--secondary-color);
        }
        
        .alert-item.low {
            border-left-color: var(--success-color);
        }
        
        .event-item {
            padding: 10px;
            border-bottom: 1px solid #eee;
            transition: background-color 0.3s;
        }
        
        .event-item:hover {
            background-color: #f8f9fa;
        }
        
        .event-item:last-child {
            border-bottom: none;
        }
        
        .severity-badge {
            font-size: 11px;
            padding: 3px 8px;
            border-radius: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .severity-critical {
            background-color: #dc3545;
            color: white;
        }
        
        .severity-high {
            background-color: #fd7e14;
            color: white;
        }
        
        .severity-medium {
            background-color: #ffc107;
            color: #212529;
        }
        
        .severity-low {
            background-color: #28a745;
            color: white;
        }
        
        .chart-container {
            position: relative;
            height: 300px;
            margin: 20px 0;
        }
        
        .top-header {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .theme-toggle {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1001;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <nav class="sidebar">
        <div class="p-3">
            <h4 class="text-center">
                <i class="fas fa-shield-alt"></i> Security
            </h4>
        </div>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link active" href="#dashboard">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#events">
                    <i class="fas fa-list"></i> Audit Logs
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#notifications">
                    <i class="fas fa-bell"></i> Notifications
                    <span class="notification-badge" id="notificationCount">0</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#reports">
                    <i class="fas fa-chart-bar"></i> Reports
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#blocked-ips">
                    <i class="fas fa-ban"></i> Blocked IPs
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#settings">
                    <i class="fas fa-cog"></i> Settings
                </a>
            </li>
        </ul>
    </nav>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Header -->
        <div class="top-header">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h2><i class="fas fa-shield-alt"></i> Security Dashboard</h2>
                    <p class="text-muted mb-0">Real-time security monitoring and threat detection</p>
                </div>
                <div class="col-md-6 text-end">
                    <button class="btn btn-outline-primary me-2" onclick="refreshDashboard()">
                        <i class="fas fa-sync-alt"></i> Refresh
                    </button>
                    <div class="dropdown d-inline-block">
                        <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="fas fa-download"></i> Export
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#" onclick="exportReport('json')">Export JSON</a></li>
                            <li><a class="dropdown-item" href="#" onclick="exportReport('pdf')">Export PDF</a></li>
                            <li><a class="dropdown-item" href="#" onclick="printReport()">Print Report</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="card stat-card bg-primary text-white">
                    <div class="card-body">
                        <div class="stat-icon bg-white bg-opacity-25">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <h3 id="totalEvents">0</h3>
                        <p class="mb-0">Total Events</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card stat-card bg-danger text-white">
                    <div class="card-body">
                        <div class="stat-icon bg-white bg-opacity-25">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <h3 id="failedLogins">0</h3>
                        <p class="mb-0">Failed Logins</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card stat-card bg-success text-white">
                    <div class="card-body">
                        <div class="stat-icon bg-white bg-opacity-25">
                            <i class="fas fa-ban"></i>
                        </div>
                        <h3 id="blockedIPs">0</h3>
                        <p class="mb-0">Blocked IPs</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card stat-card bg-warning text-white">
                    <div class="card-body">
                        <div class="stat-icon bg-white bg-opacity-25">
                            <i class="fas fa-bell"></i>
                        </div>
                        <h3 id="unreadNotifications">0</h3>
                        <p class="mb-0">Unread Alerts</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts and Alerts Row -->
        <div class="row mb-4">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-chart-line"></i> Security Events Timeline</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="securityChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-exclamation-circle"></i> Recent Alerts</h5>
                    </div>
                    <div class="card-body" style="max-height: 350px; overflow-y: auto;">
                        <div id="recentAlerts">
                            <!-- Alerts will be loaded here -->
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Events and Top Threats Row -->
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-list"></i> Recent Security Events</h5>
                    </div>
                    <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                        <div id="recentEvents">
                            <!-- Events will be loaded here -->
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-fire"></i> Top Threats</h5>
                    </div>
                    <div class="card-body">
                        <div id="topThreats">
                            <!-- Top threats will be loaded here -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        let securityChart = null;
        
        // Initialize dashboard
        document.addEventListener('DOMContentLoaded', function() {
            loadDashboard();
            
            // Auto-refresh every 30 seconds
            setInterval(loadDashboard, 30000);
        });
        
        // Load dashboard data
        async function loadDashboard() {
            try {
                const response = await fetch('/api/security/dashboard');
                const result = await response.json();
                
                if (result.status === 'success') {
                    updateDashboard(result.data);
                }
            } catch (error) {
                console.error('Error loading dashboard:', error);
            }
        }
        
        // Update dashboard with data
        function updateDashboard(data) {
            // Update statistics
            document.getElementById('totalEvents').textContent = data.total_events || 0;
            document.getElementById('failedLogins').textContent = data.failed_logins || 0;
            document.getElementById('blockedIPs').textContent = data.blocked_ips || 0;
            document.getElementById('unreadNotifications').textContent = data.unread_notifications || 0;
            document.getElementById('notificationCount').textContent = data.unread_notifications || 0;
            
            // Update recent alerts
            updateRecentAlerts(data.recent_notifications || []);
            
            // Update recent events
            updateRecentEvents(data.recent_events || []);
            
            // Update top threats
            updateTopThreats(data.top_threats || []);
            
            // Update chart
            updateChart(data.chart_data || []);
        }
        
        // Update recent alerts
        function updateRecentAlerts(alerts) {
            const container = document.getElementById('recentAlerts');
            
            if (alerts.length === 0) {
                container.innerHTML = '<p class="text-muted">No recent alerts</p>';
                return;
            }
            
            container.innerHTML = alerts.map(alert => `
                <div class="alert-item ${alert.priority}">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="mb-1">${alert.title}</h6>
                            <p class="mb-1 small">${alert.message}</p>
                            <small class="text-muted">${formatDate(alert.created_at)}</small>
                        </div>
                        <span class="severity-badge severity-${alert.priority}">${alert.priority}</span>
                    </div>
                </div>
            `).join('');
        }
        
        // Update recent events
        function updateRecentEvents(events) {
            const container = document.getElementById('recentEvents');
            
            if (events.length === 0) {
                container.innerHTML = '<p class="text-muted">No recent events</p>';
                return;
            }
            
            container.innerHTML = events.map(event => `
                <div class="event-item">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <strong>${formatEventType(event.event_type)}</strong>
                            <br>
                            <small class="text-muted">
                                ${event.email || 'Unknown'} • ${event.ip_address}
                                ${event.is_blocked ? '<span class="badge bg-danger ms-2">BLOCKED</span>' : ''}
                            </small>
                        </div>
                        <div class="text-end">
                            <span class="severity-badge severity-${event.severity}">${event.severity}</span>
                            <br>
                            <small class="text-muted">${formatDate(event.created_at)}</small>
                        </div>
                    </div>
                </div>
            `).join('');
        }
        
        // Update top threats
        function updateTopThreats(threats) {
            const container = document.getElementById('topThreats');
            
            if (threats.length === 0) {
                container.innerHTML = '<p class="text-muted">No threats detected</p>';
                return;
            }
            
            container.innerHTML = threats.map((threat, index) => `
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div>
                        <strong>${threat.ip_address}</strong>
                        <br>
                        <small class="text-muted">${threat.event_count} events</small>
                    </div>
                    <span class="badge bg-danger">#${index + 1}</span>
                </div>
            `).join('');
        }
        
        // Update chart
        function updateChart(data) {
            const ctx = document.getElementById('securityChart').getContext('2d');
            
            if (securityChart) {
                securityChart.destroy();
            }
            
            securityChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.map(item => item.period),
                    datasets: [{
                        label: 'Failed Logins',
                        data: data.filter(item => item.event_type === 'login_failed').map(item => item.count),
                        borderColor: '#dc3545',
                        backgroundColor: 'rgba(220, 53, 69, 0.1)',
                        tension: 0.4
                    }, {
                        label: 'Successful Logins',
                        data: data.filter(item => item.event_type === 'login_success').map(item => item.count),
                        borderColor: '#28a745',
                        backgroundColor: 'rgba(40, 167, 69, 0.1)',
                        tension: 0.4
                    }, {
                        label: 'Suspicious Activities',
                        data: data.filter(item => item.event_type === 'suspicious_activity').map(item => item.count),
                        borderColor: '#ffc107',
                        backgroundColor: 'rgba(255, 193, 7, 0.1)',
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
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
        
        // Refresh dashboard
        function refreshDashboard() {
            loadDashboard();
        }
        
        // Export report
        function exportReport(format) {
            window.open(`/api/security/report?format=${format}`, '_blank');
        }
        
        // Print report
        function printReport() {
            window.print();
        }
        
        // Format date
        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleString();
        }
        
        // Format event type
        function formatEventType(eventType) {
            return eventType.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
        }
    </script>
</body>
</html>
