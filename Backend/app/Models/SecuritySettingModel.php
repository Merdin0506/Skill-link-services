<?php

namespace App\Models;

use CodeIgniter\Model;

class SecuritySettingModel extends Model
{
    public const DEFAULTS = [
        'brute_force_threshold' => 5,
        'block_duration_minutes' => 30,
        'sync_poll_seconds' => 20,
        'auto_block_enabled' => true,
        'notify_on_failed_login' => true,
        'notify_on_blocked_ip' => true,
        'notify_on_suspicious_activity' => true,
    ];

    protected $table = 'security_settings';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useAutoIncrement = true;
    protected $protectFields = true;
    protected $allowedFields = [
        'brute_force_threshold',
        'block_duration_minutes',
        'sync_poll_seconds',
        'auto_block_enabled',
        'notify_on_failed_login',
        'notify_on_blocked_ip',
        'notify_on_suspicious_activity',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    public static function sanitizeSettings(array $input): array
    {
        return [
            'brute_force_threshold' => max(1, min((int) ($input['brute_force_threshold'] ?? self::DEFAULTS['brute_force_threshold']), 20)),
            'block_duration_minutes' => max(5, min((int) ($input['block_duration_minutes'] ?? self::DEFAULTS['block_duration_minutes']), 10080)),
            'sync_poll_seconds' => max(10, min((int) ($input['sync_poll_seconds'] ?? self::DEFAULTS['sync_poll_seconds']), 300)),
            'auto_block_enabled' => filter_var($input['auto_block_enabled'] ?? self::DEFAULTS['auto_block_enabled'], FILTER_VALIDATE_BOOLEAN),
            'notify_on_failed_login' => filter_var($input['notify_on_failed_login'] ?? self::DEFAULTS['notify_on_failed_login'], FILTER_VALIDATE_BOOLEAN),
            'notify_on_blocked_ip' => filter_var($input['notify_on_blocked_ip'] ?? self::DEFAULTS['notify_on_blocked_ip'], FILTER_VALIDATE_BOOLEAN),
            'notify_on_suspicious_activity' => filter_var($input['notify_on_suspicious_activity'] ?? self::DEFAULTS['notify_on_suspicious_activity'], FILTER_VALIDATE_BOOLEAN),
        ];
    }

    public function getSettings(): array
    {
        $settings = $this->first();
        if (!$settings) {
            return self::DEFAULTS;
        }

        return array_merge(self::DEFAULTS, array_intersect_key($settings, self::DEFAULTS));
    }

    public function saveSettings(array $input): array
    {
        $settings = self::sanitizeSettings($input);
        $existing = $this->first();

        if ($existing) {
            $this->update($existing['id'], $settings);
        } else {
            $this->insert($settings);
        }

        return $this->getSettings();
    }
}
