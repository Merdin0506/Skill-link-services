<?php
namespace App\Libraries;

use App\Models\AuditLogModel;

class AuditLogger
{
    /**
     * Log an audit event and check for suspicious activity.
     * Sends an email notification to admin if suspicious activity is detected.
     */
    public static function log($eventType, $userEmail = null, $details = null)
    {
        $auditLog = new AuditLogModel();
        $ip = service('request')->getIPAddress();
        $userAgent = service('request')->getUserAgent();
        $now = date('Y-m-d H:i:s');
        $auditLog->insert([
            'event_type' => $eventType,
            'user_email' => $userEmail,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'details' => $details,
            'created_at' => $now,
        ]);

        // Check for suspicious activity: 5+ failed logins from same IP or email in last 10 minutes
        if ($eventType === 'failed_login') {
            $tenMinutesAgo = date('Y-m-d H:i:s', strtotime('-10 minutes'));
            $count = $auditLog
                ->where('event_type', 'failed_login')
                ->groupStart()
                    ->where('ip_address', $ip)
                    ->orWhere('user_email', $userEmail)
                ->groupEnd()
                ->where('created_at >=', $tenMinutesAgo)
                ->countAllResults();
            if ($count >= 5) {
                self::notifyAdmin($ip, $userEmail, $count);
            }
        }
    }

    /**
     * Send an email notification to the admin for suspicious activity.
     */
    protected static function notifyAdmin($ip, $userEmail, $count)
    {
        $email = \Config\Services::email();
        $adminEmail = 'admin@skilllink.com'; // Change if needed
        $email->setTo($adminEmail);
        $email->setSubject('Suspicious Activity Detected');
        $body = "Suspicious activity detected:\n\n" .
            "IP Address: $ip\n" .
            "User Email: $userEmail\n" .
            "Failed Attempts (last 10 min): $count\n" .
            "Time: " . date('Y-m-d H:i:s') . "\n\n" .
            "Please review the audit logs for more details.";
        $email->setMessage($body);
        @$email->send(); // Suppress errors to avoid breaking login flow
    }
}
