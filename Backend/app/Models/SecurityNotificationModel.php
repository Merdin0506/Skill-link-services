<?php

namespace App\Models;

use CodeIgniter\Model;

class SecurityNotificationModel extends Model
{
    protected $table = 'security_notifications';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'admin_id', 'title', 'message', 'type', 'priority', 
        'is_read', 'action_required', 'related_user_id', 
        'ip_address', 'created_at', 'read_at'
    ];
    protected $useTimestamps = false;
    
    protected $returnType = 'array';
    
    /**
     * Get unread notifications for admin
     */
    public function getUnreadNotifications($adminId, $limit = 50)
    {
        return $this->where('admin_id', $adminId)
                   ->where('is_read', false)
                   ->orderBy('priority', 'DESC')
                   ->orderBy('created_at', 'DESC')
                   ->limit($limit)
                   ->findAll();
    }
    
    /**
     * Get all notifications for admin
     */
    public function getAllNotifications($adminId, $limit = 100)
    {
        return $this->where('admin_id', $adminId)
                   ->orderBy('is_read', 'ASC')
                   ->orderBy('priority', 'DESC')
                   ->orderBy('created_at', 'DESC')
                   ->limit($limit)
                   ->findAll();
    }
    
    /**
     * Get notification statistics
     */
    public function getNotificationStats($adminId = null)
    {
        $builder = $this->builder();
        
        if ($adminId) {
            $builder->where('admin_id', $adminId);
        }
        
        $stats = $builder->select('type, priority, COUNT(*) as count')
                         ->groupBy('type, priority')
                         ->findAll();
                         
        $result = [
            'total' => 0,
            'unread' => 0,
            'critical' => 0,
            'high' => 0,
            'medium' => 0,
            'low' => 0
        ];
        
        foreach ($stats as $stat) {
            $result['total'] += $stat['count'];
            
            if ($stat['priority'] === 'critical') {
                $result['critical'] += $stat['count'];
            } elseif ($stat['priority'] === 'high') {
                $result['high'] += $stat['count'];
            } elseif ($stat['priority'] === 'medium') {
                $result['medium'] += $stat['count'];
            } else {
                $result['low'] += $stat['count'];
            }
        }
        
        // Get unread count
        $unreadQuery = $this->builder();
        if ($adminId) {
            $unreadQuery->where('admin_id', $adminId);
        }
        $result['unread'] = $unreadQuery->where('is_read', false)->countAllResults();
        
        return $result;
    }
    
    /**
     * Get recent notifications
     */
    public function getRecentNotifications($adminId = null, $limit = 10)
    {
        $builder = $this->builder();
        
        if ($adminId) {
            $builder->where('admin_id', $adminId);
        }
        
        return $builder->orderBy('created_at', 'DESC')
                       ->limit($limit)
                       ->findAll();
    }
    
    /**
     * Get notifications by priority
     */
    public function getNotificationsByPriority($priority, $adminId = null, $limit = 20)
    {
        $builder = $this->where('priority', $priority)
                       ->orderBy('created_at', 'DESC');
                       
        if ($adminId) {
            $builder->where('admin_id', $adminId);
        }
        
        return $builder->limit($limit)->findAll();
    }
    
    /**
     * Get notifications requiring action
     */
    public function getActionRequiredNotifications($adminId = null)
    {
        $builder = $this->where('action_required', true)
                       ->where('is_read', false)
                       ->orderBy('priority', 'DESC')
                       ->orderBy('created_at', 'DESC');
                       
        if ($adminId) {
            $builder->where('admin_id', $adminId);
        }
        
        return $builder->findAll();
    }
    
    /**
     * Mark notifications as read
     */
    public function markAsRead($notificationId, $adminId = null)
    {
        $data = ['is_read' => true, 'read_at' => date('Y-m-d H:i:s')];
        
        if ($adminId) {
            return $this->where('id', $notificationId)
                        ->where('admin_id', $adminId)
                        ->set($data)
                        ->update();
        }
        
        return $this->update($notificationId, $data);
    }
    
    /**
     * Mark all notifications as read for admin
     */
    public function markAllAsRead($adminId)
    {
        return $this->where('admin_id', $adminId)
                   ->where('is_read', false)
                   ->set(['is_read' => true, 'read_at' => date('Y-m-d H:i:s')])
                   ->update();
    }
    
    /**
     * Clean old notifications
     */
    public function cleanOldNotifications($days = 30)
    {
        $cutoffDate = date('Y-m-d H:i:s', strtotime('-' . $days . ' days'));
        
        return $this->where('created_at <', $cutoffDate)
                   ->where('is_read', true)
                   ->delete();
    }
}
