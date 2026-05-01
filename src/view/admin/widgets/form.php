<?php
if (!defined('BASE_DIR')) {
    exit;
}
?>
<form
    id="widgets-form"
    method="post"
    action="<?= esc_url($url('admin/api/v1/widgets')) ?>"
    data-api-submit
    data-stay-on-page
    data-widget-builder
>
    <?= $csrfField() ?>

    <div class="content-editor-layout widget-admin-layout">
        <?php require BASE_DIR . '/' . VIEW_DIR . 'admin/widgets/builder.php'; ?>
    </div>
</form>
