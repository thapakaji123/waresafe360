<?php

use Safe360\Core\View;
$adminDashboard = true;
?>
<div class="app-container admin-page" id="adminDashboard" data-accuracy="<?= View::e((string) $report['average_accuracy']) ?>" data-completion="<?= View::e((string) $report['completion_rate']) ?>">
    <header class="page-heading page-heading-row"><div><p class="eyebrow text-teal">Trainer control centre</p><h1>Training performance</h1><p>Monitor completion, identify learning gaps, and maintain reliable training evidence.</p></div><a class="btn btn-primary" href="/admin/content">Manage training content</a></header>
    <section class="metric-grid" aria-label="Performance indicators">
        <article><span>Active trainees</span><strong><?= number_format($report['active_trainees']) ?></strong><small>Enabled learner accounts</small></article>
        <article><span>Completion rate</span><strong><?= number_format($report['completion_rate'], 1) ?>%</strong><small>Non-abandoned attempts</small></article>
        <article><span>Average score</span><strong><?= number_format($report['average_score']) ?></strong><small>Completed attempts</small></article>
        <article><span>Average accuracy</span><strong><?= number_format($report['average_accuracy'], 1) ?>%</strong><small>Completed attempts</small></article>
    </section>

    <div class="admin-grid">
        <section class="admin-panel chart-panel"><div class="panel-heading"><div><p class="eyebrow text-teal">Snapshot</p><h2>Learning outcomes</h2></div></div><canvas id="outcomeChart" aria-label="Completion and accuracy bar chart" role="img"></canvas></section>
        <section class="admin-panel"><div class="panel-heading"><div><p class="eyebrow text-teal">Content coverage</p><h2>Published module</h2></div></div><dl class="content-counts"><div><dt>Modules</dt><dd><?= $report['content_counts']['modules'] ?></dd></div><div><dt>Scenes</dt><dd><?= $report['content_counts']['scenes'] ?></dd></div><div><dt>Hazards</dt><dd><?= $report['content_counts']['hazards'] ?></dd></div><div><dt>Questions</dt><dd><?= $report['content_counts']['questions'] ?></dd></div></dl></section>
    </div>

    <section class="admin-panel section-block"><div class="panel-heading"><div><p class="eyebrow text-teal">Latest activity</p><h2>Recent attempts</h2></div></div><div class="table-responsive"><table class="table align-middle"><thead><tr><th>Trainee</th><th>Status</th><th>Score</th><th>Accuracy</th><th>Hazards</th><th>Outcome</th><th></th></tr></thead><tbody><?php foreach ($report['recent_attempts'] as $attempt): ?><tr><td><strong><?= View::e($attempt['name']) ?></strong></td><td><?= View::e(ucwords(str_replace('_', ' ', $attempt['state']))) ?></td><td><?= number_format((int) $attempt['score']) ?></td><td><?= number_format((float) $attempt['accuracy_percent'], 0) ?>%</td><td><?= number_format((float) $attempt['hazard_percent'], 0) ?>%</td><td><?= $attempt['passed'] === null ? '—' : ($attempt['passed'] ? 'Pass' : 'Review') ?></td><td><a class="text-link" href="/results/<?= (int) $attempt['id'] ?>">Open</a></td></tr><?php endforeach; ?></tbody></table></div></section>

    <div class="admin-grid section-block">
        <section class="admin-panel"><div class="panel-heading"><div><p class="eyebrow text-teal">Knowledge gaps</p><h2>Most difficult hazards</h2></div></div><?php if ($report['difficult_hazards'] === []): ?><p class="text-secondary">Difficulty data appears after trainees submit responses.</p><?php else: ?><div class="gap-list"><?php foreach ($report['difficult_hazards'] as $hazard): ?><article><div><strong><?= View::e($hazard['code']) ?> · <?= View::e($hazard['title']) ?></strong><span><?= View::e($hazard['scene_title']) ?></span></div><span><?= number_format((float) $hazard['error_rate'], 0) ?>% incorrect</span></article><?php endforeach; ?></div><?php endif; ?></section>
        <section class="admin-panel"><div class="panel-heading"><div><p class="eyebrow text-teal">Access control</p><h2>User accounts</h2></div></div><div class="user-list"><?php foreach ($report['users'] as $account): ?><article><div><strong><?= View::e($account['name']) ?></strong><span><?= View::e($account['email']) ?> · <?= View::e(ucfirst($account['role'])) ?></span></div><?php if ((int) $account['id'] !== (int) $user['id']): ?><button class="btn btn-sm <?= $account['status'] === 'active' ? 'btn-outline-danger' : 'btn-outline-success' ?> user-status" type="button" data-user-id="<?= (int) $account['id'] ?>" data-next-status="<?= $account['status'] === 'active' ? 'inactive' : 'active' ?>"><?= $account['status'] === 'active' ? 'Deactivate' : 'Activate' ?></button><?php else: ?><span class="status-pill status-ready">Current account</span><?php endif; ?></article><?php endforeach; ?></div></section>
    </div>
</div>

