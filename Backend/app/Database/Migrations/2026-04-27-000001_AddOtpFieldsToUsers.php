<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddOtpFieldsToUsers extends Migration
{
    public function up()
    {
        $fields = [];

        if (! $this->db->fieldExists('otp', 'users')) {
            $fields['otp'] = [
                'type' => 'VARCHAR',
                'constraint' => 6,
                'null' => true,
                'after' => 'email_verified_at',
            ];
        }

        if (! $this->db->fieldExists('otp_expire', 'users')) {
            $fields['otp_expire'] = [
                'type' => 'DATETIME',
                'null' => true,
                'after' => 'otp',
            ];
        }

        if (! $this->db->fieldExists('otp_attempts', 'users')) {
            $fields['otp_attempts'] = [
                'type' => 'INT',
                'constraint' => 5,
                'unsigned' => true,
                'default' => 0,
                'after' => 'otp_expire',
            ];
        }

        if ($fields !== []) {
            $this->forge->addColumn('users', $fields);
        }
    }

    public function down()
    {
        $columns = [];

        foreach (['otp', 'otp_expire', 'otp_attempts'] as $column) {
            if ($this->db->fieldExists($column, 'users')) {
                $columns[] = $column;
            }
        }

        if ($columns !== []) {
            $this->forge->dropColumn('users', $columns);
        }
    }
}
