<?php

namespace App\Database\Migrations;

use App\Libraries\SensitiveDataCipher;
use CodeIgniter\Database\Migration;

class AddSecureUserStorageControls extends Migration
{
    public function up()
    {
        $this->forge->modifyColumn('users', [
            'phone' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'Encrypted phone number at rest',
            ],
            'address' => [
                'type' => 'MEDIUMTEXT',
                'null' => true,
                'comment' => 'Encrypted address at rest',
            ],
        ]);

        $this->forge->addColumn('users', [
            'phone_last4' => [
                'type' => 'CHAR',
                'constraint' => 4,
                'null' => true,
                'after' => 'phone',
            ],
            'failed_login_attempts' => [
                'type' => 'INT',
                'constraint' => 5,
                'unsigned' => true,
                'default' => 0,
                'after' => 'email_verified_at',
            ],
            'locked_until' => [
                'type' => 'DATETIME',
                'null' => true,
                'after' => 'failed_login_attempts',
            ],
            'last_login_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'after' => 'locked_until',
            ],
            'password_changed_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'after' => 'last_login_at',
            ],
        ]);

        $this->db->query('CREATE INDEX users_phone_last4_idx ON users (phone_last4)');
        $this->db->query('CREATE INDEX users_role_status_idx ON users (user_type, status)');

        $cipher = null;
        try {
            $cipher = new SensitiveDataCipher();
        } catch (\Throwable) {
            $cipher = null;
        }

        $users = $this->db->table('users')->get()->getResultArray();
        foreach ($users as $user) {
            $phone = $user['phone'] ?? null;
            $address = $user['address'] ?? null;

            $update = [
                'phone_last4' => $cipher?->phoneLastFour($phone),
                'password_changed_at' => $user['created_at'] ?? date('Y-m-d H:i:s'),
            ];

            if ($cipher !== null && !empty($phone)) {
                $update['phone'] = $cipher->encrypt($phone);
            }

            if ($cipher !== null && !empty($address)) {
                $update['address'] = $cipher->encrypt($address);
            }

            $this->db->table('users')->where('id', $user['id'])->update($update);
        }
    }

    public function down()
    {
        $cipher = null;
        try {
            $cipher = new SensitiveDataCipher();
        } catch (\Throwable) {
            $cipher = null;
        }

        if ($cipher !== null) {
            $users = $this->db->table('users')->get()->getResultArray();
            foreach ($users as $user) {
                $update = [];
                if (!empty($user['phone'])) {
                    $update['phone'] = $cipher->decrypt($user['phone']);
                }
                if (!empty($user['address'])) {
                    $update['address'] = $cipher->decrypt($user['address']);
                }
                if ($update !== []) {
                    $this->db->table('users')->where('id', $user['id'])->update($update);
                }
            }
        }

        $this->db->query('DROP INDEX users_phone_last4_idx ON users');
        $this->db->query('DROP INDEX users_role_status_idx ON users');

        $this->forge->dropColumn('users', [
            'phone_last4',
            'failed_login_attempts',
            'locked_until',
            'last_login_at',
            'password_changed_at',
        ]);

        $this->forge->modifyColumn('users', [
            'phone' => [
                'type' => 'VARCHAR',
                'constraint' => '20',
                'null' => true,
            ],
            'address' => [
                'type' => 'TEXT',
                'null' => true,
            ],
        ]);
    }
}
