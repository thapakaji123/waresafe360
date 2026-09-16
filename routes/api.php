<?php

declare(strict_types=1);

use Safe360\Controllers\AdminController;
use Safe360\Controllers\HealthController;
use Safe360\Controllers\TrainingApiController;

$router->get('/api/health', [HealthController::class, 'show']);
$router->post('/api/attempts', [TrainingApiController::class, 'startAttempt']);
$router->get('/api/attempts/{attemptId}/scene', [TrainingApiController::class, 'scene']);
$router->post('/api/attempts/{attemptId}/responses', [TrainingApiController::class, 'respond']);
$router->post('/api/attempts/{attemptId}/scene/complete', [TrainingApiController::class, 'completeScene']);
$router->post('/api/admin/users/{userId}', [AdminController::class, 'updateUser']);
