<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$content = require $root . '/database/content.php';
$failures = [];
$scenes = $content;
$assessed = array_values(array_filter($scenes, static fn (array $scene): bool => empty($scene['tutorial'])));
$hazardCount = array_sum(array_map(static fn (array $scene): int => count($scene['hazards'] ?? []), $assessed));

if (count($scenes) !== 6) $failures[] = 'Expected six scenes.';
if ($hazardCount !== 36) $failures[] = 'Expected 36 assessed hazards.';

foreach ($scenes as $scene) {
    $path = $root . '/public' . $scene['panorama'];
    if (!is_file($path)) {
        $failures[] = 'Missing panorama: ' . $scene['panorama'];
        continue;
    }
    $size = getimagesize($path);
    if ($size === false || abs(($size[0] / $size[1]) - 2.0) > 0.02) {
        $failures[] = 'Panorama is not approximately 2:1: ' . $scene['panorama'];
    }
    foreach ($scene['hazards'] ?? [] as $hazard) {
        if (!isset($hazard['options']) || count($hazard['options']) !== 4) {
            $failures[] = 'Hazard must have four options: ' . ($hazard['code'] ?? 'unknown');
        }
        if (!isset($hazard['correct']) || $hazard['correct'] < 0 || $hazard['correct'] >= count($hazard['options'] ?? [])) {
            $failures[] = 'Hazard must identify one valid correct option: ' . ($hazard['code'] ?? 'unknown');
        }
    }
}

if ($failures !== []) {
    foreach ($failures as $failure) fwrite(STDERR, "FAIL  {$failure}\n");
    exit(1);
}

echo "PASS  Six local 2:1 panoramas are present.\n";
echo "PASS  Six scenes and 36 assessed hazards are configured.\n";
echo "PASS  Every hazard has four options and exactly one correct answer.\n";
