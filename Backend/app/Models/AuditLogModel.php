<?php

namespace App\Models;

use CodeIgniter\Model;

class AuditLogModel extends Model
{
    protected $table = 'audit_logs';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'event_type',
        'user_email',
        'ip_address',
        'user_agent',
        'details',
        'created_at',
    ];

    protected $useTimestamps = false;
    protected $returnType = 'array';
}
