<?php

namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;
use App\Controllers\SecurityController;
use App\Models\BlockedIPModel;

class SecurityFilter implements FilterInterface
{
    protected $securityController;
    protected $blockedIPModel;
    
    public function __construct()
    {
        $this->securityController = new SecurityController();
        $this->blockedIPModel = new BlockedIPModel();
    }
    
    public function before(RequestInterface $request, $arguments = null)
    {
        $ipAddress = $request->getIPAddress();
        
        // Clean expired blocks first
        $this->blockedIPModel->cleanExpiredBlocks();
        
        // Check if IP is blocked
        if ($this->blockedIPModel->isIPBlocked($ipAddress)) {
            $blockedInfo = $this->blockedIPModel
                ->where('ip_address', $ipAddress)
                ->where('is_active', true)
                ->first();
                
            // Log the blocked access attempt
            $this->securityController->logEvent(
                'unauthorized_access',
                'high',
                'Blocked IP attempted to access: ' . $request->uri->getPath(),
                null,
                null
            );
            
            // Return blocked response
            return \Config\Services::response()
                ->setStatusCode(403)
                ->setJSON([
                    'error' => 'Access denied',
                    'message' => 'Your IP address has been blocked due to suspicious activity',
                    'blocked_until' => $blockedInfo['blocked_until'] ?? 'Permanent'
                ]);
        }
        
        // Check for common attack patterns
        $this->detectAttackPatterns($request);
        
        // Check for suspicious user agents
        $this->checkUserAgent($request);
        
        // Check request rate limiting
        $this->checkRateLimit($request);
    }
    
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // No post-processing needed
    }
    
    /**
     * Detect common attack patterns
     */
    private function detectAttackPatterns($request)
    {
        $uri = $request->getUri()->getPath();
        $query = $request->getUri()->getQuery();
        $method = $request->getMethod();
        $headers = $request->getHeaders();
        $body = $request->getBody();
        
        $ipAddress = $request->getIPAddress();
        
        // SQL Injection patterns
        $sqlPatterns = [
            '/(\%27)|(\')|(\-\-)|(\%23)|(#)/i',
            '/((\%3D)|(=))[^\n]*((\%27)|(\')|(\-\-)|(\%3B)|(;))/i',
            '/\w*((\%27)|(\'))((\%6F)|o|(\%4F))((\%72)|r|(\%52))/i',
            '/((\%27)|(\'))union/ix',
            '/exec(\s|\+)+(s|x)p\w+/ix',
            '/UNION[^a-zA-Z]/i',
            '/SELECT[^a-zA-Z]/i',
            '/INSERT[^a-zA-Z]/i',
            '/DELETE[^a-zA-Z]/i',
            '/UPDATE[^a-zA-Z]/i',
            '/DROP[^a-zA-Z]/i'
        ];
        
        // XSS patterns
        $xssPatterns = [
            '/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi',
            '/<iframe\b[^<]*(?:(?!<\/iframe>)<[^<]*)*<\/iframe>/mi',
            '/javascript:/i',
            '/on\w+\s*=/i',
            '/<img[^>]*src[^>]*javascript:/i'
        ];
        
        // Check for SQL injection
        foreach ($sqlPatterns as $pattern) {
            if (preg_match($pattern, $uri) || 
                preg_match($pattern, $query) || 
                preg_match($pattern, $body)) {
                
                $this->securityController->logEvent(
                    'sql_injection_attempt',
                    'critical',
                    'SQL injection pattern detected in request',
                    null,
                    null
                );
                
                $this->securityController->blockIP($ipAddress, 'SQL injection attempt detected', true, '+2 hours');
                
                return $this->blockResponse('SQL injection attempt detected');
            }
        }
        
        // Check for XSS
        foreach ($xssPatterns as $pattern) {
            if (preg_match($pattern, $uri) || 
                preg_match($pattern, $query) || 
                preg_match($pattern, $body)) {
                
                $this->securityController->logEvent(
                    'xss_attempt',
                    'high',
                    'XSS pattern detected in request',
                    null,
                    null
                );
                
                return $this->blockResponse('XSS attempt detected');
            }
        }
        
        // Check for path traversal
        if (preg_match('/\.\.[\/\\\\]/', $uri) || 
            preg_match('/\.\.[\/\\\\]/', $query)) {
            
            $this->securityController->logEvent(
                'suspicious_activity',
                'high',
                'Path traversal attempt detected',
                null,
                null
            );
            
            return $this->blockResponse('Path traversal attempt detected');
        }
        
        // Check for command injection
        $commandPatterns = [
            '/;\s*(ls|whoami|cat|pwd|id|uname)/i',
            '/\|\s*(ls|whoami|cat|pwd|id|uname)/i',
            '/&&\s*(ls|whoami|cat|pwd|id|uname)/i'
        ];
        
        foreach ($commandPatterns as $pattern) {
            if (preg_match($pattern, $query) || preg_match($pattern, $body)) {
                $this->securityController->logEvent(
                    'suspicious_activity',
                    'critical',
                    'Command injection attempt detected',
                    null,
                    null
                );
                
                $this->securityController->blockIP($ipAddress, 'Command injection attempt detected', true, '+2 hours');
                
                return $this->blockResponse('Command injection attempt detected');
            }
        }
    }
    
    /**
     * Check for suspicious user agents
     */
    private function checkUserAgent($request)
    {
        $userAgent = $request->getUserAgent();
        $ipAddress = $request->getIPAddress();
        
        // Common bot/scanner user agents
        $suspiciousAgents = [
            'sqlmap',
            'nikto',
            'nmap',
            'masscan',
            'zap',
            'burp',
            'scanner',
            'crawler',
            'bot',
            'spider'
        ];
        
        foreach ($suspiciousAgents as $agent) {
            if (stripos($userAgent, $agent) !== false) {
                $this->securityController->logEvent(
                    'suspicious_activity',
                    'medium',
                    'Suspicious user agent detected: ' . $userAgent,
                    null,
                    null
                );
                
                // Don't block immediately, just log
                break;
            }
        }
        
        // Check for empty user agent
        if (empty($userAgent)) {
            $this->securityController->logEvent(
                'suspicious_activity',
                'low',
                'Empty user agent detected',
                null,
                null
            );
        }
    }
    
    /**
     * Check rate limiting
     */
    private function checkRateLimit($request)
    {
        $ipAddress = $request->getIPAddress();
        $uri = $request->getUri()->getPath();
        
        // Get current session or create rate limit key
        $session = \Config\Services::session();
        $rateLimitKey = 'rate_limit_' . md5($ipAddress . $uri);
        
        $currentCount = $session->get($rateLimitKey) ?? 0;
        $session->set($rateLimitKey, $currentCount + 1);
        
        // Allow 60 requests per minute per IP per endpoint
        if ($currentCount > 60) {
            $this->securityController->logEvent(
                'suspicious_activity',
                'medium',
                'Rate limit exceeded for endpoint: ' . $uri,
                null,
                null
            );
            
            return $this->blockResponse('Rate limit exceeded');
        }
        
        // Reset counter after 1 minute
        $session->markAsTempdata($rateLimitKey, 60);
    }
    
    /**
     * Return blocked response
     */
    private function blockResponse($message)
    {
        return \Config\Services::response()
            ->setStatusCode(403)
            ->setJSON([
                'error' => 'Access denied',
                'message' => $message
            ]);
    }
}
