<?php use Safe360\Core\View; ?>
<div class="app-container narrow-page">
    <header class="page-heading page-heading-row">
        <div><p class="eyebrow text-teal">Structured authoring</p><h1>Training content</h1><p>Manage module rules, 360° scenes, hazards, questions, answers, and learning feedback. Historical attempts retain their original snapshot.</p></div>
        <a class="btn btn-outline-secondary" href="/admin">Back to overview</a>
    </header>
    <?php if ($notice): ?><div class="alert alert-success" role="status"><?= View::e($notice) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger" role="alert"><?= View::e($error) ?></div><?php endif; ?>

    <section class="admin-authoring-section">
        <div class="section-heading"><div><p class="eyebrow">Module configuration</p><h2>Pass rules and publishing</h2></div></div>
        <?php foreach ($modules as $module): ?>
            <form action="/admin/modules/<?= (int) $module['id'] ?>" method="post" class="card-panel editor-form mb-3">
                <input type="hidden" name="_csrf" value="<?= View::e($csrf) ?>">
                <div class="row g-3">
                    <div class="col-md-7"><label class="form-label" for="module-title-<?= (int) $module['id'] ?>">Title</label><input class="form-control" id="module-title-<?= (int) $module['id'] ?>" name="title" value="<?= View::e($module['title']) ?>" required></div>
                    <div class="col-md-3"><label class="form-label" for="module-difficulty-<?= (int) $module['id'] ?>">Difficulty</label><input class="form-control" id="module-difficulty-<?= (int) $module['id'] ?>" name="difficulty" value="<?= View::e($module['difficulty']) ?>" required></div>
                    <div class="col-md-2"><label class="form-label" for="module-status-<?= (int) $module['id'] ?>">Status</label><select class="form-select" id="module-status-<?= (int) $module['id'] ?>" name="status"><?php foreach (['draft','active','inactive'] as $status): ?><option value="<?= $status ?>" <?= $module['status'] === $status ? 'selected' : '' ?>><?= ucfirst($status) ?></option><?php endforeach; ?></select></div>
                    <div class="col-12"><label class="form-label" for="module-description-<?= (int) $module['id'] ?>">Description</label><textarea class="form-control" id="module-description-<?= (int) $module['id'] ?>" name="description" rows="2" required><?= View::e($module['description']) ?></textarea></div>
                    <div class="col-md-3"><label class="form-label" for="hazard-pass-<?= (int) $module['id'] ?>">Hazards required (%)</label><input class="form-control" id="hazard-pass-<?= (int) $module['id'] ?>" name="min_hazard_percent" type="number" min="0" max="100" value="<?= (int) $module['min_hazard_percent'] ?>" required></div>
                    <div class="col-md-3"><label class="form-label" for="accuracy-pass-<?= (int) $module['id'] ?>">Accuracy required (%)</label><input class="form-control" id="accuracy-pass-<?= (int) $module['id'] ?>" name="min_accuracy_percent" type="number" min="0" max="100" value="<?= (int) $module['min_accuracy_percent'] ?>" required></div>
                    <div class="col-md-6 d-flex align-items-end justify-content-md-end"><button class="btn btn-primary" type="submit">Save module version</button></div>
                </div>
            </form>
        <?php endforeach; ?>
    </section>

    <section class="admin-authoring-section">
        <div class="section-heading"><div><p class="eyebrow">Scene configuration</p><h2>Panoramas and starting views</h2></div></div>
        <div class="content-editor-list">
            <?php foreach ($scenes as $scene): ?>
                <details class="content-editor">
                    <summary><span><small>Scene <?= (int) $scene['sequence_no'] ?></small><strong><?= View::e($scene['title']) ?></strong></span><span class="status-pill"><?= View::e(ucfirst($scene['status'])) ?></span></summary>
                    <form action="/admin/scenes/<?= (int) $scene['id'] ?>" method="post" class="editor-form">
                        <input type="hidden" name="_csrf" value="<?= View::e($csrf) ?>">
                        <div class="row g-3">
                            <div class="col-md-8"><label class="form-label">Scene title</label><input class="form-control" name="title" value="<?= View::e($scene['title']) ?>" required></div>
                            <div class="col-md-4"><label class="form-label">Status</label><select class="form-select" name="status"><?php foreach (['draft','active','inactive'] as $status): ?><option value="<?= $status ?>" <?= $scene['status'] === $status ? 'selected' : '' ?>><?= ucfirst($status) ?></option><?php endforeach; ?></select></div>
                            <div class="col-12"><label class="form-label">Description</label><textarea class="form-control" name="description" rows="2" required><?= View::e($scene['description']) ?></textarea></div>
                            <div class="col-md-8"><label class="form-label">Local panorama path</label><input class="form-control" name="panorama_path" value="<?= View::e($scene['panorama_path']) ?>" pattern="/assets/panoramas/[A-Za-z0-9._-]+" required></div>
                            <div class="col-md-4"><label class="form-label">Time limit (seconds)</label><input class="form-control" name="time_limit_seconds" type="number" min="30" max="7200" value="<?= View::e($scene['time_limit_seconds']) ?>"></div>
                            <div class="col-md-4"><label class="form-label">Initial yaw</label><input class="form-control" name="initial_yaw" type="number" min="-3.141593" max="3.141593" step="0.000001" value="<?= View::e($scene['initial_yaw']) ?>" required></div>
                            <div class="col-md-4"><label class="form-label">Initial pitch</label><input class="form-control" name="initial_pitch" type="number" min="-1.570797" max="1.570797" step="0.000001" value="<?= View::e($scene['initial_pitch']) ?>" required></div>
                            <div class="col-md-4"><label class="form-label">Initial FOV</label><input class="form-control" name="initial_fov" type="number" min="0.5" max="2.2" step="0.01" value="<?= View::e($scene['initial_fov']) ?>" required></div>
                        </div>
                        <div class="editor-actions"><p>Use a validated 2:1 equirectangular image in the local panorama directory.</p><button class="btn btn-primary" type="submit">Save scene version</button></div>
                    </form>
                </details>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="admin-authoring-section">
        <div class="section-heading"><div><p class="eyebrow">Hazard authoring</p><h2>Create a draft hazard</h2></div></div>
        <details class="content-editor create-editor">
            <summary><span><small>New content</small><strong>Add hazard, question, and four answers</strong></span><span class="status-pill">Draft</span></summary>
            <form action="/admin/hazards" method="post" class="editor-form">
                <input type="hidden" name="_csrf" value="<?= View::e($csrf) ?>">
                <div class="row g-3">
                    <div class="col-md-5"><label class="form-label">Scene</label><select class="form-select" name="scene_id" required><?php foreach ($scenes as $scene): ?><option value="<?= (int) $scene['id'] ?>"><?= View::e($scene['title']) ?></option><?php endforeach; ?></select></div>
                    <div class="col-md-3"><label class="form-label">Unique code</label><input class="form-control" name="code" pattern="[A-Za-z0-9-]{2,30}" placeholder="LB-NEW-01" required></div>
                    <div class="col-md-4"><label class="form-label">Severity</label><select class="form-select" name="severity"><?php foreach (['low','medium','high','critical'] as $severity): ?><option value="<?= $severity ?>"><?= ucfirst($severity) ?></option><?php endforeach; ?></select></div>
                    <div class="col-md-8"><label class="form-label">Hazard title</label><input class="form-control" name="title" required></div>
                    <div class="col-md-4"><label class="form-label">Category</label><input class="form-control" name="category" placeholder="Housekeeping" required></div>
                    <div class="col-md-3"><label class="form-label">Yaw (radians)</label><input class="form-control" name="yaw" type="number" min="-3.141593" max="3.141593" step="0.000001" value="0" required></div>
                    <div class="col-md-3"><label class="form-label">Pitch (radians)</label><input class="form-control" name="pitch" type="number" min="-1.570797" max="1.570797" step="0.000001" value="0" required></div>
                    <div class="col-md-6"><label class="form-label">Question</label><input class="form-control" name="question" required></div>
                    <div class="col-12"><label class="form-label">Learning explanation</label><textarea class="form-control" name="explanation" rows="3" required></textarea></div>
                    <div class="col-12"><fieldset class="option-editor"><legend>Answer options <small>Select the correct answer</small></legend><div class="row g-2"><?php for ($index = 0; $index < 4; $index++): ?><div class="col-md-6"><div class="input-group"><span class="input-group-text"><input class="form-check-input mt-0" type="radio" name="correct_index" value="<?= $index ?>" <?= $index === 0 ? 'checked' : '' ?> required></span><input class="form-control" name="new_options[]" placeholder="Option <?= $index + 1 ?>" required></div></div><?php endfor; ?></div></fieldset></div>
                </div>
                <div class="editor-actions"><p>New hazards remain draft until reviewed and activated.</p><button class="btn btn-primary" type="submit">Create draft hazard</button></div>
            </form>
        </details>
    </section>

    <section class="admin-authoring-section">
        <div class="section-heading"><div><p class="eyebrow">Existing hazards</p><h2>Edit assessed content</h2></div></div>
        <div class="content-editor-list">
            <?php foreach ($rows as $row): ?>
                <details class="content-editor">
                    <summary><span><small><?= View::e($row['scene_title']) ?></small><strong><?= View::e($row['code']) ?> · <?= View::e($row['hazard_title']) ?></strong></span><span class="severity-chip severity-<?= View::e($row['severity']) ?>"><?= View::e(ucfirst($row['severity'])) ?></span></summary>
                    <form action="/admin/hazards/<?= (int) $row['hazard_id'] ?>" method="post" class="editor-form">
                        <input type="hidden" name="_csrf" value="<?= View::e($csrf) ?>">
                        <div class="row g-3">
                            <div class="col-md-7"><label class="form-label">Hazard title</label><input class="form-control" name="title" value="<?= View::e($row['hazard_title']) ?>" required></div>
                            <div class="col-md-3"><label class="form-label">Category</label><input class="form-control" name="category" value="<?= View::e($row['category']) ?>" required></div>
                            <div class="col-md-2"><label class="form-label">Severity</label><select class="form-select" name="severity"><?php foreach (['low','medium','high','critical'] as $severity): ?><option value="<?= $severity ?>" <?= $row['severity'] === $severity ? 'selected' : '' ?>><?= ucfirst($severity) ?></option><?php endforeach; ?></select></div>
                            <div class="col-md-2"><label class="form-label">Yaw</label><input class="form-control" name="yaw" type="number" min="-3.141593" max="3.141593" step="0.000001" value="<?= View::e($row['yaw']) ?>" required></div>
                            <div class="col-md-2"><label class="form-label">Pitch</label><input class="form-control" name="pitch" type="number" min="-1.570797" max="1.570797" step="0.000001" value="<?= View::e($row['pitch']) ?>" required></div>
                            <div class="col-md-8"><label class="form-label">Question</label><input class="form-control" name="question" value="<?= View::e($row['question_text']) ?>" required></div>
                            <div class="col-12"><label class="form-label">Learning explanation</label><textarea class="form-control" name="explanation" rows="3" required><?= View::e($row['explanation']) ?></textarea></div>
                            <div class="col-12"><fieldset class="option-editor"><legend>Answer options <small>Select the one correct answer</small></legend><div class="row g-2"><?php foreach ($row['options'] as $option): ?><div class="col-md-6"><div class="input-group"><span class="input-group-text"><input class="form-check-input mt-0" type="radio" name="correct_option" value="<?= (int) $option['id'] ?>" <?= $option['is_correct'] ? 'checked' : '' ?> required></span><input class="form-control" name="options[<?= (int) $option['id'] ?>]" value="<?= View::e($option['option_text']) ?>" required></div></div><?php endforeach; ?></div></fieldset></div>
                        </div>
                        <div class="editor-actions"><p>Prototype content—obtain safety/client validation before operational use.</p><button class="btn btn-primary" type="submit">Save new content version</button></div>
                    </form>
                </details>
            <?php endforeach; ?>
        </div>
    </section>
</div>
