<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateUserSessionsTable extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('user_sessions')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'user_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'session_key' => [
                'type' => 'VARCHAR',
                'constraint' => 128,
            ],
            'session_type' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'default' => 'web',
            ],
            'status' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'default' => 'active',
            ],
            'ip_address' => [
                'type' => 'VARCHAR',
                'constraint' => 45,
                'null' => true,
            ],
            'user_agent' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'device_label' => [
                'type' => 'VARCHAR',
                'constraint' => 120,
                'null' => true,
            ],
            'last_activity_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'logged_in_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'logged_out_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'expires_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('session_key');
        $this->forge->addKey(['user_id', 'status']);
        $this->forge->addKey('last_activity_at');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('user_sessions');
    }

    public function down()
    {
        $this->forge->dropTable('user_sessions', true);
    }
}
