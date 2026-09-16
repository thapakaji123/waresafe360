<?php

declare(strict_types=1);

namespace Safe360\Services;

use PDO;
use Safe360\Core\Database;

final class ReportingService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    /** @return array<string, mixed> */
    public function dashboard(): array
    {
        return [
            'active_trainees' => (int) $this->db->query("SELECT COUNT(*) FROM users u JOIN roles r ON r.id = u.role_id WHERE r.name = 'trainee' AND u.status = 'active'")->fetchColumn(),
            'completion_rate' => (float) $this->db->query("SELECT COALESCE(ROUND(100.0 * SUM(CASE WHEN state = 'completed' THEN 1 ELSE 0 END) / NULLIF(COUNT(*), 0), 1), 0) FROM training_attempts WHERE state <> 'abandoned'")->fetchColumn(),
            'average_score' => (float) $this->db->query("SELECT COALESCE(ROUND(AVG(score), 0), 0) FROM training_attempts WHERE state = 'completed'")->fetchColumn(),
            'average_accuracy' => (float) $this->db->query("SELECT COALESCE(ROUND(AVG(accuracy_percent), 1), 0) FROM training_attempts WHERE state = 'completed'")->fetchColumn(),
            'recent_attempts' => $this->recentAttempts(),
            'difficult_hazards' => $this->difficultHazards(),
            'users' => $this->users(),
            'content_counts' => $this->contentCounts(),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public function recentAttempts(int $limit = 10): array
    {
        $statement = $this->db->prepare('SELECT a.id, u.name, m.title AS module_title, a.state, a.score, a.accuracy_percent, a.hazard_percent, a.passed, a.started_at, a.completed_at FROM training_attempts a JOIN users u ON u.id = a.user_id JOIN training_modules m ON m.id = a.module_id ORDER BY a.started_at DESC LIMIT :limit');
        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->execute();
        return $statement->fetchAll();
    }

    /** @return array<int, array<string, mixed>> */
    public function difficultHazards(int $limit = 8): array
    {
        $statement = $this->db->prepare('SELECT h.code, h.title, s.title AS scene_title, COUNT(hr.id) AS responses, SUM(CASE WHEN hr.is_correct = 0 THEN 1 ELSE 0 END) AS incorrect, ROUND(100.0 * SUM(CASE WHEN hr.is_correct = 0 THEN 1 ELSE 0 END) / NULLIF(COUNT(hr.id), 0), 1) AS error_rate FROM hazards h JOIN scenes s ON s.id = h.scene_id LEFT JOIN hazard_responses hr ON hr.hazard_id = h.id WHERE h.is_assessed = 1 GROUP BY h.id, h.code, h.title, s.title HAVING COUNT(hr.id) > 0 ORDER BY error_rate DESC, responses DESC LIMIT :limit');
        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->execute();
        return $statement->fetchAll();
    }

    /** @return array<int, array<string, mixed>> */
    public function users(): array
    {
        return $this->db->query('SELECT u.id, u.name, u.email, u.status, u.last_login_at, r.name AS role, COUNT(a.id) AS attempts FROM users u JOIN roles r ON r.id = u.role_id LEFT JOIN training_attempts a ON a.user_id = u.id GROUP BY u.id, u.name, u.email, u.status, u.last_login_at, r.name ORDER BY r.name, u.name')->fetchAll();
    }

    /** @return array<string, int> */
    private function contentCounts(): array
    {
        return [
            'modules' => (int) $this->db->query("SELECT COUNT(*) FROM training_modules WHERE status = 'active'")->fetchColumn(),
            'scenes' => (int) $this->db->query("SELECT COUNT(*) FROM scenes WHERE status = 'active'")->fetchColumn(),
            'hazards' => (int) $this->db->query("SELECT COUNT(*) FROM hazards WHERE status = 'active' AND is_assessed = 1")->fetchColumn(),
            'questions' => (int) $this->db->query("SELECT COUNT(*) FROM questions WHERE status = 'active'")->fetchColumn(),
        ];
    }
}

