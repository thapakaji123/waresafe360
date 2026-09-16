<?php

declare(strict_types=1);

namespace Safe360\Controllers;

use Safe360\Core\Response;
use Safe360\Services\HealthService;

final class HealthController
{
    public function show(): void
    {
        try {
            $snapshot = (new HealthService())->snapshot();
            Response::json($snapshot, $snapshot['status'] === 'ok' ? 200 : 503);
        } catch (\Throwable) {
            Response::json([
                'status' => 'degraded',
                'database' => [
                    'connected' => false,
                ],
            ], 503);
        }
    }
}
