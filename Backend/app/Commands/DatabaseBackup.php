<?php

namespace App\Commands;

use App\Libraries\DatabaseBackupManager;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class DatabaseBackup extends BaseCommand
{
    protected $group = 'Database';
    protected $name = 'db:backup';
    protected $description = 'Create a compressed database backup and prune expired backup files.';

    public function run(array $params)
    {
        $manager = new DatabaseBackupManager();
        $backup = $manager->createBackup();

        CLI::write('Backup created successfully.', 'green');
        CLI::write('File: ' . $backup['filename']);
        CLI::write('Path: ' . $backup['path']);
        CLI::write('Size: ' . number_format($backup['size']) . ' bytes');
    }
}
