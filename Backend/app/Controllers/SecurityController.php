<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\SecurityEventModel;
use App\Models\SecurityNotificationModel;
use App\Models\BlockedIPModel;
use App\Models\UserModel;
use App\Libraries\SecurityEmailNotifier;
use CodeIgniter\API\ResponseTrait;

class SecurityController extends BaseController
{
    use ResponseTrait;
    
    protected $securityEventModel;
    protected $securityNotificationModel;
    protected $blockedIPModel;
    protected $userModel;
    protected $emailNotifier;
    
    public function __construct()
    {
        $this->securityEventModel = new SecurityEventModel();
        $this->securityNotificationModel = new SecurityNotificationModel();
        $this->blockedIPModel = new BlockedIPModel();
        $this->userModel = new UserModel();
        $this->emailNotifier = new SecurityEmailNotifier();
    }

    /**
     * Render the security dashboard page.
     */
    public function dashboard()
    {
        return view('security/dashboard', [
            'dashboardData' => $this->getDashboardData(),
            'apiToken' => session()->get('api_token'),
            'securityNavActive' => 'dashboard',
        ]);
    }

    /**
     * Render audit logs page.
     */
    public function auditLogs()
    {
        return view('security/audit_logs', [
            'apiToken' => session()->get('api_token'),
            'securityNavActive' => 'audit',
        ]);
    }

    /**
     * Render reports page.
     */
    public function reports()
    {
        return view('security/reports', [
            'dashboardData' => $this->getDashboardData(),
            'apiToken' => session()->get('api_token'),
            'securityNavActive' => 'reports',
        ]);
    }

    /**
     * Render notifications page.
     */
    public function notifications()
    {
        $adminId = session()->get('user_id');

        $notifications = [];
        if (is_numeric($adminId)) {
            $notifications = $this->securityNotificationModel
                ->where('admin_id', (int) $adminId)
                ->orderBy('created_at', 'DESC')
                ->limit(50)
                ->findAll();
        }

        return view('security/notifications', [
            'notifications' => $notifications,
            'securityNavActive' => 'notifications',
        ]);
    }

    /**
     * Render blocked IPs page.
     */
    public function blockedIps()
    {
        $blockedIps = $this->blockedIPModel
            ->orderBy('created_at', 'DESC')
            ->limit(100)
            ->findAll();

        return view('security/blocked_ips', [
            'blockedIps' => $blockedIps,
            'securityNavActive' => 'blocked',
        ]);
    }

