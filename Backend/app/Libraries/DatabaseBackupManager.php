<?php

namespace App\Libraries;

use Config\Backup as BackupConfig;
use Config\Database as DatabaseConfig;
use RuntimeException;

class DatabaseBackupManager
{
    public function __construct(
        private ?BackupConfig $backupConfig = null,
        private ?DatabaseConfig $databaseConfig = null,
    ) {
        $this->backupConfig ??= config('Backup');
        $this->databaseConfig ??= config('Database');
    }

    /**
     * @return array{path: string, size: int, filename: string}
     */
    public function createBackup(?string $prefix = null): array
    {
        $db = $this->getDefaultConnection();
        $directory = rtrim($this->backupConfig->storagePath, '\\/');

        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException('Unable to create backup directory: ' . $directory);
        }

        $timestamp = date('Ymd_His');
        $base = $prefix ? $prefix . '_' . $timestamp : ($db['database'] . '_' . $timestamp);
        $filename = $base . '.sql.gz';
        $path = $directory . DIRECTORY_SEPARATOR . $filename;

        $result = $this->runProcess($this->buildDumpCommand($db));
        $compressed = gzencode($result['stdout'], 9);

        if ($compressed === false) {
            throw new RuntimeException('Failed to compress database dump.');
        }

        if (file_put_contents($path, $compressed) === false) {
            throw new RuntimeException('Failed to write backup file: ' . $path);
        }

        $this->cleanupExpiredBackups();

        return [
            'path' => $path,
            'size' => filesize($path) ?: 0,
            'filename' => $filename,
        ];
    }

    public function restoreBackup(string $sourcePath): void
    {
        $db = $this->getDefaultConnection();
        $resolvedPath = $this->resolveBackupPath($sourcePath);

        if (!is_file($resolvedPath)) {
            throw new RuntimeException('Backup file not found: ' . $resolvedPath);
        }

        if ($this->backupConfig->createPreRestoreBackup) {
            $this->createBackup('pre_restore');
        }

        $contents = file_get_contents($resolvedPath);
        if ($contents === false) {
            throw new RuntimeException('Unable to read backup file: ' . $resolvedPath);
        }

        if (str_ends_with(strtolower($resolvedPath), '.gz')) {
            $contents = gzdecode($contents);
            if ($contents === false) {
                throw new RuntimeException('Unable to decompress backup file: ' . $resolvedPath);
            }
        }

        $this->runProcess($this->buildRestoreCommand($db), $contents);
    }

    /**
     * @return list<array{path: string, filename: string, size: int, modified_at: int}>
     */
    public function listBackups(): array
    {
        $directory = rtrim($this->backupConfig->storagePath, '\\/');
        if (!is_dir($directory)) {
            return [];
        }

        $files = glob($directory . DIRECTORY_SEPARATOR . '*.sql*') ?: [];
        rsort($files);

        $backups = [];
        foreach ($files as $file) {
            $backups[] = [
                'path' => $file,
                'filename' => basename($file),
                'size' => filesize($file) ?: 0,
                'modified_at' => filemtime($file) ?: 0,
            ];
        }

        return $backups;
    }

    public function cleanupExpiredBackups(): void
    {
        $retentionDays = max(1, $this->backupConfig->retentionDays);
        $cutoff = time() - ($retentionDays * 86400);

        foreach ($this->listBackups() as $backup) {
            if ($backup['modified_at'] < $cutoff) {
                @unlink($backup['path']);
            }
        }
    }

    /**
     * @param array<string, mixed> $db
     */
    private function buildDumpCommand(array $db): string
    {
        $parts = [
            $this->quote($this->backupConfig->mysqlDumpBinary),
            '--host=' . $this->quote((string) $db['hostname']),
            '--port=' . $this->quote((string) ($db['port'] ?? 3306)),
            '--user=' . $this->quote((string) $db['username']),
            '--single-transaction',
            '--routines',
            '--triggers',
            '--events',
            '--skip-comments',
            '--default-character-set=utf8mb4',
        ];

        if (($db['password'] ?? '') !== '') {
            $parts[] = '--password=' . $this->quote((string) $db['password']);
        }

        $parts[] = $this->quote((string) $db['database']);

        return implode(' ', $parts);
    }

    /**
     * @param array<string, mixed> $db
     */
    private function buildRestoreCommand(array $db): string
    {
        $parts = [
            $this->quote($this->backupConfig->mysqlBinary),
            '--host=' . $this->quote((string) $db['hostname']),
            '--port=' . $this->quote((string) ($db['port'] ?? 3306)),
            '--user=' . $this->quote((string) $db['username']),
            '--default-character-set=utf8mb4',
        ];

        if (($db['password'] ?? '') !== '') {
            $parts[] = '--password=' . $this->quote((string) $db['password']);
        }

        $parts[] = $this->quote((string) $db['database']);

        return implode(' ', $parts);
    }

    /**
     * @return array{stdout: string, stderr: string}
     */
    private function runProcess(string $command, ?string $stdin = null): array
    {
        $descriptorSpec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($command, $descriptorSpec, $pipes);
        if (!is_resource($process)) {
            throw new RuntimeException('Failed to start process: ' . $command);
        }

        if ($stdin !== null) {
            fwrite($pipes[0], $stdin);
        }
        fclose($pipes[0]);

        $stdout = stream_get_contents($pipes[1]) ?: '';
        fclose($pipes[1]);

        $stderr = stream_get_contents($pipes[2]) ?: '';
        fclose($pipes[2]);

        $exitCode = proc_close($process);
        if ($exitCode !== 0) {
            throw new RuntimeException(trim($stderr) !== '' ? trim($stderr) : 'Database command failed.');
        }

        return ['stdout' => $stdout, 'stderr' => $stderr];
    }

    /**
     * @return array<string, mixed>
     */
    private function getDefaultConnection(): array
    {
        $defaultGroup = $this->databaseConfig->defaultGroup;
        $db = $this->databaseConfig->{$defaultGroup} ?? null;

        if (!is_array($db) || ($db['DBDriver'] ?? null) !== 'MySQLi') {
            throw new RuntimeException('Backup commands currently support MySQLi connections only.');
        }

        return $db;
    }

    private function resolveBackupPath(string $sourcePath): string
    {
        if (is_file($sourcePath)) {
            return $sourcePath;
        }

        return rtrim($this->backupConfig->storagePath, '\\/') . DIRECTORY_SEPARATOR . $sourcePath;
    }

    private function quote(string $value): string
    {
        return escapeshellarg($value);
    }
}
