<?php
declare(strict_types=1);
?>
<!doctype html>
<html lang="<?= htmlspecialchars((string)$lang, ENT_QUOTES, 'UTF-8') ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars((string)($pageTitle ?? 'TinyCMS'), ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="<?= htmlspecialchars($url('assets/css/style.css'), ENT_QUOTES, 'UTF-8') ?>">
    <script defer src="<?= htmlspecialchars($url('assets/js/flash.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
    <script defer src="<?= htmlspecialchars($url('assets/js/modal.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
    <script defer src="<?= htmlspecialchars($url('assets/js/password-toggle.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
</head>
<body>
<div class="container mt-4">
    <?php foreach ($flashes as $flash): ?>
    <div class="flash flash-<?= htmlspecialchars((string)($flash['type'] ?? 'info'), ENT_QUOTES, 'UTF-8') ?>">
        <span><?= htmlspecialchars((string)($flash['message'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
        <button type="button" data-flash-close aria-label="<?= htmlspecialchars($t('admin.close_notice'), ENT_QUOTES, 'UTF-8') ?>" title="<?= htmlspecialchars($t('admin.close_notice'), ENT_QUOTES, 'UTF-8') ?>">
            <?= $icon('cancel') ?>
        </button>
    </div>
    <?php endforeach; ?>
</div>
<?= $content ?>
<footer class="container py-4 text-muted">
    <?= htmlspecialchars((string)($siteFooter ?? '© TinyCMS'), ENT_QUOTES, 'UTF-8') ?>
</footer>
</body>
</html>
