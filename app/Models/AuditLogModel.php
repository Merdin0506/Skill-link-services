<?php
namespace App\Models;
use CodeIgniter\Model;

class AuditLogModel extends Model
{
    protected $table = 'audit_logs';
    protected $allowedFields = [
        'event_type', 'user_email', 'ip_address', 'user_agent', 'details', 'created_at'
    ];
    public $timestamps = false;
}
