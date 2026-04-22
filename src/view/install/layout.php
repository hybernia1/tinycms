<?php
declare(strict_types=1);
if (!defined('BASE_DIR')) {
    exit;
}

?>
<!doctype html>
<html lang="<?= $escHtml((string)$lang) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $escHtml((string)($pageTitle ?? 'TinyCMS')) ?></title>
    <link rel="stylesheet" href="<?= $escUrl($url(ASSETS_DIR . 'css/style.css')) ?>">
    <script>window.tinycmsIconSprite = <?= json_encode($url(ASSETS_DIR . 'svg/icons.svg'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;</script>
    <script defer src="<?= $escUrl($url(ASSETS_DIR . 'js/flash.js')) ?>"></script>
    <script defer src="<?= $escUrl($url(ASSETS_DIR . 'js/modal.js')) ?>"></script>
    <script defer src="<?= $escUrl($url(ASSETS_DIR . 'js/custom-select.js')) ?>"></script>
    <script defer src="<?= $escUrl($url(ASSETS_DIR . 'js/password-toggle.js')) ?>"></script>
</head>
<body>
<div class="container mt-4">
    <?php foreach ($flashes as $flash): ?>
    <?php $flashType = (string)($flash['type'] ?? 'warning'); ?>
    <?php $flashIcon = $flashType === 'success' ? 'success' : ($flashType === 'error' ? 'error' : 'warning'); ?>
    <div class="flash flash-<?= $escHtml($flashType === 'info' ? 'warning' : $flashType) ?>">
        <span class="d-flex align-center gap-2"><?= $icon($flashIcon) ?><span><?= $escHtml((string)($flash['message'] ?? '')) ?></span></span>
        <button type="button" data-flash-close aria-label="<?= $escHtml($t('admin.close_notice')) ?>" title="<?= $escHtml($t('admin.close_notice')) ?>">
            <?= $icon('cancel') ?>
        </button>
    </div>
    <?php endforeach; ?>
</div>
<?= $content ?>
</body>
</html>
