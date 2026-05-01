<?php

namespace App\Models;

use CodeIgniter\Model;

class BlockedIPModel extends Model
{
    protected $table = 'blocked_ips';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'ip_address', 'reason', 'blocked_by', 'is_temporary', 
        'blocked_until', 'attempts_count', 'last_attempt', 'is_active', 
        'created_at', 'updated_at'
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $dateFormat = 'datetime';
    
    protected $returnType = 'array';
    
    /**
     * Check if an IP address is currently blocked
     */
    public function isIPBlocked($ipAddress)
    {
        $blocked = $this->where('ip_address', $ipAddress)
                       ->where('is_active', true)
                       ->first();
        
        if ($blocked) {
            // Check if temporary block has expired
            if ($blocked['is_temporary'] && $blocked['blocked_until']) {
                if (strtotime($blocked['blocked_until']) < time()) {
                    // Unblock expired temporary ban
                    $this->update($blocked['id'], ['is_active' => false]);
                    return false;
                }
            }
            return true;
        }
        
        return false;
    }
    
    /**
     * Block an IP address
     */
    public function blockIP($ipAddress, $reason, $isTemporary = true, $blockedUntil = null, $blockedBy = null)
    {
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
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }
    
    /**
     * Unblock an IP address
     */
    public function unblockIP($ipAddress)
    {
        return $this->where('ip_address', $ipAddress)
                   ->update(['is_active' => false]);
    }
    
    /**
     * Get blocked IPs
     */
    public function getBlockedIPs($activeOnly = true)
    {
        $builder = $this->builder();
        
        if ($activeOnly) {
            $builder->where('is_active', true);
        }
        
        return $builder->orderBy('created_at', 'DESC')->get()->getResultArray();
    }
    
    /**
     * Record an attempt for an IP
     */
    public function recordAttempt($ipAddress)
    {
        $blocked = $this->where('ip_address', $ipAddress)->first();
        
        if ($blocked) {
            $this->update($blocked['id'], [
                'attempts_count' => $blocked['attempts_count'] + 1,
                'last_attempt' => date('Y-m-d H:i:s'),
            ]);
        }
    }
}
