<?php

namespace App\Filters;

use App\Models\BlockedIPModel;
use App\Models\SecurityEventModel;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class SecurityFilter implements FilterInterface
{
    /**
     * Apply general security checks and headers
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        $ip = $request->getIPAddress();

        // Enforce blocked-IP protection globally.
        $blockedIpModel = new BlockedIPModel();
        $blocked = $blockedIpModel
            ->where('ip_address', $ip)
            ->where('is_active', true)
            ->groupStart()
                ->where('is_temporary', false)
                ->orGroupStart()
                    ->where('is_temporary', true)
                    ->where('blocked_until >', date('Y-m-d H:i:s'))
                ->groupEnd()
            ->groupEnd()
            ->first();

        if (!empty($blocked)) {
            $this->logSecurityEvent($request, 'unauthorized_access', 'high', 'Blocked IP attempted access.');

            return service('response')
                ->setStatusCode(403)
                ->setJSON([
                    'status' => 'error',
                    'message' => 'Access denied: IP is blocked.',
                ]);
        }
        
        // Add security tracking if user is logged in
        $session = session();
        if ($session->has('user_id')) {
            service('sessiontracker')->touchWebSession((int) $session->get('user_id'));
        }

        // Basic intrusion-pattern detection for request payloads.
        $path = (string) $request->getUri()->getPath();
        $queryRaw = (string) ($request->getServer('QUERY_STRING') ?? '');
        $bodyRaw = (string) ($request->getBody() ?? '');

        // Decode encoded payloads so signatures like "<script>" and
        // "union select" are detectable even when URL-encoded.
        $query = rawurldecode($queryRaw);
        $body = rawurldecode($bodyRaw);
        $payload = mb_strtolower($path . ' ' . $query . ' ' . $body, 'UTF-8');

        $sqlPattern = '/(\bunion\b\s+\bselect\b|\bdrop\b\s+\btable\b|\binsert\b\s+\binto\b|\bor\b\s+1\s*=\s*1|--|\/\*|\*\/)/i';
        $xssPattern = '/(<\s*script\b|javascript:|onerror\s*=|onload\s*=|<\s*iframe\b|<\s*img\b[^>]*on\w+\s*=)/i';

        if (preg_match($sqlPattern, $payload) === 1) {
            $this->logSecurityEvent($request, 'sql_injection_attempt', 'high', 'Potential SQL injection pattern detected.');
        }

        if (preg_match($xssPattern, $payload) === 1) {
            $this->logSecurityEvent($request, 'xss_attempt', 'high', 'Potential XSS pattern detected.');
        }
        
        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Add additional security headers if needed
        return null;
    }

    private function logSecurityEvent(RequestInterface $request, string $eventType, string $severity, string $details): void
    {
        try {
            $session = session();
            $securityEventModel = new SecurityEventModel();

            $securityEventModel->insert([
                'user_id' => $session->get('user_id') ?: null,
                'email' => $session->get('email') ?: null,
                'event_type' => $eventType,
                'severity' => $severity,
                'ip_address' => $request->getIPAddress(),
                'user_agent' => method_exists($request, 'getUserAgent') ? (string) $request->getUserAgent() : 'unknown',
                'request_uri' => $request->getUri()->getPath(),
                'request_method' => $request->getMethod(),
                'details' => $details,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            // Never break request handling because of security logging failure.
        }
    }
}
