<?php
declare(strict_types=1);
?>
<!doctype html>
<html lang="cs">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin dashboard</title>
    <link rel="stylesheet" href="<?= htmlspecialchars($url('assets/style.css'), ENT_QUOTES, 'UTF-8') ?>">
</head>
<body>
<div class="container py-5">
    <div class="row">
        <div class="col-8">
            <div class="card p-5 bg-light">
                <div class="d-flex justify-between align-center mb-4">
                    <h1 class="m-0">Admin dashboard</h1>
                    <a class="btn btn-dark" href="<?= htmlspecialchars($url('admin/logout'), ENT_QUOTES, 'UTF-8') ?>">Odhlásit</a>
                </div>
                <p class="m-0 text-muted">Přihlášen: <?= htmlspecialchars((string)($user['name'] ?? 'Uživatel'), ENT_QUOTES, 'UTF-8') ?></p>
            </div>
        </div>
    </div>
</div>
</body>
</html>
