<?php

if (!defined('BASE_DIR')) {
    exit;
}

?>
<section class="error-page">
    <h1><?= esc_html(t('front.not_found_title')) ?></h1>
    <p><?= esc_html(t('front.not_found_text')) ?></p>
    <p><a href="<?= esc_url($url('')) ?>"><?= esc_html(t('front.not_found_home')) ?></a></p>
</section>
