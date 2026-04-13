<?php $isAdmin = (string)($user['role'] ?? '') === 'admin'; ?>
<div class="card p-4">
    <form
        id="users-form"
        method="post"
        autocomplete="off"
        action="<?= $e($mode === 'add' ? $url('admin/api/v1/users/add') : $url('admin/api/v1/users/' . (int)($user['ID'] ?? 0) . '/edit')) ?>"
        data-api-submit
        <?= $mode === 'add' ? 'data-redirect-url="' . $e($url('admin/users')) . '"' : 'data-stay-on-page' ?>
    >
        <?= $csrfField() ?>
        <div class="mb-3">
            <label><?= $e($t('common.name')) ?></label>
            <input type="text" name="name" autocomplete="off" value="<?= $e((string)($user['name'] ?? '')) ?>" required>
            <?php if (!empty($errors['name'])): ?><small class="text-danger"><?= $e((string)$errors['name']) ?></small><?php endif; ?>
        </div>
        <div class="mb-3">
            <label><?= $e($t('common.email')) ?></label>
            <div class="field-with-icon">
                <span class="field-overlay field-overlay-start field-icon" aria-hidden="true"><?= $icon('email') ?></span>
                <input class="field-control-with-start-icon" type="email" name="email" autocomplete="off" value="<?= $e((string)($user['email'] ?? '')) ?>" required>
            </div>
            <?php if (!empty($errors['email'])): ?><small class="text-danger"><?= $e((string)$errors['email']) ?></small><?php endif; ?>
        </div>
        <div class="mb-3">
            <label><?= $e($t('common.password')) ?> <?= $mode === 'edit' ? '(' . $e($t('users.password_optional')) . ')' : '' ?></label>
            <div class="field-with-icon">
                <input class="field-control-with-end-icon" type="password" name="password" autocomplete="new-password" data-password-input <?= $mode === 'add' ? 'required' : '' ?>>
                <button class="field-overlay field-overlay-end field-icon-button" type="button" data-password-toggle aria-label="<?= $e($t('auth.show_password')) ?>" title="<?= $e($t('auth.show_password')) ?>">
                    <?= $icon('show') ?>
                </button>
            </div>
            <?php if (!empty($errors['password'])): ?><small class="text-danger"><?= $e((string)$errors['password']) ?></small><?php endif; ?>
        </div>
        <div class="mb-3">
            <label><?= $e($t('users.role')) ?></label>
            <select name="role">
                <option value="user" <?= (($user['role'] ?? 'user') === 'user') ? 'selected' : '' ?>><?= $e($t('users.roles.user')) ?></option>
                <option value="admin" <?= (($user['role'] ?? '') === 'admin') ? 'selected' : '' ?>><?= $e($t('users.roles.admin')) ?></option>
            </select>
            <?php if (!empty($errors['role'])): ?><small class="text-danger"><?= $e((string)$errors['role']) ?></small><?php endif; ?>
        </div>
        <div class="mb-4">
            <?php if ($isAdmin): ?>
                <p class="text-muted m-0"><?= $e($t('users.admin_cannot_suspend')) ?></p>
            <?php else: ?>
                <label><input type="checkbox" name="suspend" value="1" <?= ((int)($user['suspend'] ?? 0) === 1) ? 'checked' : '' ?>> <?= $e($t('users.suspend')) ?></label>
            <?php endif; ?>
        </div>
    </form>
</div>
