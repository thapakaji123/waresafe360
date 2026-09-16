<?php

declare(strict_types=1);

namespace Safe360\Controllers;

use Safe360\Core\Auth;
use Safe360\Core\Csrf;
use Safe360\Core\Database;
use Safe360\Core\Request;
use Safe360\Core\Response;

final class AdminController
{
    public function updateModule(string $moduleId): void
    {
        Auth::requireRole('admin');
        Csrf::requireValid();
        $title = trim((string) Request::input('title', ''));
        $description = trim((string) Request::input('description', ''));
        $difficulty = trim((string) Request::input('difficulty', ''));
        $hazardThreshold = filter_var(Request::input('min_hazard_percent'), FILTER_VALIDATE_INT);
        $accuracyThreshold = filter_var(Request::input('min_accuracy_percent'), FILTER_VALIDATE_INT);
        $status = (string) Request::input('status', '');
        if ($title === '' || $description === '' || $difficulty === '' || $hazardThreshold === false || $accuracyThreshold === false || $hazardThreshold < 0 || $hazardThreshold > 100 || $accuracyThreshold < 0 || $accuracyThreshold > 100 || !in_array($status, ['draft', 'active', 'inactive'], true)) {
            $_SESSION['_flash_error'] = 'Module settings failed validation.';
            Response::redirect('/admin/content');
        }
        $statement = Database::connection()->prepare('UPDATE training_modules SET title = :title, description = :description, difficulty = :difficulty, min_hazard_percent = :hazard, min_accuracy_percent = :accuracy, status = :status, content_version = content_version + 1, updated_at = CURRENT_TIMESTAMP WHERE id = :id');
        $statement->execute(['title' => $title, 'description' => $description, 'difficulty' => $difficulty, 'hazard' => $hazardThreshold, 'accuracy' => $accuracyThreshold, 'status' => $status, 'id' => (int) $moduleId]);
        $_SESSION['_flash_notice'] = 'Module settings saved as a new content version.';
        Response::redirect('/admin/content');
    }

    public function updateScene(string $sceneId): void
    {
        Auth::requireRole('admin');
        Csrf::requireValid();
        $title = trim((string) Request::input('title', ''));
        $description = trim((string) Request::input('description', ''));
        $panoramaPath = trim((string) Request::input('panorama_path', ''));
        $initialYaw = filter_var(Request::input('initial_yaw'), FILTER_VALIDATE_FLOAT);
        $initialPitch = filter_var(Request::input('initial_pitch'), FILTER_VALIDATE_FLOAT);
        $initialFov = filter_var(Request::input('initial_fov'), FILTER_VALIDATE_FLOAT);
        $timeLimitRaw = trim((string) Request::input('time_limit_seconds', ''));
        $timeLimit = $timeLimitRaw === '' ? null : filter_var($timeLimitRaw, FILTER_VALIDATE_INT);
        $status = (string) Request::input('status', '');
        if ($title === '' || $description === '' || !preg_match('#^/assets/panoramas/[A-Za-z0-9._-]+$#', $panoramaPath) || $initialYaw === false || $initialPitch === false || $initialFov === false || $initialYaw < -3.141593 || $initialYaw > 3.141593 || $initialPitch < -1.570797 || $initialPitch > 1.570797 || $initialFov < 0.5 || $initialFov > 2.2 || ($timeLimit !== null && ($timeLimit === false || $timeLimit < 30 || $timeLimit > 7200)) || !in_array($status, ['draft', 'active', 'inactive'], true)) {
            $_SESSION['_flash_error'] = 'Scene settings failed validation. Panorama paths must reference /assets/panoramas/.';
            Response::redirect('/admin/content');
        }
        $db = Database::connection();
        $statement = $db->prepare('UPDATE scenes SET title = :title, description = :description, panorama_path = :path, initial_yaw = :yaw, initial_pitch = :pitch, initial_fov = :fov, time_limit_seconds = :time_limit, status = :status, updated_at = CURRENT_TIMESTAMP WHERE id = :id');
        $statement->execute(['title' => $title, 'description' => $description, 'path' => $panoramaPath, 'yaw' => $initialYaw, 'pitch' => $initialPitch, 'fov' => $initialFov, 'time_limit' => $timeLimit, 'status' => $status, 'id' => (int) $sceneId]);
        $db->exec('UPDATE training_modules SET content_version = content_version + 1, updated_at = CURRENT_TIMESTAMP WHERE id IN (SELECT module_id FROM scenes WHERE id = ' . (int) $sceneId . ')');
        $_SESSION['_flash_notice'] = 'Scene metadata saved as a new content version.';
        Response::redirect('/admin/content');
    }

