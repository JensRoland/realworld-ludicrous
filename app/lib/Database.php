<?php

namespace App\Lib;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;

class Database
{
    private static ?Connection $instance = null;
    private static bool $initialized = false;

    public static function getConnection(): Connection
    {
        if (self::$instance === null) {
            $config = Config::getDatabaseConfig();

            if ($config['driver'] === 'pdo_sqlite') {
                $config['driverOptions'] = [
                    \PDO::ATTR_PERSISTENT => true,
                ];
            }

            self::$instance = DriverManager::getConnection($config);

            if (!self::$initialized && $config['driver'] === 'pdo_sqlite') {
                self::$instance->executeStatement("PRAGMA foreign_keys = ON");
                self::$instance->executeStatement("PRAGMA journal_mode = WAL");
                self::$instance->executeStatement("PRAGMA synchronous = NORMAL");
                self::$instance->executeStatement("PRAGMA cache_size = -64000");
                self::$instance->executeStatement("PRAGMA temp_store = MEMORY");
                self::$initialized = true;
            }
        }

        return self::$instance;
    }
}
