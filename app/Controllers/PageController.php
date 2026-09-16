<?php

declare(strict_types=1);

namespace Safe360\Controllers;

use Safe360\Core\Auth;
use Safe360\Core\Csrf;
use Safe360\Core\Database;
use Safe360\Core\Response;
use Safe360\Core\View;
use Safe360\Services\ReportingService;
use Safe360\Services\TrainingService;

final class PageController
{
    public function home(): void
    {
        $user = Auth::user();
        if (!$user) {
            Response::redirect('/login');
        }
        Response::redirect($user['role'] === 'admin' ? '/admin' : '/dashboard');
    }

    public function login(): void
    {
        if (Auth::user()) {
            Response::redirect('/');
        }
        View::render('auth/login', [
            'title' => 'Sign in',
            'csrf' => Csrf::token(),
            'error' => $_SESSION['_flash_error'] ?? null,
        ], 'auth');
        unset($_SESSION['_flash_error']);
    }

    public function dashboard(): void
    {
        $user = Auth::requireRole('trainee');
        $service = new TrainingService();
        View::render('trainee/dashboard', [
            'title' => 'Training dashboard',
            'user' => $user,
            'modules' => $service->modulesForUser((int) $user['id']),
            'history' => $service->history((int) $user['id'], 5),
            'csrf' => Csrf::token(),
        ]);
    }

    public function training(string $attemptId): void
    {
        $user = Auth::requireRole('trainee');
        View::render('trainee/training', [
            'title' => '360° training',
            'user' => $user,
            'attemptId' => (int) $attemptId,
            'csrf' => Csrf::token(),
            'trainingMode' => true,
        ]);
    }

    public function history(): void
    {
        $user = Auth::requireRole('trainee');
        View::render('trainee/history', [
            'title' => 'Attempt history',
            'user' => $user,
            'history' => (new TrainingService())->history((int) $user['id']),
        ]);
    }

    public function result(string $attemptId): void
    {
        $user = Auth::requireUser();
        $admin = $user['role'] === 'admin';
        try {
            $result = (new TrainingService())->result((int) $attemptId, (int) $user['id'], $admin);
        } catch (\DomainException) {
            Response::notFound();
        }
        View::render('trainee/result', [
            'title' => 'Training result',
            'user' => $user,
            'result' => $result,
        ]);
    }

    public function leaderboard(): void
    {
        $user = Auth::requireUser();
        $moduleId = (int) (Database::connection()->query("SELECT id FROM training_modules WHERE status = 'active' ORDER BY id LIMIT 1")->fetchColumn() ?: 0);
        View::render('trainee/leaderboard', [
            'title' => 'Leaderboard',
            'user' => $user,
            'leaders' => $moduleId ? (new TrainingService())->leaderboard($moduleId) : [],
        ]);
    }

    public function admin(): void
    {
        $user = Auth::requireRole('admin');
        View::render('admin/dashboard', [
            'title' => 'Trainer dashboard',
            'user' => $user,
            'report' => (new ReportingService())->dashboard(),
            'csrf' => Csrf::token(),
        ]);
    }

    public function adminContent(): void
    {
        $user = Auth::requireRole('admin');
        $db = Database::connection();
        $rows = $db->query("SELECT h.id AS hazard_id, h.code, h.title AS hazard_title, h.category, h.severity, h.yaw, h.pitch, h.status AS hazard_status, s.title AS scene_title, q.id AS question_id, q.question_text, q.explanation FROM hazards h JOIN scenes s ON s.id = h.scene_id JOIN questions q ON q.hazard_id = h.id ORDER BY s.sequence_no, h.id")->fetchAll();
        $optionStatement = $db->prepare('SELECT id, option_text, is_correct, sequence_no FROM question_options WHERE question_id = :question_id ORDER BY sequence_no');
        foreach ($rows as &$row) {
            $optionStatement->execute(['question_id' => (int) $row['question_id']]);
            $row['options'] = $optionStatement->fetchAll();
        }
        unset($row);
        $modules = $db->query('SELECT * FROM training_modules ORDER BY id')->fetchAll();
        $scenes = $db->query('SELECT * FROM scenes ORDER BY module_id, sequence_no')->fetchAll();
        View::render('admin/content', [
            'title' => 'Training content',
            'user' => $user,
            'rows' => $rows,
            'modules' => $modules,
            'scenes' => $scenes,
            'csrf' => Csrf::token(),
            'notice' => $_SESSION['_flash_notice'] ?? null,
            'error' => $_SESSION['_flash_error'] ?? null,
        ]);
        unset($_SESSION['_flash_notice'], $_SESSION['_flash_error']);
    }
}
