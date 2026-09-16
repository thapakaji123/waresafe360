<?php

declare(strict_types=1);

namespace Safe360\Services;

use PDO;
use Safe360\Core\Database;

final class TrainingService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    /** @return array<int, array<string, mixed>> */
    public function modulesForUser(int $userId): array
    {
        $sql = <<<'SQL'
            SELECT m.*,
                   (SELECT COUNT(*) FROM scenes s WHERE s.module_id = m.id AND s.status = 'active' AND s.is_tutorial = 0) AS scene_count,
                   (SELECT COUNT(*) FROM hazards h JOIN scenes s2 ON s2.id = h.scene_id WHERE s2.module_id = m.id AND h.status = 'active' AND h.is_assessed = 1) AS hazard_count,
                   (SELECT COUNT(*) FROM training_attempts a WHERE a.module_id = m.id AND a.user_id = :user_id AND a.state = 'completed') AS completed_attempts,
                   (SELECT MAX(a2.score) FROM training_attempts a2 WHERE a2.module_id = m.id AND a2.user_id = :user_id2 AND a2.state = 'completed') AS best_score,
                   (SELECT MAX(a3.completed_at) FROM training_attempts a3 WHERE a3.module_id = m.id AND a3.user_id = :user_id3 AND a3.state = 'completed') AS last_completed_at
            FROM training_modules m
            WHERE m.status = 'active'
            ORDER BY m.id
        SQL;
        $statement = $this->db->prepare($sql);
        $statement->execute(['user_id' => $userId, 'user_id2' => $userId, 'user_id3' => $userId]);
        return $statement->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function module(int $moduleId): ?array
    {
        $statement = $this->db->prepare('SELECT * FROM training_modules WHERE id = :id AND status = :status');
        $statement->execute(['id' => $moduleId, 'status' => 'active']);
        $module = $statement->fetch();
        return $module ?: null;
    }

    public function startAttempt(int $userId, int $moduleId): int
    {
        $module = $this->module($moduleId);
        if (!$module) {
            throw new \DomainException('This training module is not available.');
        }

        $content = $this->contentSnapshot($moduleId);
        if ($content['scenes'] === []) {
            throw new \DomainException('This module has no active training scenes.');
        }

        $this->db->beginTransaction();
        try {
            $abandon = $this->db->prepare("UPDATE training_attempts SET state = 'abandoned', completed_at = CURRENT_TIMESTAMP WHERE user_id = :user_id AND module_id = :module_id AND state IN ('created','in_progress')");
            $abandon->execute(['user_id' => $userId, 'module_id' => $moduleId]);

            $firstScene = $content['scenes'][0];
            $insert = $this->db->prepare(
                "INSERT INTO training_attempts (user_id, module_id, content_version, content_snapshot, state, current_scene_id) VALUES (:user_id, :module_id, :version, :snapshot, 'in_progress', :scene_id)"
            );
            $insert->execute([
                'user_id' => $userId,
                'module_id' => $moduleId,
                'version' => (int) $module['content_version'],
                'snapshot' => json_encode($content, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES),
                'scene_id' => (int) $firstScene['id'],
            ]);
            $attemptId = (int) $this->db->lastInsertId();

            $progress = $this->db->prepare("INSERT INTO attempt_scene_progress (attempt_id, scene_id, state, started_at) VALUES (:attempt_id, :scene_id, 'in_progress', CURRENT_TIMESTAMP)");
            foreach ($content['scenes'] as $index => $scene) {
                if ($index === 0) {
                    $progress->execute(['attempt_id' => $attemptId, 'scene_id' => (int) $scene['id']]);
                } else {
                    $waiting = $this->db->prepare("INSERT INTO attempt_scene_progress (attempt_id, scene_id, state) VALUES (:attempt_id, :scene_id, 'not_started')");
                    $waiting->execute(['attempt_id' => $attemptId, 'scene_id' => (int) $scene['id']]);
                }
            }

            $this->db->commit();
            return $attemptId;
        } catch (\Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    /** @return array<string, mixed> */
    public function scenePayload(int $attemptId, int $userId): array
    {
        $attempt = $this->ownedAttempt($attemptId, $userId);
        if ($attempt['state'] !== 'in_progress') {
            throw new \DomainException('This attempt is no longer active.');
        }

        $sceneId = (int) $attempt['current_scene_id'];
        $sceneStatement = $this->db->prepare('SELECT * FROM scenes WHERE id = :id AND status = :status');
        $sceneStatement->execute(['id' => $sceneId, 'status' => 'active']);
        $scene = $sceneStatement->fetch();
        if (!$scene) {
            throw new \DomainException('The current scene is unavailable.');
        }

        $hazards = $this->hazardsForScene($sceneId, false);
        $responseStatement = $this->db->prepare('SELECT hazard_id, is_correct, total_points FROM hazard_responses WHERE attempt_id = :attempt_id AND scene_id = :scene_id');
        $responseStatement->execute(['attempt_id' => $attemptId, 'scene_id' => $sceneId]);
        $responses = [];
        foreach ($responseStatement->fetchAll() as $response) {
            $responses[(int) $response['hazard_id']] = $response;
        }

        foreach ($hazards as &$hazard) {
            $hazard['completed'] = isset($responses[(int) $hazard['id']]);
            if ($hazard['completed']) {
                $hazard['question'] = null;
            }
        }
        unset($hazard);

        $assessedTotal = count(array_filter($hazards, static fn (array $item): bool => (bool) $item['is_assessed']));
        $assessedFound = count(array_filter($hazards, static fn (array $item): bool => (bool) $item['is_assessed'] && (bool) $item['completed']));

        return [
            'attempt' => [
                'id' => $attemptId,
                'score' => (int) $attempt['score'],
                'state' => $attempt['state'],
            ],
            'scene' => [
                'id' => (int) $scene['id'],
                'code' => $scene['code'],
                'title' => $scene['title'],
                'description' => $scene['description'],
                'panorama_path' => $scene['panorama_path'],
                'initial_yaw' => (float) $scene['initial_yaw'],
                'initial_pitch' => (float) $scene['initial_pitch'],
                'initial_fov' => (float) $scene['initial_fov'],
                'is_tutorial' => (bool) $scene['is_tutorial'],
                'time_limit_seconds' => $scene['time_limit_seconds'] !== null ? (int) $scene['time_limit_seconds'] : null,
                'sequence_no' => (int) $scene['sequence_no'],
            ],
            'hazards' => $hazards,
            'progress' => [
                'found' => $assessedFound,
                'total' => $assessedTotal,
                'all_interactions' => count($responses),
                'required_interactions' => count($hazards),
            ],
            'csrf_token' => \Safe360\Core\Csrf::token(),
        ];
    }

    /** @return array<string, mixed> */
    public function submitResponse(int $attemptId, int $userId, int $hazardId, int $optionId, string $eventId): array
    {
        if ($eventId === '' || strlen($eventId) > 80) {
            throw new \InvalidArgumentException('A valid response event identifier is required.');
        }

        $this->db->beginTransaction();
        try {
            $attempt = $this->ownedAttempt($attemptId, $userId, true);
            if ($attempt['state'] !== 'in_progress') {
                throw new \DomainException('This attempt is no longer active.');
            }

            $duplicate = $this->db->prepare('SELECT hr.*, q.explanation FROM hazard_responses hr JOIN questions q ON q.id = hr.question_id WHERE hr.attempt_id = :attempt_id AND hr.hazard_id = :hazard_id');
            $duplicate->execute(['attempt_id' => $attemptId, 'hazard_id' => $hazardId]);
            if ($existing = $duplicate->fetch()) {
                $this->db->commit();
                return $this->responseResult($existing, (int) $attempt['score'], true);
            }

            $sql = <<<'SQL'
                SELECT h.id AS hazard_id, h.scene_id, h.is_assessed, q.id AS question_id, q.explanation,
                       o.id AS option_id, o.is_correct
                FROM hazards h
                JOIN questions q ON q.hazard_id = h.id AND q.status = 'active'
                JOIN question_options o ON o.question_id = q.id
                WHERE h.id = :hazard_id AND h.scene_id = :scene_id AND h.status = 'active' AND o.id = :option_id
                LIMIT 1
            SQL;
            $statement = $this->db->prepare($sql);
            $statement->execute([
                'hazard_id' => $hazardId,
                'scene_id' => (int) $attempt['current_scene_id'],
                'option_id' => $optionId,
            ]);
            $answer = $statement->fetch();
            if (!$answer) {
                throw new \DomainException('That hazard or answer is not valid for the active scene.');
            }

            $profile = json_decode((string) $attempt['scoring_profile'], true, 512, JSON_THROW_ON_ERROR);
            $assessed = (bool) $answer['is_assessed'];
            $correct = (bool) $answer['is_correct'];
            $discoveryPoints = $assessed ? (int) ($profile['hazard_discovery'] ?? 100) : 0;
            $answerPoints = $assessed ? ($correct ? (int) ($profile['correct_answer'] ?? 50) : (int) ($profile['incorrect_answer'] ?? -25)) : 0;
            $totalPoints = $discoveryPoints + $answerPoints;

            $insert = $this->db->prepare('INSERT INTO hazard_responses (attempt_id, scene_id, hazard_id, question_id, option_id, is_correct, discovery_points, answer_points, total_points, client_event_id) VALUES (:attempt_id, :scene_id, :hazard_id, :question_id, :option_id, :is_correct, :discovery, :answer, :total, :event_id)');
            $insert->execute([
                'attempt_id' => $attemptId,
                'scene_id' => (int) $answer['scene_id'],
                'hazard_id' => $hazardId,
                'question_id' => (int) $answer['question_id'],
                'option_id' => $optionId,
                'is_correct' => $correct ? 1 : 0,
                'discovery' => $discoveryPoints,
                'answer' => $answerPoints,
                'total' => $totalPoints,
                'event_id' => $eventId,
            ]);

            $newScore = max(0, (int) $attempt['score'] + $totalPoints);
            $updateAttempt = $this->db->prepare('UPDATE training_attempts SET score = :score WHERE id = :id');
            $updateAttempt->execute(['score' => $newScore, 'id' => $attemptId]);

            $updateProgress = $this->db->prepare('UPDATE attempt_scene_progress SET hazards_found = hazards_found + :found, correct_answers = correct_answers + :correct, score = score + :points WHERE attempt_id = :attempt_id AND scene_id = :scene_id');
            $updateProgress->execute([
                'found' => $assessed ? 1 : 0,
                'correct' => $assessed && $correct ? 1 : 0,
                'points' => $totalPoints,
                'attempt_id' => $attemptId,
                'scene_id' => (int) $answer['scene_id'],
            ]);

            $this->db->commit();
            return [
                'duplicate' => false,
                'correct' => $correct,
                'label' => $correct ? 'Correct' : 'Incorrect',
                'explanation' => $answer['explanation'],
                'points_delta' => $totalPoints,
                'score' => $newScore,
            ];
        } catch (\Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }
    }

    /** @return array<string, mixed> */
    public function completeCurrentScene(int $attemptId, int $userId): array
    {
        $this->db->beginTransaction();
        try {
            $attempt = $this->ownedAttempt($attemptId, $userId, true);
            if ($attempt['state'] !== 'in_progress') {
                throw new \DomainException('This attempt is no longer active.');
            }

            $sceneId = (int) $attempt['current_scene_id'];
            $counts = $this->sceneCounts($attemptId, $sceneId);
            $profile = json_decode((string) $attempt['scoring_profile'], true, 512, JSON_THROW_ON_ERROR);
            $bonus = 0;
            if ($counts['assessed_total'] > 0 && $counts['assessed_found'] === $counts['assessed_total']) {
                $bonus += (int) ($profile['scene_completion'] ?? 200);
                if ($counts['correct'] === $counts['assessed_total']) {
                    $bonus += (int) ($profile['perfect_scene'] ?? 150);
                }
            }

            $updateProgress = $this->db->prepare("UPDATE attempt_scene_progress SET state = 'completed', completed_at = CURRENT_TIMESTAMP, score = score + :bonus WHERE attempt_id = :attempt_id AND scene_id = :scene_id");
            $updateProgress->execute(['bonus' => $bonus, 'attempt_id' => $attemptId, 'scene_id' => $sceneId]);

            $newScore = max(0, (int) $attempt['score'] + $bonus);
            $next = $this->db->prepare('SELECT s.* FROM scenes s WHERE s.module_id = :module_id AND s.status = :status AND s.sequence_no > (SELECT sequence_no FROM scenes WHERE id = :scene_id) ORDER BY s.sequence_no LIMIT 1');
            $next->execute(['module_id' => (int) $attempt['module_id'], 'status' => 'active', 'scene_id' => $sceneId]);
            $nextScene = $next->fetch();

            if ($nextScene) {
                $updateAttempt = $this->db->prepare('UPDATE training_attempts SET current_scene_id = :next_scene, score = :score WHERE id = :id');
                $updateAttempt->execute(['next_scene' => (int) $nextScene['id'], 'score' => $newScore, 'id' => $attemptId]);
                $startProgress = $this->db->prepare("UPDATE attempt_scene_progress SET state = 'in_progress', started_at = COALESCE(started_at, CURRENT_TIMESTAMP) WHERE attempt_id = :attempt_id AND scene_id = :scene_id");
                $startProgress->execute(['attempt_id' => $attemptId, 'scene_id' => (int) $nextScene['id']]);
                $this->db->commit();
                return ['complete' => false, 'next_scene' => (int) $nextScene['id'], 'score' => $newScore, 'bonus' => $bonus];
            }

            $result = $this->completeAttempt($attempt, $newScore);
            $this->db->commit();
            return ['complete' => true, 'result' => $result, 'score' => $result['score'], 'bonus' => $bonus];
        } catch (\Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }
    }

    /** @return array<string, mixed> */
    public function result(int $attemptId, int $userId, bool $admin = false): array
    {
        $sql = <<<'SQL'
            SELECT a.*, m.title AS module_title, u.name AS trainee_name,
                   (SELECT COUNT(*) FROM hazard_responses hr WHERE hr.attempt_id = a.id AND hr.is_correct = 1) AS correct_answers,
                   (SELECT COUNT(*) FROM hazard_responses hr WHERE hr.attempt_id = a.id) AS answered_hazards
            FROM training_attempts a
            JOIN training_modules m ON m.id = a.module_id
            JOIN users u ON u.id = a.user_id
            WHERE a.id = :id
        SQL;
        $parameters = ['id' => $attemptId];
        if (!$admin) {
            $sql .= ' AND a.user_id = :user_id';
            $parameters['user_id'] = $userId;
        }
        $statement = $this->db->prepare($sql);
        $statement->execute($parameters);
        $result = $statement->fetch();
        if (!$result) {
            throw new \DomainException('Result not found.');
        }

        $snapshot = json_decode((string) $result['content_snapshot'], true, 512, JSON_THROW_ON_ERROR);
        $totalHazards = 0;
        foreach ($snapshot['scenes'] as $scene) {
            foreach ($scene['hazards'] as $hazard) {
                $totalHazards += $hazard['is_assessed'] ? 1 : 0;
            }
        }
        $result['total_hazards'] = $totalHazards;
        $result['achievements'] = $this->achievementsForAttempt($attemptId);
        return $result;
    }

    /** @return array<int, array<string, mixed>> */
    public function history(int $userId, int $limit = 20): array
    {
        $statement = $this->db->prepare('SELECT a.id, a.state, a.started_at, a.completed_at, a.score, a.hazard_percent, a.accuracy_percent, a.passed, m.title AS module_title FROM training_attempts a JOIN training_modules m ON m.id = a.module_id WHERE a.user_id = :user_id ORDER BY a.started_at DESC LIMIT :limit');
        $statement->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->execute();
        return $statement->fetchAll();
    }

    /** @return array<int, array<string, mixed>> */
    public function leaderboard(int $moduleId, int $limit = 10): array
    {
        $driver = Database::driver();
        $rowNumber = $driver === 'mysql'
            ? 'ROW_NUMBER() OVER (PARTITION BY a.user_id ORDER BY a.score DESC, a.elapsed_seconds ASC, a.completed_at ASC)'
            : 'ROW_NUMBER() OVER (PARTITION BY a.user_id ORDER BY a.score DESC, a.elapsed_seconds ASC, a.completed_at ASC)';
        $sql = "SELECT ranked.user_id, ranked.name, ranked.score, ranked.accuracy_percent, ranked.hazard_percent, ranked.elapsed_seconds, ranked.completed_at
                FROM (
                    SELECT a.user_id, u.name, a.score, a.accuracy_percent, a.hazard_percent, a.elapsed_seconds, a.completed_at,
                           {$rowNumber} AS user_rank
                    FROM training_attempts a
                    JOIN users u ON u.id = a.user_id
                    WHERE a.module_id = :module_id AND a.state = 'completed' AND a.leaderboard_eligible = 1
                ) ranked
                WHERE ranked.user_rank = 1
                ORDER BY ranked.score DESC, ranked.elapsed_seconds ASC, ranked.completed_at ASC
                LIMIT :limit";
        $statement = $this->db->prepare($sql);
        $statement->bindValue(':module_id', $moduleId, PDO::PARAM_INT);
        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->execute();
        return $statement->fetchAll();
    }

    /** @return array<string, mixed> */
    private function contentSnapshot(int $moduleId): array
    {
        $module = $this->module($moduleId);
        $sceneStatement = $this->db->prepare('SELECT * FROM scenes WHERE module_id = :module_id AND status = :status ORDER BY sequence_no');
        $sceneStatement->execute(['module_id' => $moduleId, 'status' => 'active']);
        $scenes = $sceneStatement->fetchAll();
        foreach ($scenes as &$scene) {
            $scene['hazards'] = $this->hazardsForScene((int) $scene['id'], true);
        }
        unset($scene);
        return ['module' => $module, 'scenes' => $scenes];
    }

    /** @return array<int, array<string, mixed>> */
    private function hazardsForScene(int $sceneId, bool $includeCorrect): array
    {
        $sql = <<<'SQL'
            SELECT h.*, q.id AS question_id, q.question_text, q.explanation,
                   o.id AS option_id, o.option_text, o.is_correct, o.sequence_no AS option_sequence
            FROM hazards h
            JOIN questions q ON q.hazard_id = h.id AND q.status = 'active'
            JOIN question_options o ON o.question_id = q.id
            WHERE h.scene_id = :scene_id AND h.status = 'active'
            ORDER BY h.id, o.sequence_no
        SQL;
        $statement = $this->db->prepare($sql);
        $statement->execute(['scene_id' => $sceneId]);
        $hazards = [];
        foreach ($statement->fetchAll() as $row) {
            $id = (int) $row['id'];
            if (!isset($hazards[$id])) {
                $hazards[$id] = [
                    'id' => $id,
                    'code' => $row['code'],
                    'title' => $row['title'],
                    'category' => $row['category'],
                    'severity' => $row['severity'],
                    'yaw' => (float) $row['yaw'],
                    'pitch' => (float) $row['pitch'],
                    'hint' => $row['hint'],
                    'is_assessed' => (bool) $row['is_assessed'],
                    'question' => [
                        'id' => (int) $row['question_id'],
                        'text' => $row['question_text'],
                        'explanation' => $row['explanation'],
                        'options' => [],
                    ],
                ];
            }
            $option = ['id' => (int) $row['option_id'], 'text' => $row['option_text']];
            if ($includeCorrect) {
                $option['is_correct'] = (bool) $row['is_correct'];
            }
            $hazards[$id]['question']['options'][] = $option;
        }
        return array_values($hazards);
    }

    /** @return array<string, mixed> */
    private function ownedAttempt(int $attemptId, int $userId, bool $forUpdate = false): array
    {
        $suffix = $forUpdate && Database::driver() === 'mysql' ? ' FOR UPDATE' : '';
        $statement = $this->db->prepare('SELECT a.*, m.scoring_profile, m.min_hazard_percent, m.min_accuracy_percent FROM training_attempts a JOIN training_modules m ON m.id = a.module_id WHERE a.id = :id AND a.user_id = :user_id' . $suffix);
        $statement->execute(['id' => $attemptId, 'user_id' => $userId]);
        $attempt = $statement->fetch();
        if (!$attempt) {
            throw new \DomainException('Training attempt not found.');
        }
        return $attempt;
    }

    /** @return array{required:int,interactions:int,assessed_total:int,assessed_found:int,correct:int} */
    private function sceneCounts(int $attemptId, int $sceneId): array
    {
        $hazardStatement = $this->db->prepare("SELECT COUNT(*) AS required, SUM(CASE WHEN is_assessed = 1 THEN 1 ELSE 0 END) AS assessed_total FROM hazards WHERE scene_id = :scene_id AND status = 'active'");
        $hazardStatement->execute(['scene_id' => $sceneId]);
        $hazards = $hazardStatement->fetch();
        $responseStatement = $this->db->prepare("SELECT COUNT(*) AS interactions, SUM(CASE WHEN h.is_assessed = 1 THEN 1 ELSE 0 END) AS assessed_found, SUM(CASE WHEN h.is_assessed = 1 AND hr.is_correct = 1 THEN 1 ELSE 0 END) AS correct FROM hazard_responses hr JOIN hazards h ON h.id = hr.hazard_id WHERE hr.attempt_id = :attempt_id AND hr.scene_id = :scene_id");
        $responseStatement->execute(['attempt_id' => $attemptId, 'scene_id' => $sceneId]);
        $responses = $responseStatement->fetch();
        return [
            'required' => (int) ($hazards['required'] ?? 0),
            'interactions' => (int) ($responses['interactions'] ?? 0),
            'assessed_total' => (int) ($hazards['assessed_total'] ?? 0),
            'assessed_found' => (int) ($responses['assessed_found'] ?? 0),
            'correct' => (int) ($responses['correct'] ?? 0),
        ];
    }

    /** @return array<string, mixed> */
    private function completeAttempt(array $attempt, int $score): array
    {
        $snapshot = json_decode((string) $attempt['content_snapshot'], true, 512, JSON_THROW_ON_ERROR);
        $totalHazards = 0;
        foreach ($snapshot['scenes'] as $scene) {
            foreach ($scene['hazards'] as $hazard) {
                $totalHazards += $hazard['is_assessed'] ? 1 : 0;
            }
        }
        $response = $this->db->prepare('SELECT COUNT(*) AS found, SUM(CASE WHEN is_correct = 1 THEN 1 ELSE 0 END) AS correct FROM hazard_responses WHERE attempt_id = :attempt_id AND hazard_id IN (SELECT id FROM hazards WHERE is_assessed = 1)');
        $response->execute(['attempt_id' => (int) $attempt['id']]);
        $counts = $response->fetch();
        $found = (int) ($counts['found'] ?? 0);
        $correct = (int) ($counts['correct'] ?? 0);
        $hazardPercent = $totalHazards > 0 ? round(($found / $totalHazards) * 100, 2) : 0.0;
        $accuracyPercent = $found > 0 ? round(($correct / $found) * 100, 2) : 0.0;
        $passed = $hazardPercent >= (float) $attempt['min_hazard_percent'] && $accuracyPercent >= (float) $attempt['min_accuracy_percent'];

        $started = new \DateTimeImmutable((string) $attempt['started_at']);
        $elapsed = max(0, time() - $started->getTimestamp());
        $update = $this->db->prepare("UPDATE training_attempts SET state = 'completed', completed_at = CURRENT_TIMESTAMP, elapsed_seconds = :elapsed, score = :score, hazard_percent = :hazard, accuracy_percent = :accuracy, passed = :passed WHERE id = :id");
        $update->execute([
            'elapsed' => $elapsed,
            'score' => $score,
            'hazard' => $hazardPercent,
            'accuracy' => $accuracyPercent,
            'passed' => $passed ? 1 : 0,
            'id' => (int) $attempt['id'],
        ]);

        $this->awardAchievements((int) $attempt['id'], (int) $attempt['user_id'], $passed, $hazardPercent, $accuracyPercent);
        return ['score' => $score, 'hazard_percent' => $hazardPercent, 'accuracy_percent' => $accuracyPercent, 'passed' => $passed];
    }

    private function awardAchievements(int $attemptId, int $userId, bool $passed, float $hazardPercent, float $accuracyPercent): void
    {
        $criteria = [];
        if ($passed) {
            $criteria[] = 'PASSED_MODULE';
        }
        if ($hazardPercent >= 100) {
            $criteria[] = 'ALL_HAZARDS';
        }
        if ($accuracyPercent >= 90) {
            $criteria[] = 'ACCURACY_90';
        }
        if ($criteria === []) {
            return;
        }

        $lookup = $this->db->prepare('SELECT id FROM achievements WHERE criteria_code = :criteria');
        $insertVerb = Database::driver() === 'mysql' ? 'INSERT IGNORE' : 'INSERT OR IGNORE';
        $insert = $this->db->prepare("{$insertVerb} INTO user_achievements (user_id, achievement_id, attempt_id) VALUES (:user_id, :achievement_id, :attempt_id)");
        foreach ($criteria as $criterion) {
            $lookup->execute(['criteria' => $criterion]);
            if ($id = $lookup->fetchColumn()) {
                $insert->execute(['user_id' => $userId, 'achievement_id' => (int) $id, 'attempt_id' => $attemptId]);
            }
        }
    }

    /** @return array<int, array<string, mixed>> */
    private function achievementsForAttempt(int $attemptId): array
    {
        $statement = $this->db->prepare('SELECT a.code, a.name, a.description FROM user_achievements ua JOIN achievements a ON a.id = ua.achievement_id WHERE ua.attempt_id = :attempt_id ORDER BY a.name');
        $statement->execute(['attempt_id' => $attemptId]);
        return $statement->fetchAll();
    }

    /** @return array<string, mixed> */
    private function responseResult(array $row, int $score, bool $duplicate): array
    {
        return [
            'duplicate' => $duplicate,
            'correct' => (bool) $row['is_correct'],
            'label' => (bool) $row['is_correct'] ? 'Correct' : 'Incorrect',
            'explanation' => $row['explanation'],
            'points_delta' => (int) $row['total_points'],
            'score' => $score,
        ];
    }
}
