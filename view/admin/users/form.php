<?php $isAdmin = (string)($user['role'] ?? '') === 'admin'; ?>
<div class="card p-5">
    <form method="post" action="<?= htmlspecialchars($mode === 'add' ? $url('admin/users/add') : $url('admin/users/edit?id=' . (int)($user['ID'] ?? 0)), ENT_QUOTES, 'UTF-8') ?>">
        <?= $csrfField() ?>
        <div class="mb-3">
            <label><?= htmlspecialchars($t('common.name', 'Name'), ENT_QUOTES, 'UTF-8') ?></label>
            <input type="text" name="name" value="<?= htmlspecialchars((string)($user['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
            <?php if (!empty($errors['name'])): ?><small class="text-danger"><?= htmlspecialchars((string)$errors['name'], ENT_QUOTES, 'UTF-8') ?></small><?php endif; ?>
        </div>
        <div class="mb-3">
            <label><?= htmlspecialchars($t('common.email', 'Email'), ENT_QUOTES, 'UTF-8') ?></label>
            <div class="input-with-icon">
                <span class="input-with-icon-symbol" aria-hidden="true"><?= $icon('email') ?></span>
                <input class="input-with-icon-field" type="email" name="email" value="<?= htmlspecialchars((string)($user['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
            </div>
            <?php if (!empty($errors['email'])): ?><small class="text-danger"><?= htmlspecialchars((string)$errors['email'], ENT_QUOTES, 'UTF-8') ?></small><?php endif; ?>
        </div>
        <div class="mb-3">
            <label><?= htmlspecialchars($t('common.password', 'Password'), ENT_QUOTES, 'UTF-8') ?> <?= $mode === 'edit' ? '(' . htmlspecialchars($t('users.password_optional', 'optional'), ENT_QUOTES, 'UTF-8') . ')' : '' ?></label>
            <div class="input-with-icon">
                <input class="input-with-icon-toggle" type="password" name="password" data-password-input <?= $mode === 'add' ? 'required' : '' ?>>
                <button class="input-with-icon-action" type="button" data-password-toggle aria-label="<?= htmlspecialchars($t('front.login.show_password', 'Show password'), ENT_QUOTES, 'UTF-8') ?>" title="<?= htmlspecialchars($t('front.login.show_password', 'Show password'), ENT_QUOTES, 'UTF-8') ?>">
                    <?= $icon('show') ?>
                </button>
            </div>
            <?php if (!empty($errors['password'])): ?><small class="text-danger"><?= htmlspecialchars((string)$errors['password'], ENT_QUOTES, 'UTF-8') ?></small><?php endif; ?>
        </div>
        <div class="mb-3">
            <label><?= htmlspecialchars($t('users.role', 'Role'), ENT_QUOTES, 'UTF-8') ?></label>
            <select name="role">
                <option value="editor" <?= (($user['role'] ?? 'editor') === 'editor') ? 'selected' : '' ?>><?= htmlspecialchars($t('users.roles.editor', 'Editor'), ENT_QUOTES, 'UTF-8') ?></option>
                <option value="admin" <?= (($user['role'] ?? '') === 'admin') ? 'selected' : '' ?>><?= htmlspecialchars($t('users.roles.admin', 'Administrator'), ENT_QUOTES, 'UTF-8') ?></option>
            </select>
            <?php if (!empty($errors['role'])): ?><small class="text-danger"><?= htmlspecialchars((string)$errors['role'], ENT_QUOTES, 'UTF-8') ?></small><?php endif; ?>
        </div>
        <div class="mb-4">
            <?php if ($isAdmin): ?>
                <p class="text-muted m-0"><?= htmlspecialchars($t('users.admin_cannot_suspend', 'Admin account cannot be suspended.'), ENT_QUOTES, 'UTF-8') ?></p>
            <?php else: ?>
                <label><input type="checkbox" name="suspend" value="1" <?= ((int)($user['suspend'] ?? 0) === 1) ? 'checked' : '' ?>> <?= htmlspecialchars($t('users.suspend', 'Suspend'), ENT_QUOTES, 'UTF-8') ?></label>
            <?php endif; ?>
        </div>
        <button class="btn btn-primary" type="submit"><?= htmlspecialchars($t('common.save', 'Save'), ENT_QUOTES, 'UTF-8') ?></button>
        <a class="btn btn-light" href="<?= htmlspecialchars($url('admin/users'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($t('common.back', 'Back'), ENT_QUOTES, 'UTF-8') ?></a>
    </form>
</div>
