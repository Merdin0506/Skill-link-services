<?php

namespace App\Libraries;

use CodeIgniter\Email\Email;

class SecurityEmailNotifier
{
    protected $email;
    
    public function __construct()
    {
        $this->email = \Config\Services::email();
    }
    
    /**
     * Send security alert email to admins
     */
    public function sendSecurityAlert($subject, $message, $priority = 'medium')
    {
        // Get admin emails from database
        $userModel = new \App\Models\UserModel();
        $admins = $userModel->where('user_type', 'admin')->findAll();
        
        $adminEmails = array_map(function($admin) {
            return $admin['email'];
        }, $admins);
        
        if (empty($adminEmails)) {
            return false;
        }
        
        $this->email->setFrom('security@skilllink.com', 'SkillLink Security System');
        $this->email->setTo($adminEmails);
        $this->email->setSubject('[SECURITY ALERT] ' . $subject);
        
        $emailBody = $this->buildEmailTemplate($subject, $message, $priority);
        $this->email->setMessage($emailBody);
        
        return $this->email->send();
    }
    
    /**
     * Send critical security alert immediately
     */
    public function sendCriticalAlert($subject, $message, $ipAddress = null, $details = null)
    {
        $priority = 'critical';
        $emailBody = $this->buildCriticalEmailTemplate($subject, $message, $ipAddress, $details);
        
        $userModel = new \App\Models\UserModel();
        $admins = $userModel->where('user_type', 'admin')->findAll();
        
        foreach ($admins as $admin) {
            $this->email->clear();
            $this->email->setFrom('security@skilllink.com', 'SkillLink Security System');
            $this->email->setTo($admin['email']);
            $this->email->setSubject('[CRITICAL SECURITY ALERT] ' . $subject);
            $this->email->setMessage($emailBody);
            $this->email->setPriority(1); // High priority
            
            $this->email->send();
        }
    }
    
    /**
     * Build standard email template
     */
    private function buildEmailTemplate($subject, $message, $priority)
    {
        $priorityColor = match($priority) {
            'critical' => '#dc3545',
            'high' => '#fd7e14',
            'medium' => '#ffc107',
            'low' => '#28a745',
            default => '#6c757d'
        };
        
        return "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background-color: #f8f9fa; }
                .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
                .header { background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%); color: white; padding: 30px; text-align: center; }
                .content { padding: 30px; }
                .priority-badge { color: white; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; display: inline-block; margin-bottom: 20px; }
                .footer { background: #f8f9fa; padding: 20px; text-align: center; color: #6c757d; font-size: 12px; }
                .alert-box { background: #f8f9fa; border-left: 4px solid {$priorityColor}; padding: 15px; margin: 20px 0; border-radius: 4px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🛡️ SkillLink Security Alert</h1>
                    <p>Security Monitoring System</p>
                </div>
                <div class='content'>
                    <div class='priority-badge' style='background-color: {$priorityColor};'>
                        PRIORITY: " . strtoupper($priority) . "
                    </div>
                    <h2>{$subject}</h2>
                    <div class='alert-box'>
                        {$message}
                    </div>
                    <p><strong>Time:</strong> " . date('Y-m-d H:i:s') . "</p>
                    <p><strong>Action Required:</strong> Please review this security event in the admin dashboard.</p>
                    <p style='text-align: center; margin-top: 30px;'>
                        <a href='http://localhost:8080/security/dashboard' style='background: #3498db; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;'>View Security Dashboard</a>
                    </p>
                </div>
                <div class='footer'>
                    <p>This is an automated message from the SkillLink Security System.</p>
                    <p>If you believe this is a false alarm, please contact your system administrator.</p>
                </div>
            </div>
        </body>
        </html>";
    }
    
    /**
     * Build critical alert email template
     */
    private function buildCriticalEmailTemplate($subject, $message, $ipAddress = null, $details = null)
    {
        return "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background-color: #fff3cd; }
                .container { max-width: 600px; margin: 0 auto; background: white; border: 2px solid #dc3545; border-radius: 8px; overflow: hidden; }
                .header { background: #dc3545; color: white; padding: 30px; text-align: center; }
                .content { padding: 30px; }
                .critical-banner { background: #dc3545; color: white; padding: 15px; text-align: center; font-weight: bold; }
                .alert-details { background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; margin: 20px 0; border-radius: 4px; }
                .footer { background: #f8f9fa; padding: 20px; text-align: center; color: #6c757d; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='critical-banner'>
                    🚨 CRITICAL SECURITY THREAT DETECTED 🚨
                </div>
                <div class='header'>
                    <h1>IMMEDIATE ACTION REQUIRED</h1>
                </div>
                <div class='content'>
                    <h2 style='color: #dc3545;'>{$subject}</h2>
                    <div class='alert-details'>
                        <strong>Alert Details:</strong><br>
                        {$message}
                        " . ($ipAddress ? "<br><strong>IP Address:</strong> {$ipAddress}" : "") . "
                        " . ($details ? "<br><strong>Additional Details:</strong><br>{$details}" : "") . "
                    </div>
                    <p><strong>Detection Time:</strong> " . date('Y-m-d H:i:s') . "</p>
                    <p style='color: #dc3545; font-weight: bold;'>⚠️ This requires immediate attention. Please investigate and take appropriate action.</p>
                    <p style='text-align: center; margin-top: 30px;'>
                        <a href='http://localhost:8080/security/dashboard' style='background: #dc3545; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; display: inline-block; font-weight: bold;'>ACCESS SECURITY DASHBOARD NOW</a>
                    </p>
                </div>
                <div class='footer'>
                    <p>This is a CRITICAL security alert from the SkillLink Security System.</p>
                    <p>Do not ignore this message. Take immediate action to secure your system.</p>
                </div>
            </div>
        </body>
        </html>";
    }
}
