<?php

declare(strict_types=1);

use Safe360\Controllers\AdminController;
use Safe360\Controllers\AuthController;
use Safe360\Controllers\PageController;

$router->get('/', [PageController::class, 'home']);
$router->get('/login', [PageController::class, 'login']);
$router->post('/login', [AuthController::class, 'login']);
$router->post('/logout', [AuthController::class, 'logout']);
$router->get('/dashboard', [PageController::class, 'dashboard']);
$router->get('/training/{attemptId}', [PageController::class, 'training']);
$router->get('/history', [PageController::class, 'history']);
$router->get('/results/{attemptId}', [PageController::class, 'result']);
$router->get('/leaderboard', [PageController::class, 'leaderboard']);
$router->get('/admin', [PageController::class, 'admin']);
$router->get('/admin/content', [PageController::class, 'adminContent']);
$router->post('/admin/modules/{moduleId}', [AdminController::class, 'updateModule']);
$router->post('/admin/scenes/{sceneId}', [AdminController::class, 'updateScene']);
$router->post('/admin/hazards', [AdminController::class, 'createHazard']);
$router->post('/admin/hazards/{hazardId}', [AdminController::class, 'updateHazard']);
