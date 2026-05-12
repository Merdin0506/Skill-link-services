<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddEmailChangeOtpFieldsToUsers extends Migration
{
    public function up()
    {
        $fields = [];

        if (! $this->db->fieldExists('pending_email', 'users')) {
            $fields['pending_email'] = [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'after' => 'email',
            ];
        }

        if (! $this->db->fieldExists('email_change_otp', 'users')) {
            $fields['email_change_otp'] = [
                'type' => 'VARCHAR',
                'constraint' => 6,
                'null' => true,
                'after' => 'pending_email',
            ];
        }

        if (! $this->db->fieldExists('email_change_otp_expire', 'users')) {
            $fields['email_change_otp_expire'] = [
                'type' => 'DATETIME',
                'null' => true,
                'after' => 'email_change_otp',
            ];
        }

        if (! $this->db->fieldExists('email_change_otp_attempts', 'users')) {
            $fields['email_change_otp_attempts'] = [
                'type' => 'INT',
                'constraint' => 5,
                'unsigned' => true,
                'default' => 0,
                'after' => 'email_change_otp_expire',
            ];
        }

        if ($fields !== []) {
            $this->forge->addColumn('users', $fields);
        }
    }

    public function down()
    {
        foreach (['pending_email', 'email_change_otp', 'email_change_otp_expire', 'email_change_otp_attempts'] as $column) {
            if ($this->db->fieldExists($column, 'users')) {
                $this->forge->dropColumn('users', $column);
            }
        }
    }
}
