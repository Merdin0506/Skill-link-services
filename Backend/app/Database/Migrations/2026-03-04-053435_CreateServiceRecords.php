<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateServiceRecords extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'booking_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'customer_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => false,
            ],
            'provider_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'service_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => false,
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['pending', 'scheduled', 'in_progress', 'completed', 'cancelled'],
                'default'    => 'pending',
            ],
            'scheduled_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'started_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'completed_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'address_text' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'labor_fee' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'default'    => '0.00',
            ],
            'platform_fee' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'default'    => '0.00',
            ],
            'total_amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'default'    => '0.00',
            ],
            'payment_status' => [
                'type'       => 'ENUM',
                'constraint' => ['unpaid', 'partial', 'paid', 'refunded'],
                'default'    => 'unpaid',
            ],
            'payment_ref' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'customer_note' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'provider_note' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'admin_note' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('customer_id');
        $this->forge->addKey('provider_id');
        $this->forge->addKey('service_id');
        $this->forge->addKey('booking_id');
        $this->forge->addKey('status');
        $this->forge->addKey('payment_status');
        $this->forge->addKey('scheduled_at');

        $this->forge->addForeignKey('customer_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('provider_id', 'users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('service_id', 'services', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('booking_id', 'bookings', 'id', 'SET NULL', 'CASCADE');

        $this->forge->createTable('service_records');
    }

    public function down()
    {
        $this->forge->dropTable('service_records', true);
    }
}
