<?php

if (!defined('BASE_DIR')) {
    exit;
}

?>
<section class="error-page">
    <h1><?= $escHtml($t('front.not_found_title')) ?></h1>
    <p><?= $escHtml($t('front.not_found_text')) ?></p>
    <p><a href="<?= $escUrl($url('')) ?>"><?= $escHtml($t('front.not_found_home')) ?></a></p>
</section>
