<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPendingApprovalToUsersStatus extends Migration
{
    public function up()
    {
        $this->db->query("ALTER TABLE users MODIFY status ENUM('pending','active','inactive','suspended') NOT NULL DEFAULT 'active'");
    }

    public function down()
    {
        $this->db->query("ALTER TABLE users MODIFY status ENUM('active','inactive','suspended') NOT NULL DEFAULT 'active'");
    }
}
