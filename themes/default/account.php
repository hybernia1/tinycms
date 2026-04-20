<?php

if (!defined('BASE_DIR')) {
    exit;
}

?>
<article class="content-single">
    <h1><?= $e($t('front.account_title')) ?></h1>
    <p><?= $e($t('front.account_welcome')) ?> <strong><?= $e((string)($user['name'] ?? '')) ?></strong></p>
    <p class="text-muted"><?= $e((string)($user['email'] ?? '')) ?></p>
</article>
