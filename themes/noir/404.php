<?php

if (!defined('BASE_DIR')) {
    exit;
}

?>
<section class="error-page">
    <p class="eyebrow">404</p>
    <h1><?= esc_html(t('front.not_found_title')) ?></h1>
    <p><?= esc_html(t('front.not_found_text')) ?></p>
    <p><a href="<?= esc_url(site_url()) ?>"><?= esc_html(t('front.not_found_home')) ?></a></p>
</section>
