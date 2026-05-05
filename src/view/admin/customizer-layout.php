<?php
declare(strict_types=1);
if (!defined('BASE_DIR')) {
    exit;
}

$contentHtml = (string)($content ?? '');
$hasMediaLibrary = str_contains($contentHtml, 'data-media-library-open');
$hasPicker = str_contains($contentHtml, 'data-picker');
$hasMenuBuilder = str_contains($contentHtml, 'data-menu-builder');
$hasWidgetBuilder = str_contains($contentHtml, 'data-widget-builder');
$scripts = array_merge(
    ['core.js', 'ui.js', 'api/flash.js', 'api/http.js', 'api/forms.js', 'admin-ui/custom-select.js'],
    $hasMediaLibrary ? ['media-library/orchestrator.js'] : [],
    $hasPicker ? ['picker.js'] : [],
    $hasMenuBuilder || $hasWidgetBuilder ? ['builder-dnd.js'] : [],
    $hasMenuBuilder ? ['menu-builder.js'] : [],
    $hasWidgetBuilder ? ['widget-builder.js'] : [],
    ['theme-customizer.js']
);
?>
<!doctype html>
<html lang="<?= esc_attr((string)$lang) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc_html((string)$pageTitle) ?> | <?= esc_html(t('admin.title_suffix')) ?></title>
    <?php if (!empty($siteFavicon)): ?>
        <link rel="icon" href="<?= esc_url($url((string)$siteFavicon)) ?>">
    <?php endif; ?>
    <link rel="stylesheet" href="<?= esc_url($url(ASSETS_DIR . 'css/style.css')) ?>">
    <link rel="stylesheet" href="<?= esc_url($url(ASSETS_DIR . 'css/admin.css')) ?>">
    <link rel="stylesheet" href="<?= esc_url($url(ASSETS_DIR . 'css/customizer.css')) ?>">
    <script>window.tinycmsI18n = <?= esc_json($adminI18n ?? []) ?>;</script>
    <script>window.tinycmsIconSprite = <?= esc_json(icon_sprite()) ?>;</script>
    <?php foreach ($scripts as $script): ?>
    <script defer src="<?= esc_url($url(ASSETS_DIR . 'js/' . $script)) ?>"></script>
    <?php endforeach; ?>
</head>
<body class="customizer-body">
    <div class="admin-flash-stack" aria-live="polite">
        <?php foreach ($flashes as $flash): ?>
        <?php $flashType = (string)($flash['type'] ?? 'warning'); ?>
        <?php $flashIcon = $flashType === 'success' ? 'success' : ($flashType === 'error' ? 'error' : 'warning'); ?>
        <div class="flash flash-<?= esc_attr($flashType === 'info' ? 'warning' : $flashType) ?>">
            <span class="d-flex align-center gap-2"><?= icon($flashIcon) ?><span><?= esc_html((string)($flash['message'] ?? '')) ?></span></span>
            <button type="button" data-flash-close aria-label="<?= esc_attr(t('admin.close_notice')) ?>" title="<?= esc_attr(t('admin.close_notice')) ?>">
                <?= icon('cancel') ?>
            </button>
        </div>
        <?php endforeach; ?>
    </div>
    <?= $content ?>
    <?php if ($hasMediaLibrary): ?>
        <?php require BASE_DIR . '/' . VIEW_DIR . 'admin/partials/media-library.php'; ?>
    <?php endif; ?>
</body>
</html>
