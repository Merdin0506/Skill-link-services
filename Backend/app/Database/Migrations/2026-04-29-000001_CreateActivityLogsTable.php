<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateActivityLogsTable extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('activity_logs')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'actor_user_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
            ],
            'target_user_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
            ],
            'event_type' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
            ],
            'action' => [
                'type' => 'VARCHAR',
                'constraint' => 80,
            ],
            'outcome' => [
                'type' => 'VARCHAR',
                'constraint' => 30,
                'default' => 'success',
            ],
            'source' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'default' => 'web',
            ],
            'ip_address_encrypted' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'Encrypted request IP address',
            ],
            'user_agent_encrypted' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'Encrypted request user agent',
            ],
            'session_key_hash' => [
                'type' => 'VARCHAR',
                'constraint' => 64,
                'null' => true,
                'comment' => 'SHA-256 hash of tracked session key',
            ],
            'details_encrypted' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'Encrypted JSON activity payload',
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('actor_user_id');
        $this->forge->addKey('target_user_id');
        $this->forge->addKey('event_type');
        $this->forge->addKey('action');
        $this->forge->addKey('outcome');
        $this->forge->addKey('session_key_hash');
        $this->forge->addKey('created_at');
        $this->forge->addForeignKey('actor_user_id', 'users', 'id', 'CASCADE', 'SET NULL');
        $this->forge->addForeignKey('target_user_id', 'users', 'id', 'CASCADE', 'SET NULL');
        $this->forge->createTable('activity_logs');
    }

    public function down()
    {
        $this->forge->dropTable('activity_logs', true);
    }
}