    public function createHazard(): void
    {
        Auth::requireRole('admin');
        Csrf::requireValid();
        $sceneId = filter_var(Request::input('scene_id'), FILTER_VALIDATE_INT);
        $code = strtoupper(trim((string) Request::input('code', '')));
        $title = trim((string) Request::input('title', ''));
        $category = trim((string) Request::input('category', ''));
        $severity = (string) Request::input('severity', '');
        $yaw = filter_var(Request::input('yaw'), FILTER_VALIDATE_FLOAT);
        $pitch = filter_var(Request::input('pitch'), FILTER_VALIDATE_FLOAT);
        $question = trim((string) Request::input('question', ''));
        $explanation = trim((string) Request::input('explanation', ''));
        $options = Request::input('new_options', []);
        $correctIndex = filter_var(Request::input('correct_index'), FILTER_VALIDATE_INT);
        if (!$sceneId || !preg_match('/^[A-Z0-9-]{2,30}$/', $code) || $title === '' || $category === '' || !in_array($severity, ['low', 'medium', 'high', 'critical'], true) || $yaw === false || $pitch === false || $yaw < -3.141593 || $yaw > 3.141593 || $pitch < -1.570797 || $pitch > 1.570797 || $question === '' || $explanation === '' || !is_array($options) || count($options) !== 4 || $correctIndex === false || $correctIndex < 0 || $correctIndex > 3) {
            $_SESSION['_flash_error'] = 'New hazard failed validation. Provide a unique code, coordinates, question, and four answers.';
            Response::redirect('/admin/content');
        }
        $options = array_values(array_map(static fn ($value): string => trim((string) $value), $options));
        if (in_array('', $options, true)) {
            $_SESSION['_flash_error'] = 'Every new answer option must contain text.';
            Response::redirect('/admin/content');
        }

        $db = Database::connection();
        $db->beginTransaction();
        try {
            $insertHazard = $db->prepare("INSERT INTO hazards (scene_id, code, title, category, severity, yaw, pitch, hint, is_assessed, status) VALUES (:scene_id, :code, :title, :category, :severity, :yaw, :pitch, :hint, 1, 'draft')");
            $insertHazard->execute(['scene_id' => $sceneId, 'code' => $code, 'title' => $title, 'category' => $category, 'severity' => $severity, 'yaw' => $yaw, 'pitch' => $pitch, 'hint' => 'Scan the ' . strtolower($category) . ' area carefully.']);
            $hazardId = (int) $db->lastInsertId();
            $insertQuestion = $db->prepare("INSERT INTO questions (hazard_id, question_text, explanation, status) VALUES (:hazard_id, :question, :explanation, 'draft')");
            $insertQuestion->execute(['hazard_id' => $hazardId, 'question' => $question, 'explanation' => $explanation]);
            $questionId = (int) $db->lastInsertId();
            $insertOption = $db->prepare('INSERT INTO question_options (question_id, option_text, is_correct, sequence_no) VALUES (:question_id, :text, :correct, :sequence)');
            foreach ($options as $index => $option) {
                $insertOption->execute(['question_id' => $questionId, 'text' => $option, 'correct' => $index === (int) $correctIndex ? 1 : 0, 'sequence' => $index + 1]);
            }
            $db->exec('UPDATE training_modules SET content_version = content_version + 1, updated_at = CURRENT_TIMESTAMP WHERE id IN (SELECT module_id FROM scenes WHERE id = ' . (int) $sceneId . ')');
            $db->commit();
            $_SESSION['_flash_notice'] = 'Draft hazard created. Review it before activation.';
        } catch (\Throwable $exception) {
            $db->rollBack();
            $_SESSION['_flash_error'] = 'The hazard could not be created. Confirm the code is unique within the selected scene.';
        }
        Response::redirect('/admin/content');
    }

    public function updateUser(string $userId): void
    {
        $admin = Auth::requireRole('admin');
        Csrf::requireValid();
        $status = (string) Request::input('status', '');
        if (!in_array($status, ['active', 'inactive'], true) || (int) $userId === (int) $admin['id']) {
            Response::json(['error' => 'validation', 'message' => 'That account status change is not allowed.'], 422);
        }
        $statement = Database::connection()->prepare('UPDATE users SET status = :status, updated_at = CURRENT_TIMESTAMP WHERE id = :id');
        $statement->execute(['status' => $status, 'id' => (int) $userId]);
        Response::json(['updated' => true, 'status' => $status]);
    }

