<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSecuritySettings extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'brute_force_threshold' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 5,
            ],
            'block_duration_minutes' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 30,
            ],
            'sync_poll_seconds' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 20,
            ],
            'auto_block_enabled' => [
                'type' => 'BOOLEAN',
                'default' => true,
            ],
            'notify_on_failed_login' => [
                'type' => 'BOOLEAN',
                'default' => true,
            ],
            'notify_on_blocked_ip' => [
                'type' => 'BOOLEAN',
                'default' => true,
            ],
            'notify_on_suspicious_activity' => [
                'type' => 'BOOLEAN',
                'default' => true,
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
        $this->forge->createTable('security_settings');
    }

    public function down()
    {
        $this->forge->dropTable('security_settings', true);
    }
}
