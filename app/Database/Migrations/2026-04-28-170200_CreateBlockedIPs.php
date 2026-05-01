<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateBlockedIPs extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'ip_address' => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => false],
            'reason' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
            'blocked_by' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'is_temporary' => ['type' => 'BOOLEAN', 'default' => true],
            'blocked_until' => ['type' => 'DATETIME', 'null' => true],
            'attempts_count' => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'last_attempt' => ['type' => 'DATETIME', 'null' => true],
            'is_active' => ['type' => 'BOOLEAN', 'default' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => false],
            'updated_at' => ['type' => 'DATETIME', 'null' => false],
        ]);
        
        $this->forge->addKey('id', true);
        $this->forge->addKey('ip_address');
        $this->forge->addKey('is_active');
        $this->forge->addKey('blocked_until');
        $this->forge->createTable('blocked_ips');
    }

    public function down()
    {
        $this->forge->dropTable('blocked_ips');
    }
}
