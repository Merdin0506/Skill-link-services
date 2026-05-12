<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddResumePathToUsers extends Migration
{
    public function up()
    {
        if (! $this->db->fieldExists('resume_path', 'users')) {
            $this->forge->addColumn('users', [
                'resume_path' => [
                    'type' => 'VARCHAR',
                    'constraint' => '255',
                    'null' => true,
                    'after' => 'address',
                    'comment' => 'Stored worker resume file path',
                ],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('resume_path', 'users')) {
            $this->forge->dropColumn('users', 'resume_path');
        }
    }
}
