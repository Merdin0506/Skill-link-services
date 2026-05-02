<?= $this->extend('layouts/security_base') ?>

<?= $this->section('content') ?>
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
                <?php if (($securityNavActive ?? '') === 'reports'): ?>
                <div class="dropdown d-inline-block">
                    <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="fas fa-download"></i> Export
                    </button>
                    <ul class="dropdown-menu p-3" style="min-width: 280px;">
                        <li class="mb-2">
                            <label class="form-label mb-1">Start Date</label>
                            <input type="date" class="form-control form-control-sm" id="reportStartDate">
                        </li>
                        <li class="mb-2">
                            <label class="form-label mb-1">End Date</label>
                            <input type="date" class="form-control form-control-sm" id="reportEndDate">
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="#" onclick="exportReport('json'); return false;">Export JSON</a></li>
                        <li><a class="dropdown-item" href="#" onclick="exportReport('pdf'); return false;">Export PDF</a></li>
                        <li><a class="dropdown-item" href="#" onclick="printReport(); return false;">Print Report</a></li>
                    </ul>
                </div>
                <?php endif; ?>
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jspdf@2.5.1/dist/jspdf.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jspdf-autotable@3.8.2/dist/jspdf.plugin.autotable.min.js"></script>
    <script>
        let securityChart = null;
        
        // Initialize dashboard
        document.addEventListener('DOMContentLoaded', function() {
            loadDashboard();
            
            // Auto-refresh every 30 seconds
            setInterval(loadDashboard, 30000);
        });
        
        // Load dashboard data from the shared dashboard endpoint so both
        // main dashboard and security page use the same source of truth.
        async function loadDashboard() {
            try {
                const response = await fetch('/dashboard/refresh-security-data', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                console.log('Dashboard response status:', response.status);
                const result = await response.json();
                console.log('Dashboard result:', result);
                if (result.status === 'success') {
                    console.log('Dashboard data loaded:', result.data);
                    updateDashboard(result.data);
                } else {
                    console.error('Failed to load dashboard:', result.message || result);
                }
            } catch (error) {
                console.error('Error loading dashboard:', error);
            }
        }
        
        // Update dashboard with data
        function updateDashboard(data) {
            // Defensive DOM updates (some IDs may not exist on this view)
            const setText = (id, value) => {
                const el = document.getElementById(id);
                if (el) el.textContent = value;
            };

            // Update statistics safely
            setText('totalEvents', data.total_events || 0);
            setText('failedLogins', data.failed_logins || 0);
            setText('blockedIPs', data.blocked_ips || 0);
            setText('unreadNotifications', data.unread_notifications || 0);
            setText('notificationCount', data.unread_notifications || 0);

            // Update recent alerts
            try {
                updateRecentAlerts(data.recent_notifications || []);
            } catch (e) {
                console.warn('updateRecentAlerts failed', e);
            }

            // Update recent events
            try {
                updateRecentEvents(data.recent_events || []);
            } catch (e) {
                console.warn('updateRecentEvents failed', e);
            }

            // Update top threats
            try {
                updateTopThreats(data.top_threats || []);
            } catch (e) {
                console.warn('updateTopThreats failed', e);
            }

            // Update chart
            try {
                updateChart(data.chart_data || []);
            } catch (e) {
                console.warn('updateChart failed', e);
            }

            // Optional debug panel: show raw JSON when ?debug=1 is present
            if (new URLSearchParams(window.location.search).get('debug') === '1') {
                showDebugJSON(data);
            }
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
        async function exportReport(format) {
            try {
                const startDate = document.getElementById('reportStartDate')?.value || '';
                const endDate = document.getElementById('reportEndDate')?.value || '';

                const params = new URLSearchParams({
                    format,
                    start_date: startDate,
                    end_date: endDate,
                });

                const response = await fetch(`/dashboard/security-events/export?${params}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const result = await response.json();
                if (!response.ok || result.status !== 'success') {
                    throw new Error(result.message || 'Unable to export report');
                }

                // Real PDF export using jsPDF + autoTable
                if (format === 'pdf') {
                    const jsPDFCtor = window.jspdf && window.jspdf.jsPDF ? window.jspdf.jsPDF : null;
                    if (!jsPDFCtor) {
                        throw new Error('PDF library failed to load');
                    }

                    const doc = new jsPDFCtor({ orientation: 'landscape', unit: 'pt', format: 'a4' });
                    const events = Array.isArray(result.data?.events) ? result.data.events : [];
                    const generatedAt = result.data?.exported_at || new Date().toLocaleString();
                    const meta = result.data?.meta || {};
                    const rangeStart = meta?.date_range?.start || 'All available data';
                    const rangeEnd = meta?.date_range?.end || 'All available data';

                    showExportNotice(`Exporting PDF report for ${formatDateRange(rangeStart, rangeEnd)}...`);

                    doc.setFontSize(16);
                    doc.text('SkillLink Security Report', 40, 40);
                    doc.setFontSize(10);
                    doc.text(`Generated: ${generatedAt}`, 40, 58);
                    doc.text(`Date Range: ${rangeStart} to ${rangeEnd}`, 40, 74);
                    doc.text(`Total Events: ${events.length}`, 40, 90);

                    const rows = events.map((event) => [
                        formatEventType(event.event_type || 'n/a'),
                        event.severity || 'n/a',
                        event.email || 'Unknown',
                        event.ip_address || 'n/a',
                        event.created_at || 'n/a',
                        event.details || ''
                    ]);

                    doc.autoTable({
                        startY: 106,
                        head: [['Event', 'Severity', 'User/Email', 'IP Address', 'Created At', 'Details']],
                        body: rows,
                        styles: { fontSize: 8, cellPadding: 4 },
                        headStyles: { fillColor: [30, 60, 114] },
                        columnStyles: {
                            0: { cellWidth: 85 },
                            1: { cellWidth: 60 },
                            2: { cellWidth: 140 },
                            3: { cellWidth: 80 },
                            4: { cellWidth: 110 },
                            5: { cellWidth: 210 }
                        }
                    });

                    const fileStart = (rangeStart === 'All available data' ? 'all' : String(rangeStart).slice(0, 10)).replace(/[^0-9A-Za-z_-]/g, '-');
                    const fileEnd = (rangeEnd === 'All available data' ? 'all' : String(rangeEnd).slice(0, 10)).replace(/[^0-9A-Za-z_-]/g, '-');
                    doc.save(`security-report-${fileStart}-to-${fileEnd}.pdf`);
                    return;
                }

                const payload = JSON.stringify(result.data, null, 2);
                const blob = new Blob([payload], { type: 'application/json' });
                const url = URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.href = url;
                const ext = format || 'json';
                const jsonMeta = result.data?.meta || {};
                const jsonStartDate = jsonMeta?.date_range?.start || 'All available data';
                const jsonEndDate = jsonMeta?.date_range?.end || 'All available data';
                showExportNotice(`Exporting JSON report for ${formatDateRange(jsonStartDate, jsonEndDate)}...`);
                link.download = `security-report-${new Date().toISOString().slice(0, 10)}.${ext}`;
                document.body.appendChild(link);
                link.click();
                link.remove();
                URL.revokeObjectURL(url);
            } catch (error) {
                console.error('Error exporting report:', error);
            }
        }
        
        // Print report
        function printReport() {
            window.print();
        }

        function formatDateRange(startDate, endDate) {
            return `${startDate} to ${endDate}`;
        }

        function showExportNotice(message) {
            let notice = document.getElementById('exportNotice');
            if (!notice) {
                notice = document.createElement('div');
                notice.id = 'exportNotice';
                notice.style.position = 'fixed';
                notice.style.top = '16px';
                notice.style.right = '16px';
                notice.style.zIndex = '100000';
                notice.style.background = '#1e3c72';
                notice.style.color = '#fff';
                notice.style.padding = '10px 14px';
                notice.style.borderRadius = '8px';
                notice.style.boxShadow = '0 10px 30px rgba(0,0,0,0.18)';
                notice.style.fontSize = '13px';
                notice.style.maxWidth = '340px';
                document.body.appendChild(notice);
            }

            notice.textContent = message;
            notice.style.opacity = '1';
            notice.style.transform = 'translateY(0)';
            clearTimeout(window.exportNoticeTimer);
            window.exportNoticeTimer = setTimeout(() => {
                if (notice) {
                    notice.style.opacity = '0';
                    notice.style.transition = 'opacity 0.3s ease';
                    setTimeout(() => notice.remove(), 350);
                }
            }, 2500);
        }
        
        // Debug helper: render returned JSON on-page when ?debug=1 is set
        function showDebugJSON(data) {
            let panel = document.getElementById('securityDebug');
            if (!panel) {
                panel = document.createElement('pre');
                panel.id = 'securityDebug';
                panel.style.position = 'fixed';
                panel.style.right = '12px';
                panel.style.bottom = '12px';
                panel.style.width = '420px';
                panel.style.maxHeight = '60vh';
                panel.style.overflow = 'auto';
                panel.style.background = 'rgba(0,0,0,0.85)';
                panel.style.color = '#fff';
                panel.style.padding = '12px';
                panel.style.zIndex = 99999;
                panel.style.fontSize = '12px';
                panel.style.borderRadius = '6px';
                document.body.appendChild(panel);
            }
            panel.textContent = JSON.stringify(data, null, 2);
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

<?= $this->endSection() ?>
