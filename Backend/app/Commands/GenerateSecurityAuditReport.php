<?php

namespace App\Commands;

use App\Libraries\SecurityEmailNotifier;
use App\Models\BlockedIPModel;
use App\Models\SecurityAuditReportModel;
use App\Models\SecurityEventModel;
use App\Models\SecurityNotificationModel;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class GenerateSecurityAuditReport extends BaseCommand
{
    protected $group = 'App';
    protected $name = 'security:report-generate';
    protected $description = 'Generate periodic security audit report summary and save it to DB and writable/reports/security.';
    protected $usage = 'security:report-generate [--period daily|weekly|monthly] [--notify]';
    protected $options = [
        '--period' => 'Report period: daily (default), weekly, or monthly.',
        '--notify' => 'Email the generated report summary to admin recipients.',
    ];

    public function run(array $params)
    {
        $period = strtolower((string) (CLI::getOption('period') ?? 'daily'));
        $shouldNotify = CLI::getOption('notify') !== null;

        if (!in_array($period, ['daily', 'weekly', 'monthly'], true)) {
            CLI::error('Invalid --period value. Use daily, weekly, or monthly.');
            return;
        }

        [$startDate, $endDate, $statsPeriod] = $this->resolvePeriodWindow($period);

        $eventModel = new SecurityEventModel();
        $notificationModel = new SecurityNotificationModel();
        $blockedIPModel = new BlockedIPModel();
        $reportModel = new SecurityAuditReportModel();

        $events = $eventModel->getEventsByDateRange($startDate, $endDate);
        $eventStats = $eventModel->getEventStatistics($statsPeriod);
        $topThreats = $eventModel->getTopSuspiciousIPs(10, $period === 'daily' ? 24 : ($period === 'weekly' ? 24 * 7 : 24 * 30));

        $failedLogins = (int) ($eventStats['login_failed'] ?? 0);
        $successfulLogins = (int) ($eventStats['login_success'] ?? 0);
        $suspiciousActivities = (int) ($eventStats['suspicious_activity'] ?? 0) + (int) ($eventStats['unauthorized_access'] ?? 0);
        $criticalAlerts = $notificationModel->where('priority', 'critical')->where('is_read', false)->countAllResults();
        $unreadNotifications = $notificationModel->where('is_read', false)->countAllResults();
        $activeBlockedIPs = $blockedIPModel->where('is_active', true)->countAllResults();

        $report = [
            'period' => $period,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'generated_at' => date('Y-m-d H:i:s'),
            'summary' => [
                'total_events' => count($events),
                'failed_logins' => $failedLogins,
                'successful_logins' => $successfulLogins,
                'suspicious_activities' => $suspiciousActivities,
                'blocked_ips' => $activeBlockedIPs,
                'critical_alerts' => $criticalAlerts,
                'unread_notifications' => $unreadNotifications,
            ],
            'events_by_type' => $eventStats,
            'top_threats' => $topThreats,
            'recent_events' => array_slice($events, 0, 50),
        ];

        $reportModel->insert([
            'period' => $period,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'total_events' => count($events),
            'failed_logins' => $failedLogins,
            'successful_logins' => $successfulLogins,
            'suspicious_activities' => $suspiciousActivities,
            'blocked_ips' => $activeBlockedIPs,
            'critical_alerts' => $criticalAlerts,
            'unread_notifications' => $unreadNotifications,
            'summary_json' => json_encode($report, JSON_PRETTY_PRINT),
            'generated_at' => date('Y-m-d H:i:s'),
        ]);

        $reportsDir = WRITEPATH . 'reports/security';
        if (!is_dir($reportsDir)) {
            mkdir($reportsDir, 0755, true);
        }

        $filePath = $reportsDir . '/security-audit-' . $period . '-' . date('Ymd-His') . '.json';
        file_put_contents($filePath, json_encode($report, JSON_PRETTY_PRINT));

        if ($shouldNotify) {
            $notifier = new SecurityEmailNotifier();
            $notifier->sendSecurityAlert(
                'Periodic Security Audit Report Generated',
                "A {$period} security audit report has been generated.\n\n" .
                "Period: {$startDate} to {$endDate}\n" .
                "Total Events: " . count($events) . "\n" .
                "Failed Logins: {$failedLogins}\n" .
                "Suspicious Activities: {$suspiciousActivities}\n" .
                "Active Blocked IPs: {$activeBlockedIPs}\n\n" .
                "Saved to: {$filePath}",
                'medium'
            );
        }

        CLI::write('Security audit report generated successfully.', 'green');
        CLI::write('Period: ' . $period, 'yellow');
        CLI::write('Window: ' . $startDate . ' to ' . $endDate, 'yellow');
        CLI::write('Saved file: ' . $filePath, 'yellow');
        CLI::write('DB record ID: ' . $reportModel->getInsertID(), 'yellow');

        CLI::newLine();
        CLI::write('Suggested scheduler examples:', 'cyan');
        CLI::write('  php spark security:report-generate --period daily --notify', 'white');
        CLI::write('  php spark security:report-generate --period weekly --notify', 'white');
    }

    private function resolvePeriodWindow(string $period): array
    {
        $now = date('Y-m-d H:i:s');

        return match ($period) {
            'weekly' => [
                date('Y-m-d 00:00:00', strtotime('-7 days')),
                $now,
                '7 days',
            ],
            'monthly' => [
                date('Y-m-d 00:00:00', strtotime('-30 days')),
                $now,
                '30 days',
            ],
            default => [
                date('Y-m-d 00:00:00', strtotime('-1 day')),
                $now,
                '24 hours',
            ],
        };
    }
}