    public function updateHazard(string $hazardId): void
    {
        Auth::requireRole('admin');
        Csrf::requireValid();
        $title = trim((string) Request::input('title', ''));
        $category = trim((string) Request::input('category', ''));
        $severity = (string) Request::input('severity', '');
        $yaw = filter_var(Request::input('yaw'), FILTER_VALIDATE_FLOAT);
        $pitch = filter_var(Request::input('pitch'), FILTER_VALIDATE_FLOAT);
        $question = trim((string) Request::input('question', ''));
        $explanation = trim((string) Request::input('explanation', ''));
        $options = Request::input('options', []);
        $correctOption = filter_var(Request::input('correct_option'), FILTER_VALIDATE_INT);

        if ($title === '' || $category === '' || !in_array($severity, ['low', 'medium', 'high', 'critical'], true) || $yaw === false || $pitch === false || $yaw < -3.141593 || $yaw > 3.141593 || $pitch < -1.570797 || $pitch > 1.570797 || $question === '' || $explanation === '' || !is_array($options) || count($options) < 2 || !$correctOption) {
            $_SESSION['_flash_error'] = 'The content update failed validation. Check required text and coordinate ranges.';
            Response::redirect('/admin/content');
        }

        $normalisedOptions = [];
        foreach ($options as $id => $text) {
            if (!ctype_digit((string) $id) || trim((string) $text) === '') {
                $_SESSION['_flash_error'] = 'Every answer option must contain text.';
                Response::redirect('/admin/content');
            }
            $normalisedOptions[(int) $id] = trim((string) $text);
        }
        if (!array_key_exists((int) $correctOption, $normalisedOptions)) {
            $_SESSION['_flash_error'] = 'Select exactly one valid correct answer.';
            Response::redirect('/admin/content');
        }

        $db = Database::connection();
        $db->beginTransaction();
        try {
            $updateHazard = $db->prepare('UPDATE hazards SET title = :title, category = :category, severity = :severity, yaw = :yaw, pitch = :pitch, updated_at = CURRENT_TIMESTAMP WHERE id = :id');
            $updateHazard->execute(compact('title', 'category', 'severity', 'yaw', 'pitch') + ['id' => (int) $hazardId]);
            $updateQuestion = $db->prepare('UPDATE questions SET question_text = :question, explanation = :explanation, updated_at = CURRENT_TIMESTAMP WHERE hazard_id = :hazard_id');
            $updateQuestion->execute(['question' => $question, 'explanation' => $explanation, 'hazard_id' => (int) $hazardId]);
            $questionIdStatement = $db->prepare('SELECT id FROM questions WHERE hazard_id = :hazard_id');
            $questionIdStatement->execute(['hazard_id' => (int) $hazardId]);
            $questionId = (int) $questionIdStatement->fetchColumn();
            $ownedOptions = $db->prepare('SELECT id FROM question_options WHERE question_id = :question_id');
            $ownedOptions->execute(['question_id' => $questionId]);
            $ownedIds = array_map('intval', $ownedOptions->fetchAll(\PDO::FETCH_COLUMN));
            if (array_diff(array_keys($normalisedOptions), $ownedIds) !== []) {
                throw new \DomainException('One or more answer options do not belong to this question.');
            }
            $updateOption = $db->prepare('UPDATE question_options SET option_text = :text, is_correct = :is_correct WHERE id = :id AND question_id = :question_id');
            foreach ($normalisedOptions as $id => $text) {
                $updateOption->execute(['text' => $text, 'is_correct' => $id === (int) $correctOption ? 1 : 0, 'id' => $id, 'question_id' => $questionId]);
            }
            $db->exec('UPDATE training_modules SET content_version = content_version + 1, updated_at = CURRENT_TIMESTAMP WHERE id IN (SELECT s.module_id FROM scenes s JOIN hazards h ON h.scene_id = s.id WHERE h.id = ' . (int) $hazardId . ')');
            $db->commit();
            $_SESSION['_flash_notice'] = 'Hazard content updated. New attempts will use the new content version; historical results are unchanged.';
        } catch (\Throwable $exception) {
            $db->rollBack();
            throw $exception;
        }
        Response::redirect('/admin/content');
    }
}
