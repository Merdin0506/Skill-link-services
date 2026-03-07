<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateUsersTable extends Migration
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
            'first_name' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => false,
            ],
            'last_name' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => false,
            ],
            'email' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'unique' => true,
                'null' => false,
            ],
            'password' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => false,
            ],
            'phone' => [
                'type' => 'VARCHAR',
                'constraint' => '20',
                'null' => true,
            ],
            'address' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'user_type' => [
                'type' => 'ENUM',
                'constraint' => ['super_admin', 'admin', 'finance', 'worker', 'customer'],
                'default' => 'customer',
            ],
            'status' => [
                'type' => 'ENUM',
                'constraint' => ['active', 'inactive', 'suspended'],
                'default' => 'active',
            ],
            'skills' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'JSON format for workers skills',
            ],
            'experience_years' => [
                'type' => 'INT',
                'constraint' => 2,
                'null' => true,
                'comment' => 'For workers',
            ],
            'commission_rate' => [
                'type' => 'DECIMAL',
                'constraint' => '5,2',
                'default' => '20.00',
                'comment' => 'Commission percentage for workers',
            ],
            'profile_image' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
            ],
            'email_verified_at' => [
                'type' => 'DATETIME',
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
        $this->forge->addKey('user_type');
        $this->forge->createTable('users');
    }

    public function down()
    {
        $this->forge->dropTable('users');
    }
}
