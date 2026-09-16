<?php use Safe360\Core\View; ?>
<div class="app-container narrow-page">
    <header class="page-heading"><p class="eyebrow text-teal">Your evidence</p><h1>Attempt history</h1><p>Review completed learning and track improvement over time.</p></header>
    <div class="table-responsive card-table">
        <table class="table align-middle mb-0">
            <thead><tr><th>Module</th><th>Started</th><th>Status</th><th>Score</th><th>Hazards</th><th>Accuracy</th><th>Outcome</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($history as $attempt): ?>
                <tr>
                    <td><strong><?= View::e($attempt['module_title']) ?></strong></td>
                    <td><?= View::e(date('d M Y, H:i', strtotime((string) $attempt['started_at']))) ?></td>
                    <td><?= View::e(ucwords(str_replace('_', ' ', $attempt['state']))) ?></td>
                    <td><?= number_format((int) $attempt['score']) ?></td>
                    <td><?= number_format((float) $attempt['hazard_percent'], 0) ?>%</td>
                    <td><?= number_format((float) $attempt['accuracy_percent'], 0) ?>%</td>
                    <td><?php if ($attempt['passed'] !== null): ?><span class="result-badge <?= $attempt['passed'] ? 'result-pass' : 'result-fail' ?>"><?= $attempt['passed'] ? 'Pass' : 'Review needed' ?></span><?php else: ?>—<?php endif; ?></td>
                    <td><?php if ($attempt['state'] === 'completed'): ?><a class="text-link" href="/results/<?= (int) $attempt['id'] ?>">Open</a><?php endif; ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

