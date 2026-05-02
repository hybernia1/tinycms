<?php
if (!defined('BASE_DIR')) {
    exit;
}
$isAdmin = (string)($user['role'] ?? '') === 'admin'; ?>
<div class="card p-4">
    <div class="d-flex align-center gap-3 mb-4">
        <?= get_avatar($user, 'user-form-avatar', 96) ?>
        <div>
            <div class="text-muted small"><?= esc_html(t('users.avatar')) ?></div>
            <strong><?= esc_html((string)($user['name'] ?? t('users.user'))) ?></strong>
        </div>
    </div>
    <form
        id="users-form"
        method="post"
        autocomplete="off"
        action="<?= esc_url($mode === 'add' ? $url('admin/api/v1/users/add') : $url('admin/api/v1/users/' . (int)($user['ID'] ?? 0) . '/edit')) ?>"
        data-api-submit
        <?= $mode === 'edit' ? 'data-stay-on-page' : '' ?>
    >
        <?= $csrfField() ?>
        <div class="mb-3">
            <label><?= esc_html(t('common.name')) ?></label>
            <input type="text" name="name" autocomplete="off" value="<?= esc_attr((string)($user['name'] ?? '')) ?>" required>
            <?php if (!empty($errors['name'])): ?><small class="text-danger"><?= esc_html((string)$errors['name']) ?></small><?php endif; ?>
        </div>
        <div class="mb-3">
            <label><?= esc_html(t('common.email')) ?></label>
            <div class="field-with-icon">
                <span class="field-overlay field-overlay-start field-icon" aria-hidden="true"><?= icon('email') ?></span>
                <input class="field-control-with-start-icon" type="email" name="email" autocomplete="off" value="<?= esc_attr((string)($user['email'] ?? '')) ?>" required>
            </div>
            <?php if (!empty($errors['email'])): ?><small class="text-danger"><?= esc_html((string)$errors['email']) ?></small><?php endif; ?>
        </div>
        <div class="mb-3">
            <label><?= esc_html(t('common.password')) ?> <?= $mode === 'edit' ? '(' . esc_html(t('users.password_optional')) . ')' : '' ?></label>
            <div class="field-with-icon">
                <input class="field-control-with-end-icon" type="password" name="password" autocomplete="new-password" data-password-input <?= $mode === 'add' ? 'required' : '' ?>>
                <button class="field-overlay field-overlay-end field-icon-button" type="button" data-password-toggle aria-label="<?= esc_attr(t('auth.show_password')) ?>" title="<?= esc_attr(t('auth.show_password')) ?>">
                    <?= icon('show') ?>
                </button>
            </div>
            <?php if (!empty($errors['password'])): ?><small class="text-danger"><?= esc_html((string)$errors['password']) ?></small><?php endif; ?>
        </div>
        <?php if ($mode === 'edit' && $isAdmin && (int)($user['is_last_admin'] ?? 0) === 1): ?>
            <input type="hidden" name="role" value="admin">
        <?php else: ?>
            <div class="mb-3">
                <label><?= esc_html(t('users.role')) ?></label>
                <select name="role">
                    <option value="user" <?= (($user['role'] ?? 'user') === 'user') ? 'selected' : '' ?>><?= esc_html(t('users.roles.user')) ?></option>
                    <option value="admin" <?= (($user['role'] ?? '') === 'admin') ? 'selected' : '' ?>><?= esc_html(t('users.roles.admin')) ?></option>
                </select>
                <?php if (!empty($errors['role'])): ?><small class="text-danger"><?= esc_html((string)$errors['role']) ?></small><?php endif; ?>
            </div>
        <?php endif; ?>
        <?php if (!$isAdmin): ?>
            <div class="mb-4">
                <label><input type="checkbox" name="suspend" value="1" <?= ((int)($user['suspend'] ?? 0) === 1) ? 'checked' : '' ?>> <?= esc_html(t('users.suspend')) ?></label>
            </div>
        <?php endif; ?>
    </form>
</div>
