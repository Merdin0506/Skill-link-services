<?php

namespace App\Controllers\API;

use App\Controllers\BaseController;
use App\Libraries\SecuritySync;
use CodeIgniter\API\ResponseTrait;

class SyncController extends BaseController
{
    use ResponseTrait;
    
    protected $securitySync;
    
    public function __construct()
    {
        $this->securitySync = new SecuritySync();
    }
    
    /**
     * Get initial security data
     */
    public function initialize()
    {
        $data = $this->securitySync->getRealtimeData();
        
        return $this->respond([
            'status' => 'success',
            'data' => $data,
            'timestamp' => time()
        ]);
    }
    
    /**
     * Get incremental updates since last sync
     */
    public function sync()
    {
        $lastSync = $this->request->getGet('last_sync') ?? 0;
        
        if ($this->securitySync->hasNewEvents($lastSync)) {
            $updates = $this->securitySync->getIncrementalUpdates($lastSync);
            
            return $this->respond([
                'status' => 'updated',
                'data' => $updates,
                'timestamp' => time()
            ]);
        }
        
        return $this->respond([
            'status' => 'no_changes',
            'timestamp' => time()
        ]);
    }
    
    /**
     * Real-time polling endpoint
     */
    public function poll()
    {
        $lastSync = $this->request->getGet('last_sync') ?? 0;
        $timeout = 30; // 30 seconds timeout
        $startTime = time();
        
        // Long polling - wait for new events or timeout
        while (time() - $startTime < $timeout) {
            if ($this->securitySync->hasNewEvents($lastSync)) {
                $updates = $this->securitySync->getIncrementalUpdates($lastSync);
                
                return $this->respond([
                    'status' => 'updated',
                    'data' => $updates,
                    'timestamp' => time()
                ]);
            }
            
            // Check every second
            sleep(1);
        }
        
        return $this->respond([
            'status' => 'timeout',
            'timestamp' => time()
        ]);
    }
    
    /**
     * Force refresh all data
     */
    public function refresh()
    {
        $data = $this->securitySync->getRealtimeData();
        
        return $this->respond([
            'status' => 'refreshed',
            'data' => $data,
            'timestamp' => time()
        ]);
    }
    
    /**
     * Get database statistics
     */
    public function stats()
    {
        $stats = $this->securitySync->getCurrentStatistics();
        
        return $this->respond([
            'status' => 'success',
            'data' => $stats,
            'timestamp' => time()
        ]);
    }
}
