<?php

if (!defined('BASE_DIR')) {
    exit;
}

?>
<article class="content-single account-page">
    <img class="account-avatar" src="<?= esc_url(user_avatar_url($user, 112)) ?>" alt="">
    <p class="eyebrow"><?= esc_html(t('front.account_title')) ?></p>
    <h1><?= esc_html((string)($user['name'] ?? '')) ?></h1>
    <p><?= esc_html(t('front.account_welcome')) ?> <strong><?= esc_html((string)($user['name'] ?? '')) ?></strong></p>
    <p class="text-muted"><?= esc_html((string)($user['email'] ?? '')) ?></p>
</article>
