<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAuditLogs extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'auto_increment' => true],
            'event_type'  => ['type' => 'VARCHAR', 'constraint' => 50],
            'user_email'  => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'ip_address'  => ['type' => 'VARCHAR', 'constraint' => 45],
            'user_agent'  => ['type' => 'TEXT'],
            'details'     => ['type' => 'TEXT', 'null' => true],
            'created_at'  => ['type' => 'DATETIME', 'null' => false],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('audit_logs');
    }

    public function down()
    {
        $this->forge->dropTable('audit_logs');
    }
}
