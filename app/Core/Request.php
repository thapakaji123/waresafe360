<?php

declare(strict_types=1);

namespace Safe360\Core;

final class Request
{
    /** @var array<string, mixed>|null */
    private static ?array $payload = null;

    public static function method(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    public static function path(): string
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        return '/' . trim($path, '/');
    }

    /** @return array<string, mixed> */
    public static function data(): array
    {
        if (self::$payload !== null) {
            return self::$payload;
        }

        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (str_contains($contentType, 'application/json')) {
            $decoded = json_decode(file_get_contents('php://input') ?: '{}', true);
            self::$payload = is_array($decoded) ? $decoded : [];
            return self::$payload;
        }

        self::$payload = $_POST;
        return self::$payload;
    }

    public static function input(string $key, mixed $default = null): mixed
    {
        return self::data()[$key] ?? $default;
    }

    public static function query(string $key, mixed $default = null): mixed
    {
        return $_GET[$key] ?? $default;
    }
}

