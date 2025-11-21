<?php

namespace App\Core;

class Config {
    private static ?array $config = null;

    public static function load(): void {
        if (self::$config !== null) {
            return;
        }

        $envFile = __DIR__ . '/../../../.env';
        if (!file_exists($envFile)) {
            throw new \RuntimeException('.env file not found');
        }

        self::$config = [];
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            // Skip comments
            if (strpos(trim($line), '#') === 0) {
                continue;
            }

            // Parse KEY=VALUE
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);

                // Remove quotes if present
                if (preg_match('/^(["\'])(.*)\1$/', $value, $matches)) {
                    $value = $matches[2];
                }

                self::$config[$key] = $value;
            }
        }
    }

    public static function get(string $key, $default = null) {
        if (self::$config === null) {
            self::load();
        }

        return self::$config[$key] ?? $default;
    }

    public static function getDatabaseConfig(): array {
        $driver = self::get('DB_DRIVER', 'pdo_sqlite');

        $config = [
            'driver' => $driver,
        ];

        if ($driver === 'pdo_sqlite') {
            $dbPath = self::get('DB_PATH', 'backend/database.sqlite');
            // Make path absolute if relative
            if ($dbPath[0] !== '/') {
                $dbPath = __DIR__ . '/../../../' . $dbPath;
            }
            $config['path'] = $dbPath;
        } else {
            $config['host'] = self::get('DB_HOST', 'localhost');
            $config['dbname'] = self::get('DB_NAME', 'realworld');
            $config['user'] = self::get('DB_USER', 'root');
            $config['password'] = self::get('DB_PASSWORD', '');

            if ($port = self::get('DB_PORT')) {
                $config['port'] = (int) $port;
            }

            if ($charset = self::get('DB_CHARSET')) {
                $config['charset'] = $charset;
            }
        }

        return $config;
    }
}