    /**
     * Unblock an IP.
     */
    public function unblockIp(int $id)
    {
        $this->blockedIPModel->update($id, [
            'is_active' => false,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return redirect()->to('/security/blocked-ips')->with('success', 'IP address has been unblocked.');
    }

    /**
     * Render security settings page.
     */
    public function settings()
    {
        return view('security/settings', [
            'securityNavActive' => 'settings',
        ]);
    }
    
    /**
     * Log security event
     */
    public function logEvent($eventType, $severity = 'medium', $details = null, $userId = null, $email = null)
    {
        $request = service('request');
        $ipAddress = $request->getIPAddress();
        
        $data = [
            'user_id' => $userId,
            'email' => $email,
            'event_type' => $eventType,
            'severity' => $severity,
            'ip_address' => $ipAddress,
            'user_agent' => method_exists($request, 'getUserAgent') ? $request->getUserAgent() : 'CLI/Unknown',
            'request_uri' => $request->getUri()->getPath(),
            'request_method' => $request->getMethod(),
            'details' => $details,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $this->securityEventModel->insert($data);
        
        // Check for suspicious patterns
        $this->detectSuspiciousActivity($ipAddress, $eventType, $userId);
        
        return $this->securityEventModel->getInsertID();
    }
    
    /**
     * Detect suspicious activity patterns
     */
    private function detectSuspiciousActivity($ipAddress, $eventType, $userId = null)
    {
        // Check for brute force attempts (5 failed logins in 1 minute)
        if ($eventType === 'login_failed') {
            $recentFailures = $this->securityEventModel
                ->where('event_type', 'login_failed')
                ->where('ip_address', $ipAddress)
                ->where('created_at >=', date('Y-m-d H:i:s', strtotime('-1 minute')))
                ->countAllResults();
                
            if ($recentFailures >= 5) {
                $this->handleBruteForce($ipAddress, $userId);
            }
        }
        
        // Check for repeated suspicious activities from same IP
        $suspiciousCount = $this->securityEventModel
            ->whereIn('event_type', ['login_failed', 'unauthorized_access', 'suspicious_activity'])
            ->where('ip_address', $ipAddress)
            ->where('created_at >=', date('Y-m-d H:i:s', strtotime('-5 minutes')))
            ->countAllResults();
            
        if ($suspiciousCount >= 10) {
            $this->blockIP($ipAddress, 'Multiple suspicious activities detected', true, '+1 hour');

            $this->createNotification(
                'Multiple Suspicious Activities Detected',
                "10 or more suspicious activities detected from IP: {$ipAddress} within 5 minutes. The IP has been blocked for 1 hour.",
                'warning',
                'high',
                true,
                $userId,
                $ipAddress
            );
            
            // Send email notification for repeated suspicious activity
            $this->emailNotifier->sendSecurityAlert(
                'Multiple Suspicious Activities Detected',
                "10 or more suspicious activities detected from IP: {$ipAddress} within 5 minutes. The IP has been blocked for 1 hour.",
                'high'
            );
        }
        
        // Check for admin account suspicious activities
        if ($userId && $eventType === 'login_success') {
            $user = $this->userModel->find($userId);
            if ($user && in_array(($user['user_type'] ?? ''), ['admin', 'super_admin'], true)) {
                // Check if admin is logging from unusual location
                $recentAdminLogins = $this->securityEventModel
                    ->where('event_type', 'login_success')
                    ->where('user_id', $userId)
                    ->where('ip_address !=', $ipAddress)
                    ->where('created_at >=', date('Y-m-d H:i:s', strtotime('-24 hours')))
                    ->countAllResults();
                    
                if ($recentAdminLogins === 0) {
                    // First admin login from this IP in 24 hours
                    $this->createNotification(
                        'Admin Login from New Location',
                        "Admin user {$user['email']} logged in from new IP: {$ipAddress}",
                        'warning',
                        'high',
                        true,
                        $userId,
                        $ipAddress
                    );
                }
            }
        }
    }
    
    /**
     * Handle brute force attack
     */
    private function handleBruteForce($ipAddress, $userId = null)
    {
        $this->blockIP($ipAddress, 'Brute force attack detected', true, '+30 minutes');
        
        $this->createNotification(
            'Brute Force Attack Detected',
            "5 or more failed login attempts detected from IP: {$ipAddress}",
            'critical',
            'critical',
            true,
            $userId,
            $ipAddress
        );
        
        // Send email notification
        $this->emailNotifier->sendCriticalAlert(
            'Brute Force Attack Detected',
            "A brute force attack has been detected from IP address: {$ipAddress}. The IP has been temporarily blocked for 30 minutes.",
            $ipAddress,
            "Multiple failed login attempts triggered automatic IP blocking."
        );
    }
    
    /**
     * Block IP address
     */
    public function blockIP($ipAddress, $reason, $isTemporary = true, $duration = '+1 hour')
    {
        $blockedUntil = $isTemporary ? date('Y-m-d H:i:s', strtotime($duration)) : null;
        
        // Check if IP is already blocked
        $existing = $this->blockedIPModel
            ->where('ip_address', $ipAddress)
            ->where('is_active', true)
            ->first();
            
        if ($existing) {
            // Update existing block
            $this->blockedIPModel->update($existing['id'], [
                'attempts_count' => $existing['attempts_count'] + 1,
                'last_attempt' => date('Y-m-d H:i:s'),
                'blocked_until' => $blockedUntil
            ]);
        } else {
            // Create new block
            $this->blockedIPModel->insert([
                'ip_address' => $ipAddress,
                'reason' => $reason,
                'is_temporary' => $isTemporary,
                'blocked_until' => $blockedUntil,
                'attempts_count' => 1,
                'last_attempt' => date('Y-m-d H:i:s'),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
        }
        
        // Mark related events as blocked
        $this->securityEventModel
            ->where('ip_address', $ipAddress)
            ->where('created_at >=', date('Y-m-d H:i:s', strtotime('-5 minutes')))
            ->set(['is_blocked' => true, 'block_reason' => $reason])
            ->update();
    }
    
    /**
     * Create security notification
     */
    public function createNotification($title, $message, $type = 'info', $priority = 'medium', $actionRequired = false, $relatedUserId = null, $ipAddress = null)
    {
        // Get all admin users
        $admins = $this->userModel->whereIn('user_type', ['admin', 'super_admin'])->findAll();
        
        foreach ($admins as $admin) {
            $data = [
                'admin_id' => $admin['id'],
                'title' => $title,
                'message' => $message,
                'type' => $type,
                'priority' => $priority,
                'action_required' => $actionRequired,
                'related_user_id' => $relatedUserId,
                'ip_address' => $ipAddress,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $this->securityNotificationModel->insert($data);
        }
    }
    
    /**
     * Check if IP is blocked
     */
    public function isIPBlocked($ipAddress)
    {
        $blocked = $this->blockedIPModel
            ->where('ip_address', $ipAddress)
            ->where('is_active', true)
            ->groupStart()
                ->where('is_temporary', false)
                ->orGroupStart()
                    ->where('is_temporary', true)
                    ->where('blocked_until >', date('Y-m-d H:i:s'))
                ->groupEnd()
            ->groupEnd()
            ->first();
            
        return !empty($blocked);
    }
    
    /**
     * Get security dashboard data
     */
    public function getDashboardData()
    {
        $data = [
            'total_events' => $this->securityEventModel->countAllResults(),
            'failed_logins' => $this->securityEventModel->where('event_type', 'login_failed')->countAllResults(),
            'successful_logins' => $this->securityEventModel->where('event_type', 'login_success')->countAllResults(),
            'blocked_ips' => $this->blockedIPModel->where('is_active', true)->countAllResults(),
            'unread_notifications' => $this->securityNotificationModel->where('is_read', false)->countAllResults(),
            'critical_alerts' => $this->securityNotificationModel->where('priority', 'critical')->where('is_read', false)->countAllResults(),
            'recent_events' => $this->securityEventModel->orderBy('created_at', 'DESC')->limit(10)->findAll(),
            'recent_notifications' => $this->securityNotificationModel->orderBy('created_at', 'DESC')->limit(5)->findAll(),
        ];
        
        return $data;
    }
    
    /**
     * Get security statistics for reports
     */
    public function getSecurityStats($period = 'daily')
    {
        $dateFormat = $period === 'daily' ? '%Y-%m-%d' : ($period === 'weekly' ? '%Y-%u' : '%Y-%m');
        
        $stats = $this->securityEventModel
            ->select("DATE_FORMAT(created_at, '{$dateFormat}') as period, event_type, COUNT(*) as count")
            ->groupBy("period, event_type")
            ->orderBy('period', 'DESC')
            ->limit(30)
            ->findAll();
            
        return $stats;
    }
    
    /**
     * Mark notification as read
     */
    public function markNotificationRead($notificationId)
    {
        $this->securityNotificationModel->update($notificationId, [
            'is_read' => true,
            'read_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * Mark all notifications as read
     */
    public function markAllNotificationsRead($adminId)
    {
        $this->securityNotificationModel
            ->where('admin_id', $adminId)
            ->where('is_read', false)
            ->set(['is_read' => true, 'read_at' => date('Y-m-d H:i:s')])
            ->update();
    }
}
