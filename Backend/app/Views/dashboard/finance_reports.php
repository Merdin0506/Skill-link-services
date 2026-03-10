<?= view('layouts/page_header', ['pageTitle' => 'Financial Reports']) ?>

    <!-- Page Content -->
    <div class="page-content">
        <div class="row mb-4">
            <div class="col-12">
                <h3 class="mb-0"><i class="fas fa-chart-bar"></i> Financial Reports</h3>
            </div>
        </div>

        <!-- Financial Summary Cards -->
        <div class="row mb-4">
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="stat-card success">
                    <i class="fas fa-peso-sign stat-icon" style="color: var(--success-color);"></i>
                    <div class="stat-value">PHP <?= number_format($stats['total_revenue'] ?? 0, 2) ?></div>
                    <div class="stat-label">Total Revenue</div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="stat-card info">
                    <i class="fas fa-calendar-month stat-icon" style="color: var(--info-color);"></i>
                    <div class="stat-value">PHP <?= number_format($stats['monthly_revenue'] ?? 0, 2) ?></div>
                    <div class="stat-label">This Month</div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="stat-card primary">
                    <i class="fas fa-percentage stat-icon" style="color: var(--primary-color);"></i>
                    <div class="stat-value">PHP <?= number_format($stats['total_commission'] ?? 0, 2) ?></div>
                    <div class="stat-label">Total Commission</div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="stat-card warning">
                    <i class="fas fa-check-circle stat-icon" style="color: var(--warning-color);"></i>
                    <div class="stat-value"><?= number_format($stats['completed_bookings'] ?? 0) ?></div>
                    <div class="stat-label">Completed Jobs</div>
                </div>
            </div>
        </div>

        <!-- Revenue Breakdown -->
        <div class="row mb-4">
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-chart-line"></i> Revenue Trend
                    </div>
                    <div class="card-body">
                        <div style="height: 300px;">
                            <canvas id="financeRevenueTrendChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-chart-pie"></i> Payment Methods Distribution
                    </div>
                    <div class="card-body">
                        <div style="height: 300px;">
                            <canvas id="financePaymentMethodsChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Export Options -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-download"></i> Export Reports
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 mb-2">
                                <button class="btn btn-success w-100" id="exportExcelBtn" type="button">
                                    <i class="fas fa-file-excel"></i> Export to Excel
                                </button>
                            </div>
                            <div class="col-md-4 mb-2">
                                <button class="btn btn-danger w-100" id="exportPdfBtn" type="button">
                                    <i class="fas fa-file-pdf"></i> Export to PDF
                                </button>
                            </div>
                            <div class="col-md-4 mb-2">
                                <button class="btn btn-primary w-100" id="printReportBtn" type="button">
                                    <i class="fas fa-print"></i> Print Report
                                </button>
                            </div>
                        </div>
                        <p class="text-muted mt-3 mb-0"><small>Exports include summary stats, daily revenue trend, and payment methods.</small></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php
    $dailyRows = $analytics['daily_collections'] ?? [];
    $paymentMethodRows = $analytics['payment_methods'] ?? [];

    $revenueLabels = array_map(
        static fn($row) => date('M d', strtotime($row['report_date'] ?? 'now')),
        $dailyRows
    );
    $revenueValues = array_map(
        static fn($row) => (float) ($row['total_amount'] ?? 0),
        $dailyRows
    );

    $methodLabels = array_map(
        static fn($row) => ucwords(str_replace('_', ' ', $row['payment_method'] ?? 'unknown')),
        $paymentMethodRows
    );
    $methodValues = array_map(
        static fn($row) => (int) ($row['payment_count'] ?? 0),
        $paymentMethodRows
    );
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
    (function() {
        const revenueLabels = <?= json_encode($revenueLabels) ?>;
        const revenueValues = <?= json_encode($revenueValues) ?>;
        const methodLabels = <?= json_encode($methodLabels) ?>;
        const methodValues = <?= json_encode($methodValues) ?>;

        const renderEmptyMessage = (canvasId, message) => {
            const canvas = document.getElementById(canvasId);
            if (!canvas) return;

            const wrapper = canvas.parentElement;
            if (!wrapper) return;

            wrapper.innerHTML = '<div class="alert alert-info mb-0"><i class="fas fa-info-circle"></i> ' + message + '</div>';
        };

        if (revenueValues.length > 0 && revenueValues.some(v => Number(v) > 0)) {
            const ctx = document.getElementById('financeRevenueTrendChart');
            if (ctx) {
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: revenueLabels,
                        datasets: [{
                            label: 'Daily Revenue',
                            data: revenueValues,
                            borderColor: '#4e73df',
                            backgroundColor: 'rgba(78, 115, 223, 0.1)',
                            borderWidth: 2,
                            fill: true,
                            tension: 0.4,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: true,
                                position: 'top',
                            },
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: (value) => 'PHP ' + Number(value).toLocaleString(),
                                },
                            },
                        },
                    },
                });
            }
        } else {
            renderEmptyMessage('financeRevenueTrendChart', 'No revenue entries yet.');
        }

        if (methodValues.length > 0 && methodValues.some(v => Number(v) > 0)) {
            const ctx = document.getElementById('financePaymentMethodsChart');
            if (ctx) {
                new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: methodLabels,
                        datasets: [{
                            data: methodValues,
                            backgroundColor: ['#1e3c72', '#17a2b8', '#1cc88a', '#f6c23e', '#e74c3c'],
                            borderWidth: 1,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                            },
                        },
                    },
                });
            }
        } else {
            renderEmptyMessage('financePaymentMethodsChart', 'No payment method data yet.');
        }

        const exportRows = revenueLabels.map((label, index) => ({
            date: label,
            revenue: Number(revenueValues[index] || 0),
        }));

        const methodRows = methodLabels.map((label, index) => ({
            method: label,
            count: Number(methodValues[index] || 0),
        }));

        const csvEscape = (value) => {
            const raw = String(value ?? '');
            const escaped = raw.replace(/"/g, '""');
            return '"' + escaped + '"';
        };

        const downloadCsv = () => {
            const lines = [];
            lines.push('Financial Reports');
            lines.push('Generated At,' + new Date().toLocaleString());
            lines.push('');
            lines.push('Summary');
            lines.push('Metric,Value');
            lines.push('Total Revenue,' + csvEscape('PHP <?= number_format((float) ($stats['total_revenue'] ?? 0), 2) ?>'));
            lines.push('Monthly Revenue,' + csvEscape('PHP <?= number_format((float) ($stats['monthly_revenue'] ?? 0), 2) ?>'));
            lines.push('Total Commission,' + csvEscape('PHP <?= number_format((float) ($stats['total_commission'] ?? 0), 2) ?>'));
            lines.push('Completed Jobs,' + csvEscape('<?= (int) ($stats['completed_bookings'] ?? 0) ?>'));
            lines.push('');
            lines.push('Revenue Trend');
            lines.push('Date,Revenue');

            if (exportRows.length === 0) {
                lines.push('No data,0');
            } else {
                exportRows.forEach((row) => {
                    lines.push(csvEscape(row.date) + ',' + csvEscape(row.revenue.toFixed(2)));
                });
            }

            lines.push('');
            lines.push('Payment Methods');
            lines.push('Method,Count');
            if (methodRows.length === 0) {
                lines.push('No data,0');
            } else {
                methodRows.forEach((row) => {
                    lines.push(csvEscape(row.method) + ',' + csvEscape(row.count));
                });
            }

            const blob = new Blob([lines.join('\n')], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = 'finance-reports-' + new Date().toISOString().slice(0, 10) + '.csv';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(url);
        };

        const openPrintableReport = () => {
            const reportWindow = window.open('', '_blank', 'width=900,height=700');
            if (!reportWindow) {
                alert('Please allow pop-ups to export PDF/print report.');
                return;
            }

            const revenueRowsHtml = exportRows.length
                ? exportRows.map((row) => '<tr><td>' + row.date + '</td><td style="text-align:right;">PHP ' + row.revenue.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + '</td></tr>').join('')
                : '<tr><td colspan="2">No revenue data</td></tr>';

            const methodRowsHtml = methodRows.length
                ? methodRows.map((row) => '<tr><td>' + row.method + '</td><td style="text-align:right;">' + row.count + '</td></tr>').join('')
                : '<tr><td colspan="2">No payment method data</td></tr>';

            reportWindow.document.write('<!doctype html><html><head><title>Finance Report</title><style>body{font-family:Segoe UI,Tahoma,sans-serif;padding:24px;color:#222}h1{margin:0 0 8px}p{margin:0 0 16px;color:#555}table{width:100%;border-collapse:collapse;margin-top:8px;margin-bottom:20px}th,td{border:1px solid #ddd;padding:8px;text-align:left}th{background:#f5f7fb} .grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px;margin:12px 0 20px} .card{border:1px solid #ddd;border-radius:8px;padding:10px;background:#fff}</style></head><body>');
            reportWindow.document.write('<h1>Financial Reports</h1><p>Generated: ' + new Date().toLocaleString() + '</p>');
            reportWindow.document.write('<div class="grid"><div class="card"><strong>Total Revenue</strong><br>PHP <?= number_format((float) ($stats['total_revenue'] ?? 0), 2) ?></div><div class="card"><strong>Monthly Revenue</strong><br>PHP <?= number_format((float) ($stats['monthly_revenue'] ?? 0), 2) ?></div><div class="card"><strong>Total Commission</strong><br>PHP <?= number_format((float) ($stats['total_commission'] ?? 0), 2) ?></div><div class="card"><strong>Completed Jobs</strong><br><?= (int) ($stats['completed_bookings'] ?? 0) ?></div></div>');
            reportWindow.document.write('<h3>Revenue Trend</h3><table><thead><tr><th>Date</th><th style="text-align:right;">Revenue</th></tr></thead><tbody>' + revenueRowsHtml + '</tbody></table>');
            reportWindow.document.write('<h3>Payment Methods</h3><table><thead><tr><th>Method</th><th style="text-align:right;">Count</th></tr></thead><tbody>' + methodRowsHtml + '</tbody></table>');
            reportWindow.document.write('</body></html>');
            reportWindow.document.close();
            return reportWindow;
        };

        const exportExcelBtn = document.getElementById('exportExcelBtn');
        const exportPdfBtn = document.getElementById('exportPdfBtn');
        const printReportBtn = document.getElementById('printReportBtn');

        if (exportExcelBtn) {
            exportExcelBtn.addEventListener('click', downloadCsv);
        }

        if (exportPdfBtn) {
            exportPdfBtn.addEventListener('click', () => {
                const reportWindow = openPrintableReport();
                if (reportWindow) {
                    reportWindow.focus();
                    reportWindow.print();
                }
            });
        }

        if (printReportBtn) {
            printReportBtn.addEventListener('click', () => {
                const reportWindow = openPrintableReport();
                if (reportWindow) {
                    reportWindow.focus();
                    reportWindow.print();
                }
            });
        }
    })();
</script>

<?= view('layouts/page_footer') ?>

