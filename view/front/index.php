<?php
declare(strict_types=1);
?>
<!doctype html>
<html lang="cs">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TinyCMS</title>
    <link rel="stylesheet" href="<?= htmlspecialchars($url('assets/style.css'), ENT_QUOTES, 'UTF-8') ?>">
</head>
<body>
<div class="container py-5">
    <div class="row">
        <div class="col-8">
            <div class="card p-5 bg-light">
                <h1 class="m-0 mb-3">TinyCMS</h1>
                <p class="m-0 mb-4 text-muted">Minimalistické CMS bez balastu.</p>
                <div class="d-flex gap-2">
                    <a class="btn btn-primary" href="<?= htmlspecialchars($url('login'), ENT_QUOTES, 'UTF-8') ?>">Front login</a>
                    <a class="btn btn-dark" href="<?= htmlspecialchars($url('admin'), ENT_QUOTES, 'UTF-8') ?>">Admin</a>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
