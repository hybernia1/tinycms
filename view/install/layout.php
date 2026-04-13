<?php
declare(strict_types=1);
?>
<!doctype html>
<html lang="<?= $e((string)$lang) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $e((string)($pageTitle ?? 'TinyCMS')) ?></title>
    <link rel="stylesheet" href="<?= $e($url('assets/css/style.css')) ?>">
    <script>window.tinycmsIconSprite = <?= json_encode($url('assets/svg/icons.svg'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;</script>
    <script defer src="<?= $e($url('assets/js/flash.js')) ?>"></script>
    <script defer src="<?= $e($url('assets/js/modal.js')) ?>"></script>
    <script defer src="<?= $e($url('assets/js/custom-select.js')) ?>"></script>
    <script defer src="<?= $e($url('assets/js/password-toggle.js')) ?>"></script>
</head>
<body>
<div class="container mt-4">
    <?php foreach ($flashes as $flash): ?>
    <?php $flashType = (string)($flash['type'] ?? 'warning'); ?>
    <?php $flashIcon = $flashType === 'success' ? 'success' : ($flashType === 'error' ? 'error' : 'warning'); ?>
    <div class="flash flash-<?= $e($flashType === 'info' ? 'warning' : $flashType) ?>">
        <span class="d-flex align-center gap-2"><?= $icon($flashIcon) ?><span><?= $e((string)($flash['message'] ?? '')) ?></span></span>
        <button type="button" data-flash-close aria-label="<?= $e($t('admin.close_notice')) ?>" title="<?= $e($t('admin.close_notice')) ?>">
            <?= $icon('cancel') ?>
        </button>
    </div>
    <?php endforeach; ?>
</div>
<?= $content ?>
</body>
</html>
