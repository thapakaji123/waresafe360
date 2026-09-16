<?php

declare(strict_types=1);

use Safe360\Core\Config;
use Safe360\Core\Database;

require dirname(__DIR__) . '/app/bootstrap.php';

if (!in_array('--fresh', $argv, true)) {
    fwrite(STDERR, "Refusing to replace existing tables without --fresh.\n");
    exit(1);
}

$pdo = Database::connection();
$driver = Database::driver();
$schemaFile = dirname(__DIR__) . '/database/schema.' . ($driver === 'mysql' ? 'mysql' : 'sqlite') . '.sql';

if (!is_file($schemaFile)) {
    throw new RuntimeException('Unsupported database driver: ' . $driver);
}

$pdo->exec((string) file_get_contents($schemaFile));

$pdo->beginTransaction();
try {
    $pdo->exec("INSERT INTO roles (name) VALUES ('trainee'), ('admin')");
    $roleIds = [];
    foreach ($pdo->query('SELECT id, name FROM roles') as $role) {
        $roleIds[$role['name']] = (int) $role['id'];
    }

    $insertUser = $pdo->prepare('INSERT INTO users (role_id, name, email, password_hash, status) VALUES (:role_id, :name, :email, :password_hash, :status)');
    $users = [
        ['admin', 'Jordan Blake', 'admin@safe360.test', 'Safe360!Admin'],
        ['trainee', 'Samira Khan', 'trainee@safe360.test', 'Safe360!Trainee'],
        ['trainee', 'Alex Morgan', 'alex@safe360.test', 'Safe360!Trainee'],
    ];
    foreach ($users as [$role, $name, $email, $password]) {
        $insertUser->execute([
            'role_id' => $roleIds[$role],
            'name' => $name,
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'status' => 'active',
        ]);
    }

    $scoring = json_encode([
        'hazard_discovery' => 100,
        'correct_answer' => 50,
        'incorrect_answer' => -25,
        'scene_completion' => 200,
        'perfect_scene' => 150,
        'time_bonus_max' => 150,
    ], JSON_THROW_ON_ERROR);

    $module = $pdo->prepare('INSERT INTO training_modules (slug, title, description, objectives, difficulty, min_hazard_percent, min_accuracy_percent, scoring_profile, content_version, status) VALUES (:slug, :title, :description, :objectives, :difficulty, :hazard, :accuracy, :scoring, 1, :status)');
    $module->execute([
        'slug' => 'warehouse-hazard-perception',
        'title' => 'Warehouse Hazard Perception',
        'description' => 'Explore realistic 360-degree warehouse scenes, identify unsafe conditions, and learn the safest response.',
        'objectives' => json_encode(['Recognise common warehouse hazards', 'Choose appropriate immediate responses', 'Improve systematic observation'], JSON_THROW_ON_ERROR),
        'difficulty' => 'Foundation',
        'hazard' => 75,
        'accuracy' => 70,
        'scoring' => $scoring,
        'status' => 'active',
    ]);
    $moduleId = (int) $pdo->lastInsertId();

    $insertScene = $pdo->prepare('INSERT INTO scenes (module_id, code, title, description, sequence_no, panorama_path, initial_yaw, initial_pitch, initial_fov, is_tutorial, time_limit_seconds, status) VALUES (:module_id, :code, :title, :description, :sequence_no, :panorama_path, 0, 0, 1.35, :is_tutorial, :time_limit, :status)');
    $insertHazard = $pdo->prepare('INSERT INTO hazards (scene_id, code, title, category, severity, yaw, pitch, hint, is_assessed, status) VALUES (:scene_id, :code, :title, :category, :severity, :yaw, :pitch, :hint, :is_assessed, :status)');
    $insertQuestion = $pdo->prepare('INSERT INTO questions (hazard_id, question_text, explanation, status) VALUES (:hazard_id, :question_text, :explanation, :status)');
    $insertOption = $pdo->prepare('INSERT INTO question_options (question_id, option_text, is_correct, sequence_no) VALUES (:question_id, :option_text, :is_correct, :sequence_no)');

    $content = require dirname(__DIR__) . '/database/content.php';
    foreach ($content as $scene) {
        $insertScene->execute([
            'module_id' => $moduleId,
            'code' => $scene['code'],
            'title' => $scene['title'],
            'description' => $scene['description'],
            'sequence_no' => $scene['sequence'],
            'panorama_path' => $scene['panorama'],
            'is_tutorial' => $scene['tutorial'] ? 1 : 0,
            'time_limit' => $scene['time_limit'],
            'status' => 'active',
        ]);
        $sceneId = (int) $pdo->lastInsertId();

        foreach ($scene['hazards'] as $item) {
            $insertHazard->execute([
                'scene_id' => $sceneId,
                'code' => $item['code'],
                'title' => $item['title'],
                'category' => $item['category'],
                'severity' => $item['severity'],
                'yaw' => $item['yaw'],
                'pitch' => $item['pitch'],
                'hint' => $item['hint'],
                'is_assessed' => $item['is_assessed'] ? 1 : 0,
                'status' => 'active',
            ]);
            $hazardId = (int) $pdo->lastInsertId();

            $insertQuestion->execute([
                'hazard_id' => $hazardId,
                'question_text' => $item['question'],
                'explanation' => $item['explanation'],
                'status' => 'active',
            ]);
            $questionId = (int) $pdo->lastInsertId();

            foreach ($item['options'] as $sequence => $option) {
                $insertOption->execute([
                    'question_id' => $questionId,
                    'option_text' => $option,
                    'is_correct' => $sequence === $item['correct'] ? 1 : 0,
                    'sequence_no' => $sequence + 1,
                ]);
            }
        }
    }

    $achievement = $pdo->prepare('INSERT INTO achievements (code, name, description, criteria_code) VALUES (:code, :name, :description, :criteria)');
    foreach ([
        ['sharp-eye', 'Sharp Eye', 'Completed a module with at least 90% question accuracy.', 'ACCURACY_90'],
        ['clean-sweep', 'Clean Sweep', 'Found every assessed hazard in a completed module.', 'ALL_HAZARDS'],
        ['safety-first', 'Safety First', 'Passed the warehouse hazard perception module.', 'PASSED_MODULE'],
    ] as [$code, $name, $description, $criteria]) {
        $achievement->execute(compact('code', 'name', 'description', 'criteria'));
    }

    $pdo->commit();
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    throw $exception;
}

echo "Safe360 database created and seeded using {$driver}.\n";
echo "Admin: admin@safe360.test / Safe360!Admin\n";
echo "Trainee: trainee@safe360.test / Safe360!Trainee\n";

