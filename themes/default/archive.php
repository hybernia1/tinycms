<?php

if (!defined('BASE_DIR')) {
    exit;
}

?>
<h1><?= esc_html((string)($archiveLabel ?? t('front.archive_for'))) ?>: <?= esc_html((string)($term['name'] ?? '')) ?></h1>
<?php if ($items === []): ?>
    <p><?= esc_html(t('front.empty')) ?></p>
<?php else: ?>
    <?php include_partial('content-loop'); ?>
    <?= get_pagination() ?>
<?php endif; ?>
