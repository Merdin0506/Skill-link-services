<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddWorkerCoverageFieldsToUsers extends Migration
{
    public function up()
    {
        $fields = [
            'service_city' => [
                'type' => 'VARCHAR',
                'constraint' => 120,
                'null' => true,
                'after' => 'address',
                'comment' => 'Primary city or service area for worker matching',
            ],
            'service_radius_km' => [
                'type' => 'DECIMAL',
                'constraint' => '5,2',
                'null' => true,
                'default' => 20.00,
                'after' => 'service_city',
                'comment' => 'Worker coverage radius in kilometers',
            ],
            'work_latitude' => [
                'type' => 'DECIMAL',
                'constraint' => '10,8',
                'null' => true,
                'after' => 'service_radius_km',
            ],
            'work_longitude' => [
                'type' => 'DECIMAL',
                'constraint' => '11,8',
                'null' => true,
                'after' => 'work_latitude',
            ],
        ];

        $this->forge->addColumn('users', $fields);
        $this->db->query('CREATE INDEX users_service_city_idx ON users (service_city)');
    }

    public function down()
    {
        $this->db->query('DROP INDEX users_service_city_idx ON users');
        $this->forge->dropColumn('users', [
            'service_city',
            'service_radius_km',
            'work_latitude',
            'work_longitude',
        ]);
    }
}
