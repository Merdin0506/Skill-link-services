<?php

namespace App\Models;

use CodeIgniter\Model;

class ActivityLogModel extends Model
{
    protected $table = 'activity_logs';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $protectFields = true;
    protected $allowedFields = [
        'actor_user_id',
        'target_user_id',
        'event_type',
        'action',
        'outcome',
        'source',
        'ip_address',
        'user_agent',
        'session_key_hash',
        'details',
        'ip_address_encrypted',
        'user_agent_encrypted',
        'details_encrypted',
        'created_at',
    ];

    protected $useTimestamps = false;
    protected $beforeInsert = ['encryptDetails'];
    protected $beforeUpdate = ['encryptDetails'];
    protected $afterFind = ['decryptDetails'];

    public function __construct(?\CodeIgniter\Database\ConnectionInterface $db = null, ?\CodeIgniter\Validation\ValidationInterface $validation = null)
    {
        parent::__construct($db, $validation);
    }

    public function getRecentForUser(int $userId, int $limit = 20): array
    {
        return $this->groupStart()
                ->where('actor_user_id', $userId)
                ->orWhere('target_user_id', $userId)
            ->groupEnd()
            ->orderBy('created_at', 'DESC')
            ->findAll($limit);
    }

    public function getRecentWithUsers(int $limit = 30): array
    {
        return $this->select(
                'activity_logs.*, actor.first_name AS actor_first_name, actor.last_name AS actor_last_name, actor.email AS actor_email, actor.user_type AS actor_role, target.first_name AS target_first_name, target.last_name AS target_last_name, target.email AS target_email, target.user_type AS target_role'
            )
            ->join('users AS actor', 'actor.id = activity_logs.actor_user_id', 'left')
            ->join('users AS target', 'target.id = activity_logs.target_user_id', 'left')
            ->orderBy('activity_logs.created_at', 'DESC')
            ->findAll($limit);
    }

    protected function encryptDetails(array $data): array
    {
        if (!isset($data['data']) || !is_array($data['data'])) {
            return $data;
        }

        if (array_key_exists('details', $data['data'])) {
            $json = json_encode($data['data']['details'], JSON_UNESCAPED_SLASHES);
            $data['data']['details_encrypted'] = $json === false ? null : $this->encryptPayload($json);
            unset($data['data']['details']);
        }

        if (array_key_exists('details_encrypted', $data['data']) && is_array($data['data']['details_encrypted'])) {
            $json = json_encode($data['data']['details_encrypted'], JSON_UNESCAPED_SLASHES);
            $data['data']['details_encrypted'] = $json === false ? null : $this->encryptPayload($json);
        }

        foreach (['ip_address', 'user_agent'] as $field) {
            if (!array_key_exists($field, $data['data'])) {
                continue;
            }

            $encryptedField = $field . '_encrypted';
            $value = $data['data'][$field];
            $data['data'][$encryptedField] = is_string($value) && trim($value) !== ''
                ? $this->encryptPayload($value)
                : null;
            unset($data['data'][$field]);
        }

        return $data;
    }

    protected function decryptDetails(array $data): array
    {
        if (!isset($data['data'])) {
            return $data;
        }

        if (isset($data['data'][0]) && is_array($data['data'][0])) {
            foreach ($data['data'] as &$row) {
                $row = $this->decryptDetailsRow($row);
            }

            return $data;
        }

        if (is_array($data['data'])) {
            $data['data'] = $this->decryptDetailsRow($data['data']);
        }

        return $data;
    }

    private function decryptDetailsRow(array $row): array
    {
        $encrypted = $row['details_encrypted'] ?? null;
        $decrypted = is_string($encrypted) ? $this->decryptPayload($encrypted) : null;
        $decoded = is_string($decrypted) ? json_decode($decrypted, true) : null;

        $row['details'] = is_array($decoded) ? $decoded : [];
        $row['ip_address'] = $this->decryptOptionalField($row['ip_address_encrypted'] ?? null);
        $row['user_agent'] = $this->decryptOptionalField($row['user_agent_encrypted'] ?? null);

        return $row;
    }

    private function decryptOptionalField(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $this->decryptPayload($value) : null;
    }

    private function encryptPayload(string $json): ?string
    {
        try {
            return base64_encode(service('encrypter')->encrypt($json));
        } catch (\Throwable $e) {
            log_message('error', 'Activity log encryption failed: {message}', ['message' => $e->getMessage()]);

            return null;
        }
    }

    private function decryptPayload(string $encrypted): ?string
    {
        $decoded = base64_decode($encrypted, true);
        if ($decoded === false) {
            return null;
        }

        try {
            return service('encrypter')->decrypt($decoded);
        } catch (\Throwable) {
            return null;
        }
    }
}
