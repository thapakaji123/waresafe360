<?php

declare(strict_types=1);

namespace Safe360\Controllers;

use Safe360\Core\Auth;
use Safe360\Core\Csrf;
use Safe360\Core\Request;
use Safe360\Core\Response;
use Safe360\Services\TrainingService;

final class TrainingApiController
{
    public function startAttempt(): void
    {
        $user = Auth::requireRole('trainee');
        Csrf::requireValid();
        $moduleId = filter_var(Request::input('module_id'), FILTER_VALIDATE_INT);
        if (!$moduleId) {
            Response::json(['error' => 'validation', 'message' => 'Select a valid module.'], 422);
        }
        try {
            $attemptId = (new TrainingService())->startAttempt((int) $user['id'], (int) $moduleId);
            Response::json(['attempt_id' => $attemptId, 'redirect' => '/training/' . $attemptId], 201);
        } catch (\DomainException $exception) {
            Response::json(['error' => 'training_unavailable', 'message' => $exception->getMessage()], 422);
        }
    }

    public function scene(string $attemptId): void
    {
        $user = Auth::requireRole('trainee');
        try {
            Response::json((new TrainingService())->scenePayload((int) $attemptId, (int) $user['id']));
        } catch (\DomainException $exception) {
            Response::json(['error' => 'invalid_attempt', 'message' => $exception->getMessage()], 422);
        }
    }

    public function respond(string $attemptId): void
    {
        $user = Auth::requireRole('trainee');
        Csrf::requireValid();
        $hazardId = filter_var(Request::input('hazard_id'), FILTER_VALIDATE_INT);
        $optionId = filter_var(Request::input('option_id'), FILTER_VALIDATE_INT);
        $eventId = trim((string) Request::input('event_id', ''));
        if (!$hazardId || !$optionId || $eventId === '') {
            Response::json(['error' => 'validation', 'message' => 'Choose an answer before submitting.'], 422);
        }
        try {
            $result = (new TrainingService())->submitResponse((int) $attemptId, (int) $user['id'], (int) $hazardId, (int) $optionId, $eventId);
            Response::json($result);
        } catch (\InvalidArgumentException|\DomainException $exception) {
            Response::json(['error' => 'invalid_response', 'message' => $exception->getMessage()], 422);
        }
    }

    public function completeScene(string $attemptId): void
    {
        $user = Auth::requireRole('trainee');
        Csrf::requireValid();
        try {
            Response::json((new TrainingService())->completeCurrentScene((int) $attemptId, (int) $user['id']));
        } catch (\DomainException $exception) {
            Response::json(['error' => 'cannot_complete', 'message' => $exception->getMessage()], 422);
        }
    }
}

