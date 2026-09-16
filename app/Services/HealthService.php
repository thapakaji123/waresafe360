<?php

declare(strict_types=1);

namespace Safe360\Services;

use PDO;
use Safe360\Core\Config;
use Safe360\Core\Database;

final class HealthService
{
    private const REQUIRED_TABLES = [
        'roles',
        'users',
        'training_modules',
        'scenes',
        'hazards',
        'questions',
        'question_options',
        'training_attempts',
        'attempt_scene_progress',
        'hazard_responses',
        'achievements',
        'user_achievements',
    ];

    /** @return array<string, mixed> */
    public function snapshot(): array
    {
        $database = Database::connection();
        $driver = (string) $database->getAttribute(PDO::ATTR_DRIVER_NAME);
        $missingTables = array_values(array_diff(self::REQUIRED_TABLES, $this->existingTables($database, $driver)));

        return [
            'status' => $missingTables === [] ? 'ok' : 'degraded',
            'app' => [
                'environment' => Config::get('APP_ENV', 'production'),
                'timezone' => date_default_timezone_get(),
            ],
            'database' => [
                'driver' => $driver,
                'connected' => true,
                'missing_tables' => $missingTables,
            ],
        ];
    }

    /** @return array<int, string> */
    private function existingTables(PDO $database, string $driver): array
    {
        if ($driver === 'sqlite') {
            $statement = $database->query("SELECT name FROM sqlite_master WHERE type = 'table'");
            return array_map('strval', $statement ? $statement->fetchAll(PDO::FETCH_COLUMN) : []);
        }

        $statement = $database->query('SELECT table_name FROM information_schema.tables WHERE table_schema = DATABASE()');
        return array_map('strval', $statement ? $statement->fetchAll(PDO::FETCH_COLUMN) : []);
    }
}
