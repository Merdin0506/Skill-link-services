<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddSuperAdminRoleToUsers extends Migration
{
    public function up()
    {
        // Extend enum to include super_admin role.
        $this->db->query("ALTER TABLE users MODIFY user_type ENUM('super_admin','admin','finance','worker','customer') NOT NULL DEFAULT 'customer'");

        // Promote seeded primary admin account to super_admin.
        $this->db->table('users')
            ->where('email', 'admin@skilllink.com')
            ->update(['user_type' => 'super_admin']);
    }

    public function down()
    {
        // Demote super_admin accounts back to admin before enum rollback.
        $this->db->table('users')
            ->where('user_type', 'super_admin')
            ->update(['user_type' => 'admin']);

        $this->db->query("ALTER TABLE users MODIFY user_type ENUM('admin','finance','worker','customer') NOT NULL DEFAULT 'customer'");
    }
}
