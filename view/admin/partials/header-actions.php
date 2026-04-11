<?php
$headerAction = (string)($headerAction ?? '');
$contentCanDelete = (bool)($contentCanDelete ?? false);
?>
<?php if ($headerAction === 'media_edit'): ?>
<div class="admin-header-action-menu" data-save-action-menu data-save-action-form="#media-editor-form">
    <div class="admin-header-action-split">
        <button class="btn btn-primary admin-header-action-main" type="button" data-save-action-primary>
            <span><?= htmlspecialchars($t('common.save'), ENT_QUOTES, 'UTF-8') ?></span>
        </button>
        <button class="btn btn-primary btn-icon admin-header-action-toggle" type="button" data-save-action-toggle aria-expanded="false" aria-label="<?= htmlspecialchars($t('common.actions'), ENT_QUOTES, 'UTF-8') ?>">
            <?= $icon('next', 'icon content-action-summary-arrow') ?>
        </button>
    </div>
    <div class="admin-header-action-options" hidden>
        <div class="admin-header-action-group">
            <button class="btn btn-light admin-header-action-option" type="button" data-save-action-submit>
                <span><?= htmlspecialchars($t('common.save'), ENT_QUOTES, 'UTF-8') ?></span>
            </button>
        </div>
        <div class="admin-header-action-group admin-header-action-group-danger">
            <button class="btn btn-danger admin-header-action-option" type="button" data-save-action-delete data-modal-open data-modal-target="#media-delete-modal">
                <span><?= htmlspecialchars($t('common.delete'), ENT_QUOTES, 'UTF-8') ?></span>
                <?= $icon('delete') ?>
            </button>
        </div>
    </div>
</div>
<?php elseif ($headerAction === 'media_add'): ?>
<button class="btn btn-primary" type="button" data-save-action-form-submit="#media-editor-form">
    <span><?= htmlspecialchars($t('common.save'), ENT_QUOTES, 'UTF-8') ?></span>
</button>
<?php elseif ($headerAction === 'users_form'): ?>
<button class="btn btn-primary" type="button" data-save-action-form-submit="#users-editor-form">
    <span><?= htmlspecialchars($t('common.save'), ENT_QUOTES, 'UTF-8') ?></span>
</button>
<?php elseif ($headerAction === 'terms_form'): ?>
<button class="btn btn-primary" type="button" data-save-action-form-submit="#terms-editor-form">
    <span><?= htmlspecialchars($t('common.save'), ENT_QUOTES, 'UTF-8') ?></span>
</button>
<?php elseif ($headerAction === 'settings_form'): ?>
<button class="btn btn-primary" type="button" data-save-action-form-submit="#settings-form">
    <span><?= htmlspecialchars($t('common.save'), ENT_QUOTES, 'UTF-8') ?></span>
</button>
<?php elseif ($headerAction === 'users_list'): ?>
<a class="btn btn-primary" href="<?= htmlspecialchars($url('admin/users/add'), ENT_QUOTES, 'UTF-8') ?>">
    <?= $icon('add') ?>
    <span><?= htmlspecialchars($t('admin.add_user'), ENT_QUOTES, 'UTF-8') ?></span>
</a>
<?php elseif ($headerAction === 'content_form'): ?>
<div class="admin-header-action-menu" data-content-action-menu>
    <div class="admin-header-action-split">
        <button class="btn btn-primary admin-header-action-main" type="button" data-content-action-primary>
            <span data-content-action-label><?= htmlspecialchars($t('content.statuses.draft'), ENT_QUOTES, 'UTF-8') ?></span>
        </button>
        <button class="btn btn-primary btn-icon admin-header-action-toggle" type="button" data-content-action-toggle aria-expanded="false" aria-label="<?= htmlspecialchars($t('common.actions'), ENT_QUOTES, 'UTF-8') ?>">
            <?= $icon('next', 'icon content-action-summary-arrow') ?>
        </button>
    </div>
    <div class="admin-header-action-options" hidden>
        <div class="admin-header-action-group">
            <button class="btn btn-light admin-header-action-option" type="button" data-content-action-submit="published">
                <span><?= htmlspecialchars($t('content.publish'), ENT_QUOTES, 'UTF-8') ?></span>
                <span data-content-action-check="published"><?= $icon('success') ?></span>
            </button>
            <button class="btn btn-light admin-header-action-option" type="button" data-content-action-submit="draft">
                <span><?= htmlspecialchars($t('content.statuses.draft'), ENT_QUOTES, 'UTF-8') ?></span>
                <span data-content-action-check="draft"><?= $icon('success') ?></span>
            </button>
        </div>
        <?php if ($contentCanDelete): ?>
        <div class="admin-header-action-group admin-header-action-group-danger">
            <button class="btn btn-danger admin-header-action-option" type="button" data-content-action-delete>
                <span><?= htmlspecialchars($t('common.delete'), ENT_QUOTES, 'UTF-8') ?></span>
                <?= $icon('delete') ?>
            </button>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php elseif ($headerAction === 'content_list'): ?>
<a class="btn btn-primary" href="<?= htmlspecialchars($url('admin/content/add'), ENT_QUOTES, 'UTF-8') ?>">
    <?= $icon('add') ?>
    <span><?= htmlspecialchars($t('admin.add_content'), ENT_QUOTES, 'UTF-8') ?></span>
</a>
<?php elseif ($headerAction === 'media_list'): ?>
<a class="btn btn-primary" href="<?= htmlspecialchars($url('admin/media/add'), ENT_QUOTES, 'UTF-8') ?>">
    <?= $icon('add') ?>
    <span><?= htmlspecialchars($t('admin.add_media'), ENT_QUOTES, 'UTF-8') ?></span>
</a>
<?php elseif ($headerAction === 'terms_list'): ?>
<a class="btn btn-primary" href="<?= htmlspecialchars($url('admin/terms/add'), ENT_QUOTES, 'UTF-8') ?>">
    <?= $icon('add') ?>
    <span><?= htmlspecialchars($t('admin.add_term'), ENT_QUOTES, 'UTF-8') ?></span>
</a>
<?php endif; ?>
