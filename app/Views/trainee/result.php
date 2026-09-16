<?php use Safe360\Core\View; ?>
<div class="app-container result-page">
    <header class="result-hero <?= $result['passed'] ? 'result-hero-pass' : 'result-hero-review' ?>">
        <div>
            <p class="eyebrow"><?= $result['passed'] ? 'Module complete' : 'Learning review recommended' ?></p>
            <h1><?= $result['passed'] ? 'You passed.' : 'Keep sharpening your awareness.' ?></h1>
            <p><?= View::e($result['module_title']) ?> · <?= View::e($result['trainee_name']) ?></p>
        </div>
        <div class="score-medallion"><span>Final score</span><strong><?= number_format((int) $result['score']) ?></strong></div>
    </header>

    <section class="result-metrics" aria-label="Result summary">
        <article><span>Hazards identified</span><strong><?= number_format((float) $result['hazard_percent'], 0) ?>%</strong><small><?= (int) $result['answered_hazards'] ?> of <?= (int) $result['total_hazards'] ?> interactions recorded</small></article>
        <article><span>Question accuracy</span><strong><?= number_format((float) $result['accuracy_percent'], 0) ?>%</strong><small><?= (int) $result['correct_answers'] ?> correct answers</small></article>
        <article><span>Elapsed time</span><strong><?= sprintf('%02d:%02d', intdiv((int) $result['elapsed_seconds'], 60), (int) $result['elapsed_seconds'] % 60) ?></strong><small>Timing never overrides safety accuracy</small></article>
        <article><span>Outcome</span><strong><?= $result['passed'] ? 'PASS' : 'REVIEW' ?></strong><small>Requires hazard and accuracy thresholds</small></article>
    </section>

    <div class="result-grid">
        <section class="result-panel">
            <p class="eyebrow text-teal">Learning reflection</p>
            <h2><?= $result['passed'] ? 'Strong work—keep applying the scan pattern.' : 'A second attempt will help reinforce the weak areas.' ?></h2>
            <p>Systematically scan routes, storage, vehicles, people, and emergency access. Accuracy matters more than clicking quickly.</p>
            <div class="d-flex flex-wrap gap-2"><a class="btn btn-primary" href="/dashboard">Return to dashboard</a><a class="btn btn-outline-secondary" href="/leaderboard">View leaderboard</a></div>
        </section>
        <section class="result-panel">
            <p class="eyebrow text-teal">Achievements</p>
            <?php if ($result['achievements'] === []): ?>
                <p>No achievement earned on this attempt yet. Complete more hazards and improve accuracy to unlock one.</p>
            <?php else: ?>
                <div class="achievement-list">
                    <?php foreach ($result['achievements'] as $achievement): ?>
                        <article><span aria-hidden="true">✦</span><div><strong><?= View::e($achievement['name']) ?></strong><p><?= View::e($achievement['description']) ?></p></div></article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>
</div>

