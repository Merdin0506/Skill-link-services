<?php

namespace App\Models;

use CodeIgniter\Model;

class SecurityAuditReportModel extends Model
{
    protected $table = 'security_audit_reports';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'period',
        'start_date',
        'end_date',
        'total_events',
        'failed_logins',
        'successful_logins',
        'suspicious_activities',
        'blocked_ips',
        'critical_alerts',
        'unread_notifications',
        'summary_json',
        'generated_at',
    ];

    protected $useTimestamps = false;
    protected $returnType = 'array';
}
