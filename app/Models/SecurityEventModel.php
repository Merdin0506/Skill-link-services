<?php

namespace App\Models;

use CodeIgniter\Model;

class SecurityEventModel extends Model
{
    protected $table = 'security_events';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'user_id', 'email', 'event_type', 'severity', 'ip_address', 
        'user_agent', 'request_uri', 'request_method', 'details', 
        'is_blocked', 'block_reason', 'created_at'
    ];
    protected $useTimestamps = false;
    
    protected $returnType = 'array';
    
    /**
     * Get events by date range
     */
    public function getEventsByDateRange($startDate, $endDate, $eventType = null)
    {
        $builder = $this->where('created_at >=', $startDate)
                       ->where('created_at <=', $endDate);
                       
        if ($eventType) {
            $builder->where('event_type', $eventType);
        }
        
        return $builder->orderBy('created_at', 'DESC')->findAll();
    }
    
    /**
     * Get events by IP address
     */
    public function getEventsByIP($ipAddress, $limit = 100)
    {
        return $this->where('ip_address', $ipAddress)
                   ->orderBy('created_at', 'DESC')
                   ->limit($limit)
                   ->findAll();
    }
    
    /**
     * Get events by user
     */
    public function getEventsByUser($userId, $limit = 100)
    {
        return $this->where('user_id', $userId)
                   ->orderBy('created_at', 'DESC')
                   ->limit($limit)
                   ->findAll();
    }
    
    /**
     * Get event statistics
     */
    public function getEventStatistics($period = '24 hours')
    {
        $timeFilter = date('Y-m-d H:i:s', strtotime('-' . $period));
        
        $stats = $this->select('event_type, COUNT(*) as count')
                     ->where('created_at >=', $timeFilter)
                     ->groupBy('event_type')
                     ->findAll();
                     
        $result = [];
        foreach ($stats as $stat) {
            $result[$stat['event_type']] = $stat['count'];
        }
        
        return $result;
    }
    
    /**
     * Get top suspicious IPs
     */
    public function getTopSuspiciousIPs($limit = 10, $hours = 24)
    {
        $timeFilter = date('Y-m-d H:i:s', strtotime('-' . $hours . ' hours'));
        
        return $this->select('ip_address, COUNT(*) as event_count, MAX(created_at) as last_activity')
                     ->whereIn('event_type', ['login_failed', 'unauthorized_access', 'suspicious_activity'])
                     ->where('created_at >=', $timeFilter)
                     ->groupBy('ip_address')
                     ->orderBy('event_count', 'DESC')
                     ->limit($limit)
                     ->findAll();
    }
    
    /**
     * Get failed login attempts by time period
     */
    public function getFailedLoginAttempts($hours = 24)
    {
        $timeFilter = date('Y-m-d H:i:s', strtotime('-' . $hours . ' hours'));
        
        return $this->where('event_type', 'login_failed')
                   ->where('created_at >=', $timeFilter)
                   ->orderBy('created_at', 'DESC')
                   ->findAll();
    }
    
    /**
     * Get security events for charts
     */
    public function getChartData($period = '7 days', $groupBy = 'hour')
    {
        $timeFilter = date('Y-m-d H:i:s', strtotime('-' . $period));
        $dateFormat = $groupBy === 'hour' ? '%Y-%m-%d %H:00:00' : '%Y-%m-%d';
        
        return $this->select("DATE_FORMAT(created_at, '{$dateFormat}') as time_period, 
                              event_type, COUNT(*) as count")
                     ->where('created_at >=', $timeFilter)
                     ->groupBy("time_period, event_type")
                     ->orderBy('time_period', 'ASC')
                     ->findAll();
    }
}
