<?php

use Safe360\Core\View;

$completed = array_sum(array_map(static fn (array $module): int => (int) $module['completed_attempts'] > 0 ? 1 : 0, $modules));
$completion = count($modules) > 0 ? (int) round(($completed / count($modules)) * 100) : 0;
?>
<div class="app-container">
    <section class="dashboard-hero">
        <div>
            <p class="eyebrow">Your training hub</p>
            <h1>Good to see you, <?= View::e(explode(' ', $user['name'])[0]) ?>.</h1>
            <p>Build sharper hazard awareness through short, realistic warehouse scenarios.</p>
        </div>
        <div class="hero-progress" aria-label="Overall module completion <?= $completion ?> percent">
            <div class="progress-ring" style="--progress: <?= $completion ?>"><span><?= $completion ?>%</span></div>
            <div><strong>Overall progress</strong><span><?= $completed ?> of <?= count($modules) ?> modules completed</span></div>
        </div>
    </section>

    <section aria-labelledby="available-title" class="section-block">
        <div class="section-heading">
            <div>
                <p class="eyebrow text-teal">Continue learning</p>
                <h2 id="available-title">Available training</h2>
            </div>
            <a href="/history" class="text-link">View attempt history <span aria-hidden="true">→</span></a>
        </div>

        <div class="module-grid">
            <?php foreach ($modules as $module): ?>
                <article class="module-card">
                    <div class="module-visual">
                        <span class="status-pill <?= (int) $module['completed_attempts'] > 0 ? 'status-complete' : 'status-ready' ?>">
                            <?= (int) $module['completed_attempts'] > 0 ? 'Completed · retry available' : 'Ready to begin' ?>
                        </span>
                        <div class="module-orbit" aria-hidden="true"><span></span><i></i><b></b></div>
                    </div>
                    <div class="module-content">
                        <div class="module-meta">
                            <span><?= View::e($module['difficulty']) ?></span>
                            <span><?= (int) $module['scene_count'] ?> scenes</span>
                            <span><?= (int) $module['hazard_count'] ?> hazards</span>
                        </div>
                        <h3><?= View::e($module['title']) ?></h3>
                        <p><?= View::e($module['description']) ?></p>
                        <div class="module-stats">
                            <div><span>Best score</span><strong><?= $module['best_score'] !== null ? number_format((int) $module['best_score']) : '—' ?></strong></div>
                            <div><span>Pass rule</span><strong><?= (int) $module['min_hazard_percent'] ?>% / <?= (int) $module['min_accuracy_percent'] ?>%</strong></div>
                        </div>
                        <button class="btn btn-primary start-module" type="button" data-module-id="<?= (int) $module['id'] ?>">
                            <?= (int) $module['completed_attempts'] > 0 ? 'Start new attempt' : 'Start training' ?>
                        </button>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="section-block recent-section" aria-labelledby="recent-title">
        <div class="section-heading"><div><p class="eyebrow text-teal">Performance</p><h2 id="recent-title">Recent activity</h2></div></div>
        <?php if ($history === []): ?>
            <div class="empty-state"><span aria-hidden="true">◎</span><h3>Your results will appear here</h3><p>Complete the first scenario to establish your baseline.</p></div>
        <?php else: ?>
            <div class="table-responsive card-table">
                <table class="table align-middle mb-0">
                    <thead><tr><th>Module</th><th>Status</th><th>Score</th><th>Accuracy</th><th>Date</th><th><span class="visually-hidden">Action</span></th></tr></thead>
                    <tbody>
                    <?php foreach ($history as $attempt): ?>
                        <tr>
                            <td><strong><?= View::e($attempt['module_title']) ?></strong></td>
                            <td><span class="result-badge result-<?= View::e($attempt['state']) ?>"><?= View::e(ucwords(str_replace('_', ' ', $attempt['state']))) ?></span></td>
                            <td><?= number_format((int) $attempt['score']) ?></td>
                            <td><?= number_format((float) $attempt['accuracy_percent'], 0) ?>%</td>
                            <td><?= View::e(date('d M Y', strtotime((string) $attempt['started_at']))) ?></td>
                            <td><?php if ($attempt['state'] === 'completed'): ?><a href="/results/<?= (int) $attempt['id'] ?>" class="text-link">Review</a><?php endif; ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</div>

