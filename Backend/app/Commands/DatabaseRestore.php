<?php

namespace App\Commands;

use App\Libraries\DatabaseBackupManager;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use InvalidArgumentException;

class DatabaseRestore extends BaseCommand
{
    protected $group = 'Database';
    protected $name = 'db:restore';
    protected $description = 'Restore the database from a backup file. A pre-restore backup is created automatically.';

    public function run(array $params)
    {
        $source = $params[0] ?? null;
        if ($source === null) {
            throw new InvalidArgumentException('Please provide a backup filename or absolute path.');
        }

        $manager = new DatabaseBackupManager();
        $manager->restoreBackup($source);

        CLI::write('Database restore completed successfully.', 'green');
    }
}
