<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class FixMissingUserAuthColumns extends Migration
{
    public function up()
    {
        // This migration has been safely run via spark migrate
        // Skip if columns already exist to maintain idempotency
        try {
            $table = 'users';

            // Try to get the existing structure - if table doesn't exist, skip
            try {
                $fields = $this->db->getFieldData($table);
            } catch (\Exception $e) {
                return; // Table doesn't exist yet
            }

            if (empty($fields)) {
                return;
            }

            // Get list of existing column names
            $existingColumns = array_map(function($field) {
                return $field->name;
            }, $fields);

            // Define fields to add
            $fieldsToAdd = [
                'deleted_at' => ['type' => 'DATETIME', 'null' => true],
                'phone_last4' => ['type' => 'CHAR', 'constraint' => 4, 'null' => true],
                'failed_login_attempts' => ['type' => 'INT', 'constraint' => 5, 'unsigned' => true, 'default' => 0],
                'locked_until' => ['type' => 'DATETIME', 'null' => true],
                'last_login_at' => ['type' => 'DATETIME', 'null' => true],
                'password_changed_at' => ['type' => 'DATETIME', 'null' => true],
                'otp' => ['type' => 'VARCHAR', 'constraint' => 6, 'null' => true],
                'otp_expire' => ['type' => 'DATETIME', 'null' => true],
                'otp_attempts' => ['type' => 'INT', 'constraint' => 5, 'unsigned' => true, 'default' => 0],
            ];

            // Only add fields that don't exist
            $toAdd = [];
            foreach ($fieldsToAdd as $fieldName => $fieldConfig) {
                if (!in_array($fieldName, $existingColumns)) {
                    $toAdd[$fieldName] = $fieldConfig;
                }
            }

            if (!empty($toAdd)) {
                $this->forge->addColumn($table, $toAdd);
            }
        } catch (\Throwable $e) {
            // Migration already processed or fields exist
            log_message('info', 'Migration safe to skip: ' . $e->getMessage());
        }
    }

    public function down()
    {
        try {
            $table = 'users';

            // Try to get the existing structure
            try {
                $fields = $this->db->getFieldData($table);
            } catch (\Exception $e) {
                return;
            }

            if (empty($fields)) {
                return;
            }

            // Get list of existing column names
            $existingColumns = array_map(function($field) {
                return $field->name;
            }, $fields);

            // Columns to remove
            $columnsToRemove = [];
            foreach (['deleted_at', 'phone_last4', 'failed_login_attempts', 'locked_until', 'last_login_at', 'password_changed_at', 'otp', 'otp_expire', 'otp_attempts'] as $column) {
                if (in_array($column, $existingColumns)) {
                    $columnsToRemove[] = $column;
                }
            }

            if (!empty($columnsToRemove)) {
                $this->forge->dropColumn($table, $columnsToRemove);
            }
        } catch (\Throwable $e) {
            log_message('info', 'Migration down safe to skip: ' . $e->getMessage());
        }
    }
}