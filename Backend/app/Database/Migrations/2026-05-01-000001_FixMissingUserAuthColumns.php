<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class FixMissingUserAuthColumns extends Migration
{
    public function up()
    {
        $fields = [];

        if (! $this->db->fieldExists('deleted_at', 'users')) {
            $fields['deleted_at'] = [
                'type'  => 'DATETIME',
                'null'  => true,
                'after' => 'updated_at',
            ];
        }

        if (! $this->db->fieldExists('phone_last4', 'users')) {
            $fields['phone_last4'] = [
                'type'       => 'CHAR',
                'constraint' => 4,
                'null'       => true,
                'after'      => 'phone',
            ];
        }

        if (! $this->db->fieldExists('failed_login_attempts', 'users')) {
            $fields['failed_login_attempts'] = [
                'type'       => 'INT',
                'constraint' => 5,
                'unsigned'   => true,
                'default'    => 0,
                'after'      => 'email_verified_at',
            ];
        }

        if (! $this->db->fieldExists('locked_until', 'users')) {
            $fields['locked_until'] = [
                'type'  => 'DATETIME',
                'null'  => true,
                'after' => 'failed_login_attempts',
            ];
        }

        if (! $this->db->fieldExists('last_login_at', 'users')) {
            $fields['last_login_at'] = [
                'type'  => 'DATETIME',
                'null'  => true,
                'after' => 'locked_until',
            ];
        }

        if (! $this->db->fieldExists('password_changed_at', 'users')) {
            $fields['password_changed_at'] = [
                'type'  => 'DATETIME',
                'null'  => true,
                'after' => 'last_login_at',
            ];
        }

        if (! $this->db->fieldExists('otp', 'users')) {
            $fields['otp'] = [
                'type'       => 'VARCHAR',
                'constraint' => 6,
                'null'       => true,
                'after'      => 'email_verified_at',
            ];
        }

        if (! $this->db->fieldExists('otp_expire', 'users')) {
            $fields['otp_expire'] = [
                'type'  => 'DATETIME',
                'null'  => true,
                'after' => 'otp',
            ];
        }

        if (! $this->db->fieldExists('otp_attempts', 'users')) {
            $fields['otp_attempts'] = [
                'type'       => 'INT',
                'constraint' => 5,
                'unsigned'   => true,
                'default'    => 0,
                'after'      => 'otp_expire',
            ];
        }

        if ($fields !== []) {
            $this->forge->addColumn('users', $fields);
        }
    }

    public function down()
    {
        $columns = [];

        foreach (['deleted_at', 'phone_last4', 'failed_login_attempts', 'locked_until', 'last_login_at', 'password_changed_at', 'otp', 'otp_expire', 'otp_attempts'] as $column) {
            if ($this->db->fieldExists($column, 'users')) {
                $columns[] = $column;
            }
        }

        if ($columns !== []) {
            $this->forge->dropColumn('users', $columns);
        }
    }
}