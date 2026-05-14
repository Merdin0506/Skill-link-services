<?php

namespace App\Controllers\API;

use App\Controllers\BaseController;
use App\Libraries\DatabaseBackupManager;
use CodeIgniter\API\ResponseTrait;

class BackupsController extends BaseController
{
    use ResponseTrait;

    public function index()
    {
        try {
            $manager = new DatabaseBackupManager();
            $backups = $manager->listBackups();
            $latestBackup = $backups[0] ?? null;
            $totalSize = array_sum(array_column($backups, 'size'));

            return $this->respond([
                'status' => 'success',
                'data' => [
                    'backups' => $backups,
                    'summary' => [
                        'count' => count($backups),
                        'total_size' => $totalSize,
                        'latest_backup' => $latestBackup,
                    ],
                ],
            ]);
        } catch (\Throwable $e) {
            return $this->fail('Failed to load backups: ' . $e->getMessage());
        }
    }

    public function create()
    {
        try {
            $manager = new DatabaseBackupManager();
            $backup = $manager->createBackup();

            return $this->respondCreated([
                'status' => 'success',
                'message' => 'Backup created successfully.',
                'data' => $backup,
            ]);
        } catch (\Throwable $e) {
            return $this->fail('Failed to create backup: ' . $e->getMessage());
        }
    }

    public function restore()
    {
        $filename = trim((string) ($this->request->getVar('backup_file') ?? $this->request->getVar('filename') ?? ''));
        if ($filename === '') {
            return $this->fail('Backup file is required.');
        }

        try {
            $manager = new DatabaseBackupManager();
            $manager->restoreBackup($filename);

            return $this->respond([
                'status' => 'success',
                'message' => 'Database restored successfully. A pre-restore backup was created automatically.',
            ]);
        } catch (\Throwable $e) {
            return $this->fail('Failed to restore backup: ' . $e->getMessage());
        }
    }
}
