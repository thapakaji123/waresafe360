<?php

declare(strict_types=1);

namespace Safe360\Core;

use PDO;

final class Database
{
    private static ?PDO $connection = null;

    public static function connection(): PDO
    {
        if (self::$connection instanceof PDO) {
            return self::$connection;
        }

        $dsn = Config::get('DB_DSN');
        if ($dsn === '') {
            throw new \RuntimeException('DB_DSN is not configured. Copy .env.example to .env and run the setup command.');
        }

        self::$connection = new PDO(
            $dsn,
            Config::get('DB_USER'),
            Config::get('DB_PASSWORD'),
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );

        if (str_starts_with($dsn, 'sqlite:')) {
            self::$connection->exec('PRAGMA foreign_keys = ON');
        }

        return self::$connection;
    }

    public static function reset(): void
    {
        self::$connection = null;
    }

    public static function driver(): string
    {
        return (string) self::connection()->getAttribute(PDO::ATTR_DRIVER_NAME);
    }
}

