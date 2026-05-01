<?php

namespace App\Controllers;

use App\Controllers\SecurityController as BaseSecurityController;
use App\Models\SecurityEventModel;
use App\Models\SecurityNotificationModel;
use App\Models\BlockedIPModel;

class Security extends BaseController
{
    protected $baseSecurityController;
    protected $securityEventModel;
    protected $securityNotificationModel;
    protected $blockedIPModel;
    
    public function __construct()
    {
        $this->baseSecurityController = new BaseSecurityController();
        $this->securityEventModel = new SecurityEventModel();
        $this->securityNotificationModel = new SecurityNotificationModel();
        $this->blockedIPModel = new BlockedIPModel();
    }
    
    /**
     * Security Dashboard
     */
    public function dashboard()
    {
        // Check if user is admin
        if (!$this->isAdmin()) {
            return redirect()->to('/dashboard')->with('error', 'Access denied');
        }
        
        $data = [
            'title' => 'Security Dashboard',
            'dashboardData' => $this->baseSecurityController->getDashboardData()
        ];
        
        return view('security/dashboard', $data);
    }
    
    /**
     * Audit Logs
     */
    public function auditLogs()
    {
        if (!$this->isAdmin()) {
            return redirect()->to('/dashboard')->with('error', 'Access denied');
        }
        
        $data = [
            'title' => 'Audit Logs'
        ];
        
        return view('security/audit_logs', $data);
    }
    
    /**
     * Notifications
     */
    public function notifications()
    {
        if (!$this->isAdmin()) {
            return redirect()->to('/dashboard')->with('error', 'Access denied');
        }
        
        $data = [
            'title' => 'Security Notifications'
        ];
        
        return view('security/notifications', $data);
    }
    
    /**
     * Reports
     */
    public function reports()
    {
        if (!$this->isAdmin()) {
            return redirect()->to('/dashboard')->with('error', 'Access denied');
        }
        
        $data = [
            'title' => 'Security Reports'
        ];
        
        return view('security/reports', $data);
    }
    
    /**
     * Blocked IPs
     */
    public function blockedIPs()
    {
        if (!$this->isAdmin()) {
            return redirect()->to('/dashboard')->with('error', 'Access denied');
        }
        
        $data = [
            'title' => 'Blocked IPs'
        ];
        
        return view('security/blocked_ips', $data);
    }
    
    /**
     * Settings
     */
    public function settings()
    {
        if (!$this->isAdmin()) {
            return redirect()->to('/dashboard')->with('error', 'Access denied');
        }
        
        $data = [
            'title' => 'Security Settings'
        ];
        
        return view('security/settings', $data);
    }
    
    /**
     * Check if current user is admin
     */
    private function isAdmin()
    {
        $session = session();
        $user = $session->get('user');
        
        return $user && $user['user_type'] === 'admin';
    }
}
