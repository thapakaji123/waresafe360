<?php use Safe360\Core\View; ?>
<div class="app-container narrow-page">
    <header class="page-heading"><p class="eyebrow text-teal">High-score challenge</p><h1>Leaderboard</h1><p>Best eligible completed attempt per trainee. Accuracy and completion remain more important than speed.</p></header>
    <div class="leaderboard-card">
        <?php if ($leaders === []): ?>
            <div class="empty-state"><span aria-hidden="true">◇</span><h2>No qualifying scores yet</h2><p>Complete the module to establish the first benchmark.</p></div>
        <?php else: ?>
            <ol class="leaderboard-list">
                <?php foreach ($leaders as $index => $leader): ?>
                    <li>
                        <span class="leader-rank"><?= $index + 1 ?></span>
                        <div class="leader-name"><strong><?= View::e($leader['name']) ?></strong><span><?= number_format((float) $leader['accuracy_percent'], 0) ?>% accuracy · <?= number_format((float) $leader['hazard_percent'], 0) ?>% hazards</span></div>
                        <strong class="leader-score"><?= number_format((int) $leader['score']) ?></strong>
                    </li>
                <?php endforeach; ?>
            </ol>
        <?php endif; ?>
    </div>
</div>

