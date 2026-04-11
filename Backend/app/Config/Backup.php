<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Backup extends BaseConfig
{
    public string $storagePath = WRITEPATH . 'backups' . DIRECTORY_SEPARATOR . 'database';

    public int $retentionDays = 14;

    public string $mysqlDumpBinary = 'mysqldump';

    public string $mysqlBinary = 'mysql';

    public bool $createPreRestoreBackup = true;
}
