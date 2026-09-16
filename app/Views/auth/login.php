<?php

use Safe360\Core\View;

?>
<section class="auth-brand-panel" aria-label="Safe360 introduction">
    <div class="auth-brand-content">
        <a class="brand brand-light" href="/">
            <span class="brand-mark" aria-hidden="true"><span></span></span>
            <span><strong>Safe</strong>360</span>
        </a>
        <p class="eyebrow">Warehouse hazard perception</p>
        <h1>See the risk.<br>Choose the safe response.</h1>
        <p class="auth-lead">Step into interactive warehouse scenes, identify hidden hazards, and turn every decision into safer workplace awareness.</p>
        <div class="auth-proof" aria-label="Training benefits">
            <div><strong>360°</strong><span>active exploration</span></div>
            <div><strong>36</strong><span>assessed hazards</span></div>
            <div><strong>Live</strong><span>learning feedback</span></div>
        </div>
    </div>
</section>

<section class="auth-form-panel">
    <div class="auth-card">
        <p class="eyebrow text-teal">Secure training portal</p>
        <h2>Welcome back</h2>
        <p class="text-secondary">Sign in to continue your assigned training.</p>

        <?php if ($error): ?>
            <div class="alert alert-danger" role="alert"><?= View::e($error) ?></div>
        <?php endif; ?>

        <form action="/login" method="post" novalidate>
            <input type="hidden" name="_csrf" value="<?= View::e($csrf) ?>">
            <div class="mb-3">
                <label class="form-label" for="email">Email address</label>
                <input class="form-control form-control-lg" id="email" name="email" type="email" autocomplete="username" required autofocus>
            </div>
            <div class="mb-2">
                <label class="form-label" for="password">Password</label>
                <input class="form-control form-control-lg" id="password" name="password" type="password" autocomplete="current-password" required>
            </div>
            <button class="btn btn-primary btn-lg w-100 mt-3" type="submit">Sign in securely</button>
        </form>

        <div class="demo-credentials">
            <strong>Demonstration accounts</strong>
            <span>Trainee: trainee@safe360.test / Safe360!Trainee</span>
            <span>Trainer: admin@safe360.test / Safe360!Admin</span>
        </div>
    </div>
</section>

