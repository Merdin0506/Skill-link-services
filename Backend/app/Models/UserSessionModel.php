<?php

namespace App\Models;

use CodeIgniter\Model;

class UserSessionModel extends Model
{
    protected $table = 'user_sessions';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $protectFields = true;
    protected $allowedFields = [
        'user_id',
        'session_key',
        'session_type',
        'status',
        'ip_address',
        'user_agent',
        'device_label',
        'last_activity_at',
        'logged_in_at',
        'logged_out_at',
        'expires_at',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    public function findBySessionKey(string $sessionKey): ?array
    {
        $session = $this->where('session_key', $sessionKey)->first();

        return is_array($session) ? $session : null;
    }

    public function getActiveSessionsWithUsers(int $limit = 20): array
    {
        return $this->select('user_sessions.*, users.first_name, users.last_name, users.email, users.user_type')
            ->join('users', 'users.id = user_sessions.user_id', 'left')
            ->where('user_sessions.status', 'active')
            ->orderBy('user_sessions.last_activity_at', 'DESC')
            ->findAll($limit);
    }

    public function countActiveSessions(): int
    {
        return $this->where('status', 'active')->countAllResults();
    }

    public function getActiveSessionsForUser(int $userId): array
    {
        return $this->where('user_id', $userId)
            ->where('status', 'active')
            ->orderBy('last_activity_at', 'DESC')
            ->findAll();
    }
}
