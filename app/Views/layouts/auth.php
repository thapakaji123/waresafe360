<?php

use Safe360\Core\View;

?><!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#092f33">
    <title><?= View::e($title ?? 'Safe360') ?> · Safe360</title>
    <link rel="stylesheet" href="/assets/vendor/bootstrap.min.css">
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body class="auth-body">
    <a class="skip-link" href="#main-content">Skip to content</a>
    <main id="main-content" class="auth-shell">
        <?= $content ?>
    </main>
    <script src="/assets/vendor/bootstrap.bundle.min.js" defer></script>
</body>
</html>

