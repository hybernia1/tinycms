<?php
declare(strict_types=1);
$currentPath = trim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '', '/');
?>
<!doctype html>
<html lang="cs">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars((string)$pageTitle, ENT_QUOTES, 'UTF-8') ?> | Admin</title>
    <link rel="stylesheet" href="<?= htmlspecialchars($url('assets/css/style.css'), ENT_QUOTES, 'UTF-8') ?>">
    <link rel="stylesheet" href="<?= htmlspecialchars($url('assets/css/admin.css'), ENT_QUOTES, 'UTF-8') ?>">
    <script defer src="<?= htmlspecialchars($url('assets/js/flash.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
    <script defer src="<?= htmlspecialchars($url('assets/js/modal.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
</head>
<body>
<div class="admin-shell">
    <aside class="admin-sidebar">
        <h2 class="admin-brand">TinyCMS Admin</h2>
        <nav class="admin-nav">
            <?php foreach ($adminMenu as $item):
                $itemPath = trim(parse_url((string)$item['url'], PHP_URL_PATH) ?? '', '/');
                $active = $itemPath !== '' && str_starts_with($currentPath, $itemPath);
            ?>
            <a class="admin-nav-link<?= $active ? ' active' : '' ?>" href="<?= htmlspecialchars((string)$item['url'], ENT_QUOTES, 'UTF-8') ?>">
                <?= htmlspecialchars((string)$item['label'], ENT_QUOTES, 'UTF-8') ?>
            </a>
            <?php endforeach; ?>
        </nav>
    </aside>
    <main class="admin-main">
        <header class="admin-header d-flex justify-between align-center">
            <strong><?= htmlspecialchars((string)$pageTitle, ENT_QUOTES, 'UTF-8') ?></strong>
            <a class="btn btn-dark" href="<?= htmlspecialchars($url('admin/logout'), ENT_QUOTES, 'UTF-8') ?>">Odhlásit</a>
        </header>
        <section class="admin-content">
            <?php foreach ($flashes as $flash): ?>
            <div class="flash flash-<?= htmlspecialchars((string)($flash['type'] ?? 'info'), ENT_QUOTES, 'UTF-8') ?>">
                <span><?= htmlspecialchars((string)($flash['message'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                <button type="button" data-flash-close>×</button>
            </div>
            <?php endforeach; ?>
            <?= $content ?>
        </section>
        <footer class="admin-footer text-muted">TinyCMS Admin</footer>
    </main>
</div>
</body>
</html>
