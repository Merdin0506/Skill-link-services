<?php
namespace App\Controllers;

use App\Models\AuditLogModel;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class AuditReport extends BaseCommand
{
    protected $group       = 'Security';
    protected $name        = 'audit:report';
    protected $description = 'Generate and email a periodic audit report.';

    public function run(array $params)
    {
        $auditLog = new AuditLogModel();
        $sevenDaysAgo = date('Y-m-d H:i:s', strtotime('-7 days'));
        $now = date('Y-m-d H:i:s');

        // Metrics
        $failedLogins = $auditLog->where('event_type', 'failed_login')->where('created_at >=', $sevenDaysAgo)->countAllResults();
        $securityBreaches = $auditLog->where('event_type', 'security_breach')->where('created_at >=', $sevenDaysAgo)->countAllResults();

        // Top offending IPs
        $db = \Config\Database::connect();
        $topIps = $db->table('audit_logs')
            ->select('ip_address, COUNT(*) as attempts')
            ->where('event_type', 'failed_login')
            ->where('created_at >=', $sevenDaysAgo)
            ->groupBy('ip_address')
            ->orderBy('attempts', 'DESC')
            ->limit(5)
            ->get()->getResultArray();

        // Recent alerts
        $recentAlerts = $auditLog->where('event_type', 'security_breach')->where('created_at >=', $sevenDaysAgo)->orderBy('created_at', 'DESC')->findAll(5);

        // Format report
        $report = "Security Audit Report (Last 7 Days)\n";
        $report .= "Generated: $now\n\n";
        $report .= "Failed Login Attempts: $failedLogins\n";
        $report .= "Security Breaches: $securityBreaches\n\n";
        $report .= "Top Offending IPs (Failed Logins):\n";
        foreach ($topIps as $ip) {
            $report .= "- {$ip['ip_address']}: {$ip['attempts']} attempts\n";
        }
        $report .= "\nRecent Security Breaches:\n";
        foreach ($recentAlerts as $alert) {
            $report .= "- [{$alert['created_at']}] {$alert['user_email']} | {$alert['ip_address']} | {$alert['details']}\n";
        }

        // Email report
        $email = \Config\Services::email();
        $adminEmail = 'admin@skilllink.com';
        $email->setTo($adminEmail);
        $email->setSubject('Weekly Security Audit Report');
        $email->setMessage($report);
        @$email->send();

        CLI::write("Audit report generated and sent to $adminEmail.", 'green');
    }
}
