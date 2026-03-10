<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateReviewsTable extends Migration
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
            'customer_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => false,
            ],
            'worker_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => false,
            ],
            'rating' => [
                'type' => 'INT',
                'constraint' => 1,
                'null' => false,
                'comment' => 'Rating from 1 to 5',
            ],
            'comment' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'service_quality' => [
                'type' => 'INT',
                'constraint' => 1,
                'null' => false,
                'comment' => 'Service quality rating 1-5',
            ],
            'timeliness' => [
                'type' => 'INT',
                'constraint' => 1,
                'null' => false,
                'comment' => 'Timeliness rating 1-5',
            ],
            'professionalism' => [
                'type' => 'INT',
                'constraint' => 1,
                'null' => false,
                'comment' => 'Professionalism rating 1-5',
            ],
            'would_recommend' => [
                'type' => 'BOOLEAN',
                'default' => true,
            ],
            'status' => [
                'type' => 'ENUM',
                'constraint' => ['published', 'hidden', 'flagged'],
                'default' => 'published',
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
        $this->forge->addKey('customer_id');
        $this->forge->addKey('worker_id');
        $this->forge->addKey('rating');
        $this->forge->addKey('status');
        
        $this->forge->addForeignKey('booking_id', 'bookings', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('customer_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('worker_id', 'users', 'id', 'CASCADE', 'CASCADE');
        
        $this->forge->createTable('reviews');
    }

    public function down()
    {
        $this->forge->dropTable('reviews');
    }
}
