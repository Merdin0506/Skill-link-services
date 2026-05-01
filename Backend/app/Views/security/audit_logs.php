<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Logs - Security System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --success-color: #27ae60;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }
        
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, var(--primary-color) 0%, #34495e 100%);
            color: white;
            position: fixed;
            width: 250px;
            z-index: 1000;
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
        
        .filter-card {
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .event-row {
            border-left: 4px solid;
            background: white;
            margin-bottom: 10px;
            padding: 15px;
            border-radius: 5px;
            transition: all 0.3s;
        }
        
        .event-row:hover {
            transform: translateX(5px);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .event-row.critical {
            border-left-color: var(--danger-color);
        }
        
        .event-row.high {
            border-left-color: var(--warning-color);
        }
        
        .event-row.medium {
            border-left-color: var(--secondary-color);
        }
        
        .event-row.low {
            border-left-color: var(--success-color);
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
        
        .event-type-badge {
            background-color: #6c757d;
            color: white;
            font-size: 10px;
            padding: 2px 6px;
            border-radius: 8px;
        }
        
        .pagination {
            justify-content: center;
            margin-top: 20px;
        }
        
        .search-box {
            position: relative;
        }
        
        .search-box i {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
        }
        
        .search-box input {
            padding-left: 35px;
        }
        
        .export-btn {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
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
                <a class="nav-link" href="/security/dashboard">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link active" href="/security/audit-logs">
                    <i class="fas fa-list"></i> Audit Logs
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="/security/notifications">
                    <i class="fas fa-bell"></i> Notifications
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="/security/reports">
                    <i class="fas fa-chart-bar"></i> Reports
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="/security/blocked-ips">
                    <i class="fas fa-ban"></i> Blocked IPs
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="/security/settings">
                    <i class="fas fa-cog"></i> Settings
                </a>
            </li>
        </ul>
    </nav>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2><i class="fas fa-list"></i> Audit Logs</h2>
                <p class="text-muted mb-0">Complete security event history and audit trail</p>
            </div>
            <div>
                <button class="btn btn-outline-primary me-2" onclick="refreshLogs()">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
                <div class="dropdown d-inline-block">
                    <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="fas fa-download"></i> Export
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="#" onclick="exportLogs('json')">Export JSON</a></li>
                        <li><a class="dropdown-item" href="#" onclick="exportLogs('csv')">Export CSV</a></li>
                        <li><a class="dropdown-item" href="#" onclick="printLogs()">Print</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card filter-card">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <label class="form-label">Search</label>
                        <div class="search-box">
                            <i class="fas fa-search"></i>
                            <input type="text" class="form-control" id="searchInput" placeholder="Search events...">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Event Type</label>
                        <select class="form-select" id="eventTypeFilter">
                            <option value="">All Types</option>
                            <option value="login_success">Login Success</option>
                            <option value="login_failed">Login Failed</option>
                            <option value="logout">Logout</option>
                            <option value="unauthorized_access">Unauthorized Access</option>
                            <option value="suspicious_activity">Suspicious Activity</option>
                            <option value="sql_injection_attempt">SQL Injection</option>
                            <option value="xss_attempt">XSS Attempt</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Severity</label>
                        <select class="form-select" id="severityFilter">
                            <option value="">All Severities</option>
                            <option value="critical">Critical</option>
                            <option value="high">High</option>
                            <option value="medium">Medium</option>
                            <option value="low">Low</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Start Date</label>
                        <input type="datetime-local" class="form-control" id="startDate">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">End Date</label>
                        <input type="datetime-local" class="form-control" id="endDate">
                    </div>
                    <div class="col-md-1">
                        <label class="form-label">&nbsp;</label>
                        <button class="btn btn-primary w-100" onclick="applyFilters()">
                            <i class="fas fa-filter"></i> Filter
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Events List -->
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-history"></i> Security Events</h5>
                <small class="text-muted">Showing <span id="showingCount">0</span> events</small>
            </div>
            <div class="card-body">
                <div id="eventsList">
                    <!-- Events will be loaded here -->
                </div>
                
                <!-- Pagination -->
                <nav id="pagination">
                    <!-- Pagination will be loaded here -->
                </nav>
            </div>
        </div>
    </div>

    <!-- Export Button (Floating) -->
    <div class="export-btn">
        <button class="btn btn-primary btn-lg rounded-circle" onclick="scrollToTop()">
            <i class="fas fa-arrow-up"></i>
        </button>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let currentPage = 1;
        let totalPages = 1;
        
        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            loadEvents();
            
            // Set default date range (last 24 hours)
            const now = new Date();
            const yesterday = new Date(now.getTime() - 24 * 60 * 60 * 1000);
            document.getElementById('endDate').value = formatDateTimeLocal(now);
            document.getElementById('startDate').value = formatDateTimeLocal(yesterday);
            
            // Auto-refresh every 60 seconds
            setInterval(loadEvents, 60000);
        });
        
        // Load events
        async function loadEvents(page = 1) {
            try {
                const params = new URLSearchParams({
                    page: page,
                    limit: 50,
                    search: document.getElementById('searchInput').value,
                    event_type: document.getElementById('eventTypeFilter').value,
                    severity: document.getElementById('severityFilter').value,
                    start_date: document.getElementById('startDate').value,
                    end_date: document.getElementById('endDate').value
                });
                
                const response = await fetch(`/api/security/events?${params}`);
                const result = await response.json();
                
                if (result.status === 'success') {
                    updateEventsList(result.data.events);
                    updatePagination(result.data.pagination);
                    currentPage = page;
                    totalPages = result.data.pagination.pages;
                }
            } catch (error) {
                console.error('Error loading events:', error);
            }
        }
        
        // Update events list
        function updateEventsList(events) {
            const container = document.getElementById('eventsList');
            document.getElementById('showingCount').textContent = events.length;
            
            if (events.length === 0) {
                container.innerHTML = `
                    <div class="text-center py-5">
                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No events found</h5>
                        <p class="text-muted">Try adjusting your filters or search criteria</p>
                    </div>
                `;
                return;
            }
            
            container.innerHTML = events.map(event => `
                <div class="event-row ${event.severity}">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <div class="d-flex align-items-center mb-2">
                                <span class="event-type-badge me-2">${formatEventType(event.event_type)}</span>
                                <span class="severity-badge severity-${event.severity}">${event.severity}</span>
                                ${event.is_blocked ? '<span class="badge bg-danger ms-2">BLOCKED</span>' : ''}
                            </div>
                            <h6 class="mb-1">
                                ${event.email ? `<strong>${event.email}</strong>` : 'Unknown User'}
                                <small class="text-muted">• ${event.ip_address}</small>
                            </h6>
                            ${event.details ? `<p class="mb-1 small text-muted">${event.details}</p>` : ''}
                            <div class="small text-muted">
                                <i class="fas fa-globe"></i> ${event.request_uri || 'N/A'} • 
                                <i class="fas fa-code"></i> ${event.request_method || 'N/A'} • 
                                <i class="fas fa-clock"></i> ${formatDate(event.created_at)}
                            </div>
                        </div>
                        <div class="col-md-4 text-end">
                            <button class="btn btn-sm btn-outline-info me-1" onclick="showEventDetails(${event.id})">
                                <i class="fas fa-info-circle"></i> Details
                            </button>
                            <button class="btn btn-sm btn-outline-warning" onclick="blockIP('${event.ip_address}')" 
                                    ${event.is_blocked ? 'disabled' : ''}>
                                <i class="fas fa-ban"></i> Block IP
                            </button>
                        </div>
                    </div>
                </div>
            `).join('');
        }
        
        // Update pagination
        function updatePagination(pagination) {
            const container = document.getElementById('pagination');
            
            if (pagination.pages <= 1) {
                container.innerHTML = '';
                return;
            }
            
            let paginationHTML = '<ul class="pagination">';
            
            // Previous button
            paginationHTML += `
                <li class="page-item ${pagination.page <= 1 ? 'disabled' : ''}">
                    <a class="page-link" href="#" onclick="loadEvents(${pagination.page - 1}); return false;">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                </li>
            `;
            
            // Page numbers
            for (let i = 1; i <= pagination.pages; i++) {
                if (i === pagination.page || 
                    i === 1 || 
                    i === pagination.pages || 
                    (i >= pagination.page - 2 && i <= pagination.page + 2)) {
                    
                    paginationHTML += `
                        <li class="page-item ${i === pagination.page ? 'active' : ''}">
                            <a class="page-link" href="#" onclick="loadEvents(${i}); return false;">${i}</a>
                        </li>
                    `;
                } else if (i === pagination.page - 3 || i === pagination.page + 3) {
                    paginationHTML += '<li class="page-item disabled"><a class="page-link">...</a></li>';
                }
            }
            
            // Next button
            paginationHTML += `
                <li class="page-item ${pagination.page >= pagination.pages ? 'disabled' : ''}">
                    <a class="page-link" href="#" onclick="loadEvents(${pagination.page + 1}); return false;">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                </li>
            `;
            
            paginationHTML += '</ul>';
            container.innerHTML = paginationHTML;
        }
        
        // Apply filters
        function applyFilters() {
            loadEvents(1);
        }
        
        // Refresh logs
        function refreshLogs() {
            loadEvents(currentPage);
        }
        
        // Show event details
        function showEventDetails(eventId) {
            // This would open a modal with detailed event information
            alert(`Event details for ID: ${eventId}\n\nThis would show a modal with complete event details including user agent, request headers, and full context.`);
        }
        
        // Block IP
        async function blockIP(ipAddress) {
            if (!confirm(`Are you sure you want to block IP address: ${ipAddress}?`)) {
                return;
            }
            
            try {
                const response = await fetch(`/api/security/block-ip`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        ip_address: ipAddress,
                        reason: 'Manual block from audit logs'
                    })
                });
                
                const result = await response.json();
                
                if (result.status === 'success') {
                    alert('IP blocked successfully');
                    refreshLogs();
                } else {
                    alert('Error blocking IP: ' + result.message);
                }
            } catch (error) {
                console.error('Error blocking IP:', error);
                alert('Error blocking IP');
            }
        }
        
        // Export logs
        function exportLogs(format) {
            const params = new URLSearchParams({
                format: format,
                search: document.getElementById('searchInput').value,
                event_type: document.getElementById('eventTypeFilter').value,
                severity: document.getElementById('severityFilter').value,
                start_date: document.getElementById('startDate').value,
                end_date: document.getElementById('endDate').value
            });
            
            window.open(`/api/security/events/export?${params}`, '_blank');
        }
        
        // Print logs
        function printLogs() {
            window.print();
        }
        
        // Scroll to top
        function scrollToTop() {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
        
        // Format date
        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleString();
        }
        
        // Format datetime-local
        function formatDateTimeLocal(date) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            const hours = String(date.getHours()).padStart(2, '0');
            const minutes = String(date.getMinutes()).padStart(2, '0');
            
            return `${year}-${month}-${day}T${hours}:${minutes}`;
        }
        
        // Format event type
        function formatEventType(eventType) {
            return eventType.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
        }
        
        // Search on Enter key
        document.getElementById('searchInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                applyFilters();
            }
        });
    </script>
</body>
</html>
