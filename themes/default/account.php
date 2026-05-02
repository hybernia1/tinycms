<?php

if (!defined('BASE_DIR')) {
    exit;
}

?>
<article class="content-single">
    <?= get_avatar($user, 'account-avatar', 112) ?>
    <h1><?= esc_html(t('front.account_title')) ?></h1>
    <p><?= esc_html(t('front.account_welcome')) ?> <strong><?= esc_html((string)($user['name'] ?? '')) ?></strong></p>
    <p class="text-muted"><?= esc_html((string)($user['email'] ?? '')) ?></p>
</article>
