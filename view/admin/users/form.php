<?php $isAdmin = (string)($user['role'] ?? '') === 'admin'; ?>
<div class="card p-4">
    <form
        id="users-form"
        method="post"
        autocomplete="off"
        action="<?= htmlspecialchars($mode === 'add' ? $url('admin/api/v1/users/add') : $url('admin/api/v1/users/' . (int)($user['ID'] ?? 0) . '/edit'), ENT_QUOTES, 'UTF-8') ?>"
        data-api-submit
        <?= $mode === 'add' ? 'data-redirect-url="' . htmlspecialchars($url('admin/users'), ENT_QUOTES, 'UTF-8') . '"' : 'data-stay-on-page' ?>
    >
        <?= $csrfField() ?>
        <div class="mb-3">
            <label><?= htmlspecialchars($t('common.name'), ENT_QUOTES, 'UTF-8') ?></label>
            <input type="text" name="name" autocomplete="off" value="<?= htmlspecialchars((string)($user['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
            <?php if (!empty($errors['name'])): ?><small class="text-danger"><?= htmlspecialchars((string)$errors['name'], ENT_QUOTES, 'UTF-8') ?></small><?php endif; ?>
        </div>
        <div class="mb-3">
            <label><?= htmlspecialchars($t('common.email'), ENT_QUOTES, 'UTF-8') ?></label>
            <div class="field-with-icon">
                <span class="field-overlay field-overlay-start field-icon" aria-hidden="true"><?= $icon('email') ?></span>
                <input class="field-control-with-start-icon" type="email" name="email" autocomplete="off" value="<?= htmlspecialchars((string)($user['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
            </div>
            <?php if (!empty($errors['email'])): ?><small class="text-danger"><?= htmlspecialchars((string)$errors['email'], ENT_QUOTES, 'UTF-8') ?></small><?php endif; ?>
        </div>
        <div class="mb-3">
            <label><?= htmlspecialchars($t('common.password'), ENT_QUOTES, 'UTF-8') ?> <?= $mode === 'edit' ? '(' . htmlspecialchars($t('users.password_optional'), ENT_QUOTES, 'UTF-8') . ')' : '' ?></label>
            <div class="field-with-icon">
                <input class="field-control-with-end-icon" type="password" name="password" autocomplete="new-password" data-password-input <?= $mode === 'add' ? 'required' : '' ?>>
                <button class="field-overlay field-overlay-end field-icon-button" type="button" data-password-toggle aria-label="<?= htmlspecialchars($t('front.login.show_password'), ENT_QUOTES, 'UTF-8') ?>" title="<?= htmlspecialchars($t('front.login.show_password'), ENT_QUOTES, 'UTF-8') ?>">
                    <?= $icon('show') ?>
                </button>
            </div>
            <?php if (!empty($errors['password'])): ?><small class="text-danger"><?= htmlspecialchars((string)$errors['password'], ENT_QUOTES, 'UTF-8') ?></small><?php endif; ?>
        </div>
        <div class="mb-3">
            <label><?= htmlspecialchars($t('users.role'), ENT_QUOTES, 'UTF-8') ?></label>
            <select name="role">
                <option value="user" <?= (($user['role'] ?? 'user') === 'user') ? 'selected' : '' ?>><?= htmlspecialchars($t('users.roles.user'), ENT_QUOTES, 'UTF-8') ?></option>
                <option value="admin" <?= (($user['role'] ?? '') === 'admin') ? 'selected' : '' ?>><?= htmlspecialchars($t('users.roles.admin'), ENT_QUOTES, 'UTF-8') ?></option>
            </select>
            <?php if (!empty($errors['role'])): ?><small class="text-danger"><?= htmlspecialchars((string)$errors['role'], ENT_QUOTES, 'UTF-8') ?></small><?php endif; ?>
        </div>
        <div class="mb-4">
            <?php if ($isAdmin): ?>
                <p class="text-muted m-0"><?= htmlspecialchars($t('users.admin_cannot_suspend'), ENT_QUOTES, 'UTF-8') ?></p>
            <?php else: ?>
                <label><input type="checkbox" name="suspend" value="1" <?= ((int)($user['suspend'] ?? 0) === 1) ? 'checked' : '' ?>> <?= htmlspecialchars($t('users.suspend'), ENT_QUOTES, 'UTF-8') ?></label>
            <?php endif; ?>
        </div>
    </form>
</div>
