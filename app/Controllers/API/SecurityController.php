<?php

namespace App\Controllers\API;

use App\Controllers\BaseController;
use App\Controllers\SecurityController as BaseSecurityController;
use App\Models\SecurityEventModel;
use App\Models\SecurityNotificationModel;
use App\Models\BlockedIPModel;
use App\Models\UserModel;
use CodeIgniter\API\ResponseTrait;

class SecurityController extends BaseController
{
    use ResponseTrait;
    
    protected $baseSecurityController;
    protected $securityEventModel;
    protected $securityNotificationModel;
    protected $blockedIPModel;
    protected $userModel;
    
    public function __construct()
    {
        $this->baseSecurityController = new BaseSecurityController();
        $this->securityEventModel = new SecurityEventModel();
        $this->securityNotificationModel = new SecurityNotificationModel();
        $this->blockedIPModel = new BlockedIPModel();
        $this->userModel = new UserModel();
    }
    
    /**
     * Get security dashboard data
     */
    public function dashboard()
    {
        $dashboardData = $this->baseSecurityController->getDashboardData();
        
        // Add additional data for API
        $dashboardData['top_threats'] = $this->securityEventModel->getTopSuspiciousIPs(5, 24);
        $dashboardData['recent_alerts'] = $this->securityNotificationModel->getActionRequiredNotifications();
        
        return $this->respond([
            'status' => 'success',
            'data' => $dashboardData
        ]);
    }
    
    /**
     * Get security events with filtering
     */
    public function events()
    {
        $request = service('request');
        $page = $request->getVar('page') ?? 1;
        $limit = $request->getVar('limit') ?? 50;
        $eventType = $request->getVar('event_type');
        $severity = $request->getVar('severity');
        $startDate = $request->getVar('start_date');
        $endDate = $request->getVar('end_date');
        $search = $request->getVar('search');
        
        $builder = $this->securityEventModel;
        
        // Apply filters
        if ($eventType) {
            $builder = $builder->where('event_type', $eventType);
        }
        
        if ($severity) {
            $builder = $builder->where('severity', $severity);
        }
        
        if ($startDate) {
            $builder = $builder->where('created_at >=', $startDate);
        }
        
        if ($endDate) {
            $builder = $builder->where('created_at <=', $endDate);
        }
        
        if ($search) {
            $builder = $builder->groupStart()
                               ->like('ip_address', $search)
                               ->orLike('email', $search)
                               ->orLike('details', $search)
                               ->groupEnd();
        }
        
        $total = $builder->countAllResults(false);
        $events = $builder->orderBy('created_at', 'DESC')
                         ->limit($limit, ($page - 1) * $limit)
                         ->findAll();
        
        return $this->respond([
            'status' => 'success',
            'data' => [
                'events' => $events,
                'pagination' => [
                    'total' => $total,
                    'page' => $page,
                    'limit' => $limit,
                    'pages' => ceil($total / $limit)
                ]
            ]
        ]);
    }
    
    /**
     * Get notifications
     */
    public function notifications()
    {
        $request = service('request');
        $page = $request->getVar('page') ?? 1;
        $limit = $request->getVar('limit') ?? 20;
        $type = $request->getVar('type');
        $priority = $request->getVar('priority');
        $unread = $request->getVar('unread');
        
        // Get current user (assuming JWT authentication)
        $currentUser = $this->getCurrentUser();
        
        $builder = $this->securityNotificationModel;
        
        if ($currentUser && $currentUser['user_type'] === 'admin') {
            $builder = $builder->where('admin_id', $currentUser['id']);
        }
        
        if ($type) {
            $builder = $builder->where('type', $type);
        }
        
        if ($priority) {
            $builder = $builder->where('priority', $priority);
        }
        
        if ($unread !== null) {
            $builder = $builder->where('is_read', $unread === 'true');
        }
        
        $total = $builder->countAllResults(false);
        $notifications = $builder->orderBy('created_at', 'DESC')
                                ->limit($limit, ($page - 1) * $limit)
                                ->findAll();
        
        return $this->respond([
            'status' => 'success',
            'data' => [
                'notifications' => $notifications,
                'pagination' => [
                    'total' => $total,
                    'page' => $page,
                    'limit' => $limit,
                    'pages' => ceil($total / $limit)
                ]
            ]
        ]);
    }
    
    /**
     * Mark notification as read
     */
    public function markNotificationRead($id)
    {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser) {
            return $this->failUnauthorized();
        }
        
        $notification = $this->securityNotificationModel->find($id);
        
        if (!$notification) {
            return $this->failNotFound('Notification not found');
        }
        
        // Check if user owns this notification (admin check)
        if ($currentUser['user_type'] === 'admin' && $notification['admin_id'] != $currentUser['id']) {
            return $this->failForbidden('Access denied');
        }
        
        $this->securityNotificationModel->markAsRead($id);
        
