<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSecurityEvents extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'user_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'email' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'event_type' => ['type' => 'ENUM', 'constraint' => [
                'login_success', 'login_failed', 'logout', 'password_change',
                'account_locked', 'account_unlocked', 'unauthorized_access',
                'suspicious_activity', 'brute_force_attempt', 'sql_injection_attempt',
                'xss_attempt', 'csrf_attempt', 'admin_access', 'permission_denied'
            ], 'null' => false],
            'severity' => ['type' => 'ENUM', 'constraint' => ['low', 'medium', 'high', 'critical'], 'default' => 'medium'],
            'ip_address' => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => false],
            'user_agent' => ['type' => 'TEXT', 'null' => true],
            'request_uri' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'request_method' => ['type' => 'VARCHAR', 'constraint' => 10, 'null' => true],
            'details' => ['type' => 'TEXT', 'null' => true],
            'is_blocked' => ['type' => 'BOOLEAN', 'default' => false],
            'block_reason' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => false],
        ]);
        
        $this->forge->addKey('id', true);
        $this->forge->addKey('user_id');
        $this->forge->addKey('event_type');
        $this->forge->addKey('severity');
        $this->forge->addKey('ip_address');
        $this->forge->addKey('is_blocked');
        $this->forge->addKey('created_at');
        $this->forge->createTable('security_events');
    }

    public function down()
    {
        $this->forge->dropTable('security_events');
    }
}
