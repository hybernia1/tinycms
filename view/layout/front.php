<?php
declare(strict_types=1);
?>
<!doctype html>
<html lang="cs">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php
    $siteTitleValue = $site_title();
    $pageTitleValue = (string)($pageTitle ?? $siteTitleValue);
    $documentTitle = $pageTitleValue === $siteTitleValue ? $siteTitleValue : $pageTitleValue . ' | ' . $siteTitleValue;
    ?>
    <title><?= $escape($documentTitle) ?></title>
    <link rel="stylesheet" href="<?= $escape($url('assets/css/style.css')) ?>">
    <script defer src="<?= $escape($url('assets/vendor/jquery-4.0.0.min.js')) ?>"></script>
    <script defer src="<?= $escape($url('assets/js/flash.js')) ?>"></script>
    <script defer src="<?= $escape($url('assets/js/modal.js')) ?>"></script>
</head>
<body data-theme="<?= $escape((string)$theme) ?>">
<div class="container mt-4">
    <?php foreach ($flashes as $flash): ?>
    <div class="flash flash-<?= $escape((string)($flash['type'] ?? 'info')) ?>">
        <span><?= $escape((string)($flash['message'] ?? '')) ?></span>
        <button type="button" data-flash-close aria-label="Zavřít notifikaci" title="Zavřít notifikaci">
            <?= $icon('cancel') ?>
        </button>
    </div>
    <?php endforeach; ?>
</div>
<?= $content ?>
<footer class="container py-4 text-muted">
    <?= $escape((string)($siteFooter ?? '© TinyCMS')) ?>
</footer>
</body>
</html>
