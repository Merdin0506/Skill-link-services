<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddSoftDeleteToServiceRecords extends Migration
{
    public function up()
    {
        $this->forge->addColumn('service_records', [
            'deleted_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'after' => 'updated_at',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('service_records', 'deleted_at');
    }
}
