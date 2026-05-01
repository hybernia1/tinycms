<?php

if (!defined('BASE_DIR')) {
    exit;
}

?>
<header class="page-heading">
    <p class="eyebrow"><?= esc_html((string)($archiveLabel ?? t('front.archive_for'))) ?></p>
    <h1><?= esc_html((string)($term['name'] ?? '')) ?></h1>
</header>
<?php if ($items === []): ?>
    <p><?= esc_html(t('front.empty')) ?></p>
<?php else: ?>
    <?php include_partial('content-loop'); ?>
    <?= get_pagination() ?>
<?php endif; ?>
