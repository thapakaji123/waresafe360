<?php

declare(strict_types=1);

use Safe360\Core\Config;
use Safe360\Core\Router;

define('SAFE360_ROOT', dirname(__DIR__));

spl_autoload_register(static function (string $class): void {
    $prefix = 'Safe360\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = str_replace('\\', DIRECTORY_SEPARATOR, substr($class, strlen($prefix)));
    $file = SAFE360_ROOT . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . $relative . '.php';
    if (is_file($file)) {
        require $file;
    }
});

Config::load(SAFE360_ROOT . '/.env');

date_default_timezone_set(Config::get('APP_TIMEZONE', 'Asia/Katmandu'));

$secureCookie = str_starts_with(Config::get('APP_URL', ''), 'https://');
session_name(Config::get('SESSION_NAME', 'safe360_session'));
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => $secureCookie,
    'httponly' => true,
    'samesite' => 'Lax',
]);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!headers_sent()) {
    header("Content-Security-Policy: default-src 'self'; img-src 'self' data: blob:; style-src 'self'; script-src 'self'; connect-src 'self'; font-src 'self'; object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'");
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
}

if (Config::bool('APP_DEBUG', false)) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(E_ALL);
}

set_exception_handler(static function (Throwable $exception): void {
    $logDirectory = SAFE360_ROOT . '/storage/logs';
    if (!is_dir($logDirectory)) {
        @mkdir($logDirectory, 0775, true);
    }
    error_log(sprintf("[%s] %s\n%s\n", date(DATE_ATOM), $exception->getMessage(), $exception->getTraceAsString()), 3, $logDirectory . '/app.log');

    if (str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/api/')) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'error' => 'server_error',
            'message' => Config::bool('APP_DEBUG', false) ? $exception->getMessage() : 'Something went wrong. Please try again.',
        ], JSON_UNESCAPED_SLASHES);
        return;
    }

    http_response_code(500);
    echo '<!doctype html><html lang="en"><meta charset="utf-8"><title>Safe360 error</title><body><h1>We could not complete that request.</h1><p>Please try again or contact the trainer.</p></body></html>';
});

return new Router();
