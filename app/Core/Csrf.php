<?php

declare(strict_types=1);

namespace Safe360\Core;

final class Csrf
{
    public static function token(): string
    {
        if (!isset($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }
        return (string) $_SESSION['_csrf'];
    }

    public static function validate(?string $token): bool
    {
        return is_string($token) && isset($_SESSION['_csrf']) && hash_equals((string) $_SESSION['_csrf'], $token);
    }

    public static function requireValid(): void
    {
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? Request::input('_csrf');
        if (!self::validate(is_string($token) ? $token : null)) {
            if (str_starts_with(Request::path(), '/api/')) {
                Response::json(['error' => 'invalid_csrf', 'message' => 'Your session token is invalid. Refresh the page and try again.'], 419);
            }
            http_response_code(419);
            View::render('errors/419', ['title' => 'Session expired']);
            exit;
        }
    }
}

