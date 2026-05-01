<?php

if (!defined('BASE_DIR')) {
    exit;
}

if (($mode ?? 'loop') === 'content'): ?>
    <?php include_partial('content-single', ['show_terms' => false]); ?>
<?php else: ?>
    <header class="page-heading">
        <p class="eyebrow"><?= esc_html(t('front.noir_dispatch')) ?></p>
        <h1><?= esc_html(t('front.latest_content')) ?></h1>
    </header>
    <?php if ($items === []): ?>
        <p><?= esc_html(t('front.empty')) ?></p>
    <?php else: ?>
        <?php include_partial('content-loop'); ?>
        <?= get_pagination() ?>
    <?php endif; ?>
<?php endif; ?>
