<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSecurityNotifications extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'admin_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'title' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
            'message' => ['type' => 'TEXT', 'null' => false],
            'type' => ['type' => 'ENUM', 'constraint' => ['info', 'warning', 'danger', 'critical'], 'default' => 'info'],
            'priority' => ['type' => 'ENUM', 'constraint' => ['low', 'medium', 'high', 'critical'], 'default' => 'medium'],
            'is_read' => ['type' => 'BOOLEAN', 'default' => false],
            'action_required' => ['type' => 'BOOLEAN', 'default' => false],
            'related_user_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'ip_address' => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => false],
            'read_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        
        $this->forge->addKey('id', true);
        $this->forge->addKey('admin_id');
        $this->forge->addKey('is_read');
        $this->forge->addKey('type');
        $this->forge->addKey('priority');
        $this->forge->addKey('created_at');
        $this->forge->createTable('security_notifications');
    }

    public function down()
    {
        $this->forge->dropTable('security_notifications');
    }
}
