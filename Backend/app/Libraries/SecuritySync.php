<?php

namespace App\Libraries;

use CodeIgniter\Database\BaseConnection;

class SecuritySync
{
    protected $db;
    protected $lastSyncTime;
    
    public function __construct()
    {
        $this->db = \Config\Database::connect();
        $this->lastSyncTime = time();
    }
    
    /**
     * Get real-time security data with timestamp
     */
    public function getRealtimeData()
    {
        $currentTime = time();
        $data = [
            'timestamp' => $currentTime,
            'security_events' => $this->getLatestSecurityEvents(),
            'notifications' => $this->getLatestNotifications(),
            'statistics' => $this->getCurrentStatistics(),
            'blocked_ips' => $this->getLatestBlockedIPs()
        ];
        
        $this->lastSyncTime = $currentTime;
        return $data;
    }
    
    /**
     * Get latest security events since last sync
     */
    private function getLatestSecurityEvents()
    {
        return $this->db->table('security_events')
            ->orderBy('created_at', 'DESC')
            ->limit(20)
            ->get()
            ->getResultArray();
    }
    
    /**
     * Get latest notifications since last sync
     */
    private function getLatestNotifications()
    {
        return $this->db->table('security_notifications')
            ->where('is_read', false)
            ->orderBy('created_at', 'DESC')
            ->limit(10)
            ->get()
            ->getResultArray();
    }
    
    /**
     * Get current statistics
     */
    public function getCurrentStatistics()
    {
        return [
            'total_events' => $this->db->table('security_events')->countAllResults(),
            'failed_logins' => $this->db->table('security_events')->where('event_type', 'login_failed')->countAllResults(),
            'successful_logins' => $this->db->table('security_events')->where('event_type', 'login_success')->countAllResults(),
            'blocked_ips' => $this->db->table('blocked_ips')->where('is_active', true)->countAllResults(),
            'unread_notifications' => $this->db->table('security_notifications')->where('is_read', false)->countAllResults(),
            'critical_alerts' => $this->db->table('security_notifications')->where('priority', 'critical')->where('is_read', false)->countAllResults()
        ];
    }
    
    /**
     * Get latest blocked IPs
     */
    private function getLatestBlockedIPs()
    {
        return $this->db->table('blocked_ips')
            ->where('is_active', true)
            ->orderBy('created_at', 'DESC')
            ->limit(10)
            ->get()
            ->getResultArray();
    }
    
    /**
     * Check for new events since timestamp
     */
    public function hasNewEvents($sinceTimestamp)
    {
        $newEvents = $this->db->table('security_events')
            ->where('UNIX_TIMESTAMP(created_at) >', $sinceTimestamp)
            ->countAllResults();
            
        return $newEvents > 0;
    }
    
    /**
     * Get incremental updates
     */
    public function getIncrementalUpdates($sinceTimestamp)
    {
        return [
            'new_events' => $this->db->table('security_events')
                ->where('UNIX_TIMESTAMP(created_at) >', $sinceTimestamp)
                ->orderBy('created_at', 'DESC')
                ->get()
                ->getResultArray(),
            'new_notifications' => $this->db->table('security_notifications')
                ->where('UNIX_TIMESTAMP(created_at) >', $sinceTimestamp)
                ->orderBy('created_at', 'DESC')
                ->get()
                ->getResultArray(),
            'updated_stats' => $this->getCurrentStatistics()
        ];
    }
}
