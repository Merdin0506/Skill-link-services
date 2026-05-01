<?php

namespace App\Models;

use CodeIgniter\Model;

class BlockedIPModel extends Model
{
    protected $table = 'blocked_ips';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'ip_address', 'reason', 'blocked_by', 'is_temporary', 
        'blocked_until', 'attempts_count', 'last_attempt', 
        'is_active', 'created_at', 'updated_at'
    ];
    protected $useTimestamps = false;
    
    protected $returnType = 'array';
    
    /**
     * Get active blocked IPs
     */
    public function getActiveBlockedIPs($limit = 100)
    {
        return $this->where('is_active', true)
                   ->groupStart()
                       ->where('is_temporary', false)
                       ->orGroupStart()
                           ->where('is_temporary', true)
                           ->where('blocked_until >', date('Y-m-d H:i:s'))
                       ->groupEnd()
                   ->groupEnd()
                   ->orderBy('created_at', 'DESC')
                   ->limit($limit)
                   ->findAll();
    }
    
    /**
     * Check if IP is blocked
     */
    public function isIPBlocked($ipAddress)
    {
        $blocked = $this->where('ip_address', $ipAddress)
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
     * Block an IP address
     */
    public function blockIP($ipAddress, $reason, $blockedBy = null, $isTemporary = true, $duration = '+1 hour')
    {
        $blockedUntil = $isTemporary ? date('Y-m-d H:i:s', strtotime($duration)) : null;
        
        // Check if IP is already blocked
        $existing = $this->where('ip_address', $ipAddress)
                         ->where('is_active', true)
                         ->first();
                         
        if ($existing) {
            // Update existing block
            return $this->update($existing['id'], [
                'reason' => $reason,
                'blocked_by' => $blockedBy,
                'is_temporary' => $isTemporary,
                'blocked_until' => $blockedUntil,
                'attempts_count' => $existing['attempts_count'] + 1,
                'last_attempt' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
        } else {
            // Create new block
            return $this->insert([
                'ip_address' => $ipAddress,
                'reason' => $reason,
                'blocked_by' => $blockedBy,
                'is_temporary' => $isTemporary,
                'blocked_until' => $blockedUntil,
                'attempts_count' => 1,
                'last_attempt' => date('Y-m-d H:i:s'),
                'is_active' => true,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
        }
    }
    
    /**
     * Unblock an IP address
     */
    public function unblockIP($ipAddress)
    {
        return $this->where('ip_address', $ipAddress)
                   ->set(['is_active' => false, 'updated_at' => date('Y-m-d H:i:s')])
                   ->update();
    }
    
    /**
     * Unblock IP by ID
     */
    public function unblockIPById($id)
    {
        return $this->update($id, [
            'is_active' => false,
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * Clean expired temporary blocks
     */
    public function cleanExpiredBlocks()
    {
        return $this->where('is_temporary', true)
                   ->where('blocked_until <', date('Y-m-d H:i:s'))
                   ->where('is_active', true)
                   ->set(['is_active' => false, 'updated_at' => date('Y-m-d H:i:s')])
                   ->update();
    }
    
    /**
     * Get block statistics
     */
    public function getBlockStatistics()
    {
        $stats = [
            'total_blocks' => $this->countAllResults(),
            'active_blocks' => $this->where('is_active', true)->countAllResults(),
            'temporary_blocks' => $this->where('is_temporary', true)->where('is_active', true)->countAllResults(),
            'permanent_blocks' => $this->where('is_temporary', false)->where('is_active', true)->countAllResults(),
            'expired_blocks' => $this->where('is_temporary', true)
                                  ->where('blocked_until <', date('Y-m-d H:i:s'))
                                  ->where('is_active', true)
                                  ->countAllResults()
        ];
        
        return $stats;
    }
    
    /**
     * Get top blocked IPs
     */
    public function getTopBlockedIPs($limit = 10)
    {
        return $this->select('ip_address, reason, attempts_count, created_at, blocked_until')
                   ->where('is_active', true)
                   ->orderBy('attempts_count', 'DESC')
                   ->orderBy('created_at', 'DESC')
                   ->limit($limit)
                   ->findAll();
    }
    
    /**
     * Get recent blocks
     */
    public function getRecentBlocks($limit = 20)
    {
        return $this->orderBy('created_at', 'DESC')
                   ->limit($limit)
                   ->findAll();
    }
    
    /**
     * Search blocked IPs
     */
    public function searchBlockedIPs($search, $limit = 50)
    {
        return $this->groupStart()
                   ->like('ip_address', $search)
                   ->orLike('reason', $search)
                   ->groupEnd()
                   ->orderBy('created_at', 'DESC')
                   ->limit($limit)
                   ->findAll();
    }
}
