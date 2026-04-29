<?php

namespace App\Libraries;

use App\Models\ActivityLogModel;
use CodeIgniter\HTTP\IncomingRequest;

class ActivityLogger
{
    private const SECRET_KEYS = [
        'password',
        'password_confirm',
        'current_password',
        'new_password',
        'confirm_password',
        'otp',
        'token',
        'api_token',
        'authorization',
    ];

    private ActivityLogModel $activityLogModel;
    private IncomingRequest $request;

    public function __construct(?ActivityLogModel $activityLogModel = null, ?IncomingRequest $request = null)
    {
        $this->activityLogModel = $activityLogModel ?? new ActivityLogModel();
        $this->request = $request ?? service('request');
    }

    /**
     * @param array<string, mixed> $details
     */
    public function record(
        string $eventType,
        string $action,
        string $outcome = 'success',
        ?int $actorUserId = null,
        ?int $targetUserId = null,
        array $details = [],
        ?string $source = null,
        ?string $sessionKey = null
    ): void {
        try {
            $this->activityLogModel->insert([
                'actor_user_id' => $actorUserId,
                'target_user_id' => $targetUserId,
                'event_type' => $eventType,
                'action' => $action,
                'outcome' => $outcome,
                'source' => $source ?? $this->resolveSource(),
                'ip_address' => $this->request->getIPAddress(),
                'user_agent' => $this->request->getUserAgent()?->getAgentString(),
                'session_key_hash' => $this->hashSessionKey($sessionKey ?? $this->resolveSessionKey()),
                'details' => $this->sanitizeDetails($details),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Activity log write failed: {message}', ['message' => $e->getMessage()]);
        }
    }

    /**
     * @param array<string, mixed> $before
     * @param array<string, mixed> $after
     * @param list<string>|null $fields
     * @return array<string, array<string, mixed>>
     */
    public function changedFields(array $before, array $after, ?array $fields = null): array
    {
        $fields ??= array_values(array_unique(array_merge(array_keys($before), array_keys($after))));
        $changes = [];

        foreach ($fields as $field) {
            $oldValue = $before[$field] ?? null;
            $newValue = $after[$field] ?? null;

            if ((string) $oldValue === (string) $newValue) {
                continue;
            }

            if ($this->isSecretKey($field)) {
                $changes[$field] = ['changed' => true];
                continue;
            }

            $changes[$field] = [
                'from' => $oldValue,
                'to' => $newValue,
            ];
        }

        return $this->sanitizeDetails($changes);
    }

    /**
     * @param array<string, mixed> $details
     * @return array<string, mixed>
     */
    private function sanitizeDetails(array $details): array
    {
        $clean = [];

        foreach ($details as $key => $value) {
            $keyString = (string) $key;

            if ($this->isSecretKey($keyString)) {
                $clean[$keyString] = '[REDACTED]';
                continue;
            }

            if (is_array($value)) {
                $clean[$keyString] = $this->sanitizeDetails($value);
                continue;
            }

            $clean[$keyString] = $value;
        }

        return $clean;
    }

    private function isSecretKey(string $key): bool
    {
        $normalized = strtolower($key);

        foreach (self::SECRET_KEYS as $secretKey) {
            if ($normalized === $secretKey || str_contains($normalized, $secretKey)) {
                return true;
            }
        }

        return false;
    }

    private function resolveSource(): string
    {
        return str_starts_with(trim($this->request->getUri()->getPath(), '/'), 'api/') ? 'api' : 'web';
    }

    private function resolveSessionKey(): ?string
    {
        $authSessionKey = $this->request->authSessionKey ?? null;
        if (is_string($authSessionKey) && $authSessionKey !== '') {
            return $authSessionKey;
        }

        $sessionKey = session()->get('tracked_session_key');

        return is_string($sessionKey) && $sessionKey !== '' ? $sessionKey : null;
    }

    private function hashSessionKey(?string $sessionKey): ?string
    {
        return is_string($sessionKey) && $sessionKey !== '' ? hash('sha256', $sessionKey) : null;
    }
}
