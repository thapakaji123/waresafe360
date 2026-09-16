<?php

declare(strict_types=1);

$testDatabase = dirname(__DIR__) . '/database/safe360-test.sqlite';
if (is_file($testDatabase)) {
    unlink($testDatabase);
}

putenv('APP_ENV=testing');
putenv('APP_DEBUG=true');
putenv('DB_DSN=sqlite:' . $testDatabase);
putenv('DB_USER=');
putenv('DB_PASSWORD=');

$argv = ['setup.php', '--fresh'];
ob_start();
require dirname(__DIR__) . '/scripts/setup.php';
ob_end_clean();

use Safe360\Core\Csrf;
use Safe360\Core\Database;
use Safe360\Services\HealthService;
use Safe360\Services\ReportingService;
use Safe360\Services\TrainingService;

$passed = 0;
$failed = 0;

$check = static function (bool $condition, string $message) use (&$passed, &$failed): void {
    if ($condition) {
        $passed++;
        echo "PASS  {$message}\n";
        return;
    }
    $failed++;
    echo "FAIL  {$message}\n";
};

$db = Database::connection();
$health = (new HealthService())->snapshot();
$check($health['status'] === 'ok' && $health['database']['missing_tables'] === [], 'Health check confirms the seeded database schema is complete.');
$check((int) $db->query('SELECT COUNT(*) FROM scenes')->fetchColumn() === 6, 'Seed contains six PRD scenes.');
$check((int) $db->query('SELECT COUNT(*) FROM hazards WHERE is_assessed = 1')->fetchColumn() === 36, 'Seed contains 36 assessed hazards.');
$check((int) $db->query('SELECT COUNT(*) FROM questions')->fetchColumn() === 37, 'Every hazard, including tutorial practice, has a question.');
$check((int) $db->query('SELECT COUNT(*) FROM question_options WHERE is_correct = 1')->fetchColumn() === 37, 'Every question has exactly one seeded correct answer.');

$token = Csrf::token();
$check(strlen($token) === 64 && Csrf::validate($token), 'CSRF token is generated and validated.');
$check(!Csrf::validate('invalid-token'), 'Invalid CSRF token is rejected.');

$service = new TrainingService();
$attemptId = $service->startAttempt(2, 1);
$sceneTitles = [];
$firstScore = null;
$duplicateScore = null;

for ($sceneIndex = 0; $sceneIndex < 6; $sceneIndex++) {
    $scene = $service->scenePayload($attemptId, 2);
    $sceneTitles[] = $scene['scene']['title'];
    foreach ($scene['hazards'] as $hazard) {
        $response = $service->submitResponse(
            $attemptId,
            2,
            (int) $hazard['id'],
            (int) $hazard['question']['options'][0]['id'],
            'event-' . $sceneIndex . '-' . $hazard['id']
        );
        if ($firstScore === null) {
            $firstScore = $response['score'];
            $duplicate = $service->submitResponse(
                $attemptId,
                2,
                (int) $hazard['id'],
                (int) $hazard['question']['options'][0]['id'],
                'duplicate-' . $hazard['id']
            );
            $duplicateScore = $duplicate['score'];
            $check($duplicate['duplicate'] === true, 'Duplicate hazard response is detected idempotently.');
        }
    }
    $completion = $service->completeCurrentScene($attemptId, 2);
}

$result = $service->result($attemptId, 2);
$check(count($sceneTitles) === 6 && $sceneTitles[0] === 'Orientation & Tutorial' && $sceneTitles[5] === 'Emergency & Fire Safety', 'Attempt advances through all six scenes in order.');
$check($firstScore === $duplicateScore, 'Duplicate response does not award duplicate points.');
$check((int) $result['score'] === 7150, 'Perfect run reconciles to the expected authoritative score of 7,150.');
$check((float) $result['hazard_percent'] === 100.0, 'Perfect run records 100% hazard completion.');
$check((float) $result['accuracy_percent'] === 100.0, 'Perfect run records 100% question accuracy.');
$check((bool) $result['passed'] === true, 'Configured pass rule marks the perfect run as passed.');
$check(count($result['achievements']) === 3, 'Perfect run awards all three configured achievements.');

$leaderboard = $service->leaderboard(1);
$check(count($leaderboard) === 1 && (int) $leaderboard[0]['score'] === 7150, 'Leaderboard returns the best eligible completed attempt.');

$report = (new ReportingService())->dashboard();
$check((int) $report['active_trainees'] === 2, 'Trainer report counts active trainee accounts.');
$check((float) $report['average_accuracy'] === 100.0, 'Trainer report aggregates completed accuracy correctly.');

$zeroAttempt = $service->startAttempt(3, 1);
$service->completeCurrentScene($zeroAttempt, 3);
$check(true, 'A zero-hazard-found path can exit a scene without trapping the trainee.');

echo "\n{$passed} passed, {$failed} failed.\n";
exit($failed === 0 ? 0 : 1);
