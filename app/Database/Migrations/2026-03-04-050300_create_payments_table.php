<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePaymentsTable extends Migration
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
            'booking_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => false,
            ],
            'payment_reference' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'unique' => true,
                'null' => false,
            ],
            'amount' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'null' => false,
            ],
            'payment_method' => [
                'type' => 'ENUM',
                'constraint' => ['cash', 'gcash', 'paymaya', 'bank_transfer', 'credit_card'],
                'null' => false,
            ],
            'payment_type' => [
                'type' => 'ENUM',
                'constraint' => ['customer_payment', 'worker_payout'],
                'null' => false,
            ],
            'status' => [
                'type' => 'ENUM',
                'constraint' => ['pending', 'processing', 'completed', 'failed', 'refunded'],
                'default' => 'pending',
            ],
            'transaction_id' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
                'comment' => 'External payment gateway transaction ID',
            ],
            'paid_by' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
                'comment' => 'User ID who made the payment',
            ],
            'paid_to' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
                'comment' => 'User ID who received the payment',
            ],
            'payment_date' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'processed_by' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
                'comment' => 'Admin/Finance who processed the payment',
            ],
            'notes' => [
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
        $this->forge->addKey('booking_id');
        $this->forge->addKey('payment_method');
        $this->forge->addKey('payment_type');
        $this->forge->addKey('status');
        $this->forge->addKey('payment_date');
        
        $this->forge->addForeignKey('booking_id', 'bookings', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('paid_by', 'users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('paid_to', 'users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('processed_by', 'users', 'id', 'SET NULL', 'CASCADE');
        
        $this->forge->createTable('payments');
    }

    public function down()
    {
        $this->forge->dropTable('payments');
    }
}
