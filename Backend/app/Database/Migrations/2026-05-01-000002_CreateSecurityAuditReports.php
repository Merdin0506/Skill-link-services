<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSecurityAuditReports extends Migration
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
            'period' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
            ],
            'start_date' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
            'end_date' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
            'total_events' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
            ],
            'failed_logins' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
            ],
            'successful_logins' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
            ],
            'suspicious_activities' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
            ],
            'blocked_ips' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
            ],
            'critical_alerts' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
            ],
            'unread_notifications' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
            ],
            'summary_json' => [
                'type' => 'LONGTEXT',
                'null' => true,
            ],
            'generated_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('period');
        $this->forge->addKey('generated_at');
        $this->forge->createTable('security_audit_reports');
    }

    public function down()
    {
        $this->forge->dropTable('security_audit_reports');
    }
}
