<?php

use Safe360\Core\Auth;
use Safe360\Core\Csrf;
use Safe360\Core\View;

$currentUser = $user ?? Auth::user();
$isAdmin = ($currentUser['role'] ?? null) === 'admin';
?><!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#092f33">
    <meta name="csrf-token" content="<?= View::e(Csrf::token()) ?>">
    <title><?= View::e($title ?? 'Safe360') ?> · Safe360</title>
    <link rel="stylesheet" href="/assets/vendor/bootstrap.min.css">
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body class="<?= !empty($trainingMode) ? 'training-body' : '' ?>">
    <a class="skip-link" href="#main-content">Skip to content</a>
    <header class="site-header">
        <nav class="navbar navbar-expand-lg" aria-label="Primary navigation">
            <div class="container-fluid app-container">
                <a class="brand" href="<?= $isAdmin ? '/admin' : '/dashboard' ?>" aria-label="Safe360 home">
                    <span class="brand-mark" aria-hidden="true"><span></span></span>
                    <span><strong>Safe</strong>360</span>
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Open navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="mainNav">
                    <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-1">
                        <?php if ($isAdmin): ?>
                            <li class="nav-item"><a class="nav-link" href="/admin">Overview</a></li>
                            <li class="nav-item"><a class="nav-link" href="/admin/content">Training content</a></li>
                            <li class="nav-item"><a class="nav-link" href="/leaderboard">Leaderboard</a></li>
                        <?php else: ?>
                            <li class="nav-item"><a class="nav-link" href="/dashboard">Dashboard</a></li>
                            <li class="nav-item"><a class="nav-link" href="/history">History</a></li>
                            <li class="nav-item"><a class="nav-link" href="/leaderboard">Leaderboard</a></li>
                        <?php endif; ?>
                        <li class="nav-item nav-user"><span><?= View::e($currentUser['name'] ?? '') ?></span></li>
                        <li class="nav-item">
                            <form action="/logout" method="post">
                                <input type="hidden" name="_csrf" value="<?= View::e(Csrf::token()) ?>">
                                <button class="btn btn-sm btn-outline-light" type="submit">Sign out</button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
    </header>
    <main id="main-content" class="<?= !empty($trainingMode) ? 'training-main' : 'page-main' ?>">
        <?= $content ?>
    </main>
    <footer class="site-footer <?= !empty($trainingMode) ? 'd-none' : '' ?>">
        <div class="app-container footer-inner">
            <span>Safe360 academic prototype</span>
            <span>Safety content requires organisational validation before operational use.</span>
        </div>
    </footer>
    <div class="toast-container position-fixed bottom-0 end-0 p-3" aria-live="polite" aria-atomic="true"></div>
    <script src="/assets/vendor/bootstrap.bundle.min.js" defer></script>
    <script src="/assets/js/app.js" type="module"></script>
    <?php if (!empty($trainingMode)): ?>
        <script src="/assets/vendor/marzipano.js" defer></script>
        <script src="/assets/js/training.js" type="module"></script>
    <?php endif; ?>
    <?php if (!empty($adminDashboard)): ?>
        <script src="/assets/vendor/chart.umd.js" defer></script>
        <script src="/assets/js/admin.js" type="module"></script>
    <?php endif; ?>
</body>
</html>

