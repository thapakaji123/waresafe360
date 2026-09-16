<?php

declare(strict_types=1);

use Safe360\Core\Router;

// PHP's development server needs the router to release real static assets.
$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$staticFile = __DIR__ . str_replace('/', DIRECTORY_SEPARATOR, $requestPath);
if ($requestPath !== '/' && is_file($staticFile)) {
    return false;
}

/** @var Router $router */
$router = require dirname(__DIR__) . '/app/bootstrap.php';

require dirname(__DIR__) . '/routes/web.php';
require dirname(__DIR__) . '/routes/api.php';

$router->dispatch();
