<?php

if (!defined('BASE_DIR')) {
    exit;
}

?>
<article class="content-single">
    <h1><?= $escHtml($t('front.account_title')) ?></h1>
    <p><?= $escHtml($t('front.account_welcome')) ?> <strong><?= $escHtml((string)($user['name'] ?? '')) ?></strong></p>
    <p class="text-muted"><?= $escHtml((string)($user['email'] ?? '')) ?></p>
</article>