        return $this->respond([
            'status' => 'success',
            'message' => 'Notification marked as read'
        ]);
    }
    
    /**
     * Mark all notifications as read
     */
    public function markAllNotificationsRead()
    {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser) {
            return $this->failUnauthorized();
        }
        
        if ($currentUser['user_type'] === 'admin') {
            $this->securityNotificationModel->markAllAsRead($currentUser['id']);
        }
        
        return $this->respond([
            'status' => 'success',
            'message' => 'All notifications marked as read'
        ]);
    }
    
    /**
     * Get blocked IPs
     */
    public function blockedIPs()
    {
        $request = service('request');
        $page = $request->getVar('page') ?? 1;
        $limit = $request->getVar('limit') ?? 20;
        $search = $request->getVar('search');
        
        $builder = $this->blockedIPModel;
        
        if ($search) {
            $builder = $builder->searchBlockedIPs($search, 1000);
        } else {
            $builder = $builder->getActiveBlockedIPs(1000);
        }
        
        $total = count($builder);
        $blockedIPs = array_slice($builder, ($page - 1) * $limit, $limit);
        
        return $this->respond([
            'status' => 'success',
            'data' => [
                'blocked_ips' => $blockedIPs,
                'pagination' => [
                    'total' => $total,
                    'page' => $page,
                    'limit' => $limit,
                    'pages' => ceil($total / $limit)
                ]
            ]
        ]);
    }
    
    /**
     * Unblock IP
     */
    public function unblockIP($id)
    {
        $currentUser = $this->getCurrentUser();
        
        if (!$currentUser || $currentUser['user_type'] !== 'admin') {
            return $this->failUnauthorized();
        }
        
        $blockedIP = $this->blockedIPModel->find($id);
        
        if (!$blockedIP) {
            return $this->failNotFound('Blocked IP not found');
        }
        
        $this->blockedIPModel->unblockIPById($id);
        
        // Log the unblock action
        $this->baseSecurityController->logEvent(
            'account_unlocked',
            'medium',
            'IP unblocked: ' . $blockedIP['ip_address'] . ' by admin ' . $currentUser['email'],
            $currentUser['id'],
            $currentUser['email']
        );
        
        return $this->respond([
            'status' => 'success',
            'message' => 'IP unblocked successfully'
        ]);
    }
    
    /**
     * Get security statistics
     */
    public function statistics()
    {
        $request = service('request');
        $period = $request->getVar('period') ?? 'daily';
        
        $stats = $this->baseSecurityController->getSecurityStats($period);
        
        // Get additional statistics
        $eventStats = $this->securityEventModel->getEventStatistics('24 hours');
        $blockStats = $this->blockedIPModel->getBlockStatistics();
        $notificationStats = $this->securityNotificationModel->getNotificationStats();
        
        return $this->respond([
            'status' => 'success',
            'data' => [
                'chart_data' => $stats,
                'event_stats' => $eventStats,
                'block_stats' => $blockStats,
                'notification_stats' => $notificationStats
            ]
        ]);
    }
    
    /**
     * Get security report
     */
    public function report()
    {
        $request = service('request');
        $period = $request->getVar('period') ?? 'daily';
        $format = $request->getVar('format') ?? 'json';
        
        $startDate = match($period) {
            'daily' => date('Y-m-d 00:00:00'),
            'weekly' => date('Y-m-d 00:00:00', strtotime('-7 days')),
            'monthly' => date('Y-m-d 00:00:00', strtotime('-30 days')),
            default => date('Y-m-d 00:00:00')
        };
        
        $endDate = date('Y-m-d 23:59:59');
        
        // Get report data
        $events = $this->securityEventModel->getEventsByDateRange($startDate, $endDate);
        $stats = $this->securityEventModel->getEventStatistics($period);
        $topThreats = $this->securityEventModel->getTopSuspiciousIPs(10, 24);
        
        $report = [
            'period' => $period,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'summary' => [
                'total_events' => count($events),
                'failed_logins' => $stats['login_failed'] ?? 0,
                'successful_logins' => $stats['login_success'] ?? 0,
                'suspicious_activities' => ($stats['suspicious_activity'] ?? 0) + ($stats['unauthorized_access'] ?? 0),
                'blocked_ips' => $this->blockedIPModel->where('is_active', true)->countAllResults()
            ],
            'events_by_type' => $stats,
            'top_threats' => $topThreats,
            'recent_events' => array_slice($events, 0, 50)
        ];
        
        if ($format === 'pdf') {
            // For now, return JSON. PDF export will be implemented separately
            return $this->respond([
                'status' => 'success',
                'data' => $report,
                'message' => 'PDF export will be implemented separately'
            ]);
        }
        
        return $this->respond([
            'status' => 'success',
            'data' => $report
        ]);
    }
    
    /**
     * Get current authenticated user
     */
    private function getCurrentUser()
    {
        // This should implement JWT token extraction and validation
        // For now, return null as placeholder
        return null;
    }
}
