<?php

namespace App\Libraries;

use App\Models\UserSessionModel;
use CodeIgniter\HTTP\IncomingRequest;
use Config\Services;

class SessionTracker
{
    private const STATUS_ACTIVE = 'active';
    private const STATUS_LOGGED_OUT = 'logged_out';
    private const STATUS_EXPIRED = 'expired';

    private UserSessionModel $sessionModel;
    private IncomingRequest $request;

    public function __construct(?UserSessionModel $sessionModel = null, ?IncomingRequest $request = null)
    {
        $this->sessionModel = $sessionModel ?? new UserSessionModel();
        $this->request = $request ?? service('request');
    }

    public function startWebSession(array $user): string
    {
        $sessionKey = 'web:' . session_id();
        $this->upsertSession($sessionKey, (int) $user['id'], 'web', $this->getWebExpiryTimestamp());

        return $sessionKey;
    }

    public function startApiSession(array $user): string
    {
        $sessionKey = 'api:' . bin2hex(random_bytes(24));
        $this->upsertSession($sessionKey, (int) $user['id'], 'api', date('Y-m-d H:i:s', time() + (60 * 60 * 24)));

        return $sessionKey;
    }

    public function touchWebSession(?int $userId = null): void
    {
        $session = session();
        $sessionKey = $session->get('tracked_session_key');

        if (!is_string($sessionKey) || $sessionKey === '') {
            return;
        }

        $this->touchSession($sessionKey, $userId, $this->getWebExpiryTimestamp());
    }

    public function touchApiSession(string $sessionKey, ?int $userId = null): void
    {
        $this->touchSession($sessionKey, $userId, date('Y-m-d H:i:s', time() + (60 * 60 * 24)));
    }

    public function endWebSession(?string $status = null): void
    {
        $sessionKey = session()->get('tracked_session_key');
        if (!is_string($sessionKey) || $sessionKey === '') {
            return;
        }

        $this->endSession($sessionKey, $status ?? self::STATUS_LOGGED_OUT);
    }

    public function endApiSession(?string $sessionKey, ?string $status = null): void
    {
        if (!is_string($sessionKey) || $sessionKey === '') {
            return;
        }

        $this->endSession($sessionKey, $status ?? self::STATUS_LOGGED_OUT);
    }

    public function validateTrackedSession(string $sessionKey): ?array
    {
        $this->expireStaleSessions();

        $session = $this->sessionModel->findBySessionKey($sessionKey);
        if (!$session || ($session['status'] ?? null) !== self::STATUS_ACTIVE) {
            return null;
        }

        if (!empty($session['expires_at']) && strtotime((string) $session['expires_at']) <= time()) {
            $this->endSession($sessionKey, self::STATUS_EXPIRED);
            return null;
        }

        return $session;
    }

    public function expireStaleSessions(): void
    {
        $now = date('Y-m-d H:i:s');

        $this->sessionModel
            ->where('status', self::STATUS_ACTIVE)
            ->groupStart()
                ->where('expires_at <', $now)
                ->orGroupStart()
                    ->where('session_type', 'web')
                    ->where('last_activity_at <', date('Y-m-d H:i:s', time() - $this->getWebSessionLifetimeSeconds()))
                ->groupEnd()
            ->groupEnd()
            ->set([
                'status' => self::STATUS_EXPIRED,
                'logged_out_at' => $now,
                'updated_at' => $now,
            ])
            ->update();
    }

    public function getCurrentSessionSummary(): ?array
    {
        $sessionKey = session()->get('tracked_session_key');
        if (!is_string($sessionKey) || $sessionKey === '') {
            return null;
        }

        return $this->validateTrackedSession($sessionKey);
    }

    public function getActiveSessions(int $limit = 20): array
    {
        $this->expireStaleSessions();
        return $this->sessionModel->getActiveSessionsWithUsers($limit);
    }

    public function getActiveSessionsForUser(int $userId): array
    {
        $this->expireStaleSessions();
        return $this->sessionModel->getActiveSessionsForUser($userId);
    }

    public function getActiveSessionCount(): int
    {
        $this->expireStaleSessions();
        return $this->sessionModel->countActiveSessions();
    }

    private function upsertSession(string $sessionKey, int $userId, string $sessionType, string $expiresAt): void
    {
        $now = date('Y-m-d H:i:s');
        $existing = $this->sessionModel->findBySessionKey($sessionKey);
        $payload = [
            'user_id' => $userId,
            'session_key' => $sessionKey,
            'session_type' => $sessionType,
            'status' => self::STATUS_ACTIVE,
            'ip_address' => $this->request->getIPAddress(),
            'user_agent' => $this->request->getUserAgent()?->getAgentString(),
            'device_label' => $this->resolveDeviceLabel(),
            'last_activity_at' => $now,
            'logged_in_at' => $existing['logged_in_at'] ?? $now,
            'logged_out_at' => null,
            'expires_at' => $expiresAt,
        ];

        if ($existing) {
            $this->sessionModel->update((int) $existing['id'], $payload);
            return;
        }

        $this->sessionModel->insert($payload);
    }

    private function touchSession(string $sessionKey, ?int $userId, string $expiresAt): void
    {
        $existing = $this->validateTrackedSession($sessionKey);
        if (!$existing) {
            return;
        }

        $this->sessionModel->update((int) $existing['id'], [
            'user_id' => $userId ?? (int) $existing['user_id'],
            'ip_address' => $this->request->getIPAddress(),
            'user_agent' => $this->request->getUserAgent()?->getAgentString(),
            'device_label' => $this->resolveDeviceLabel(),
            'last_activity_at' => date('Y-m-d H:i:s'),
            'expires_at' => $expiresAt,
        ]);
    }

    private function endSession(string $sessionKey, string $status): void
    {
        $existing = $this->sessionModel->findBySessionKey($sessionKey);
        if (!$existing) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $this->sessionModel->update((int) $existing['id'], [
            'status' => $status,
            'last_activity_at' => $now,
            'logged_out_at' => $now,
        ]);
    }

    private function getWebSessionLifetimeSeconds(): int
    {
        $expiration = (int) config('Session')->expiration;
        return $expiration > 0 ? $expiration : 7200;
    }

    private function getWebExpiryTimestamp(): string
    {
        return date('Y-m-d H:i:s', time() + $this->getWebSessionLifetimeSeconds());
    }

    private function resolveDeviceLabel(): string
    {
        $agent = $this->request->getUserAgent();
        if ($agent === null) {
            return 'Unknown device';
        }

        if ($agent->isBrowser()) {
            return trim($agent->getBrowser() . ' on ' . $agent->getPlatform());
        }

        if ($agent->isRobot()) {
            return 'Robot: ' . $agent->getRobot();
        }

        if ($agent->isMobile()) {
            return trim($agent->getMobile() . ' on ' . $agent->getPlatform());
        }

        return $agent->getPlatform() ?: 'Unknown device';
    }
}
