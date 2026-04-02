<div class="card p-5">
    <h1 class="m-0 mb-4"><?= $mode === 'add' ? 'Přidat uživatele' : 'Upravit uživatele' ?></h1>
    <?php if ($message !== ''): ?><p class="text-danger mb-3"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
    <form method="post" action="<?= htmlspecialchars($mode === 'add' ? $url('admin/users/add') : $url('admin/users/edit?id=' . (int)($user['ID'] ?? 0)), ENT_QUOTES, 'UTF-8') ?>">
        <div class="mb-3">
            <label>Jméno</label>
            <input type="text" name="name" value="<?= htmlspecialchars((string)($user['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
            <?php if (!empty($errors['name'])): ?><small class="text-danger"><?= htmlspecialchars((string)$errors['name'], ENT_QUOTES, 'UTF-8') ?></small><?php endif; ?>
        </div>
        <div class="mb-3">
            <label>Email</label>
            <input type="email" name="email" value="<?= htmlspecialchars((string)($user['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
            <?php if (!empty($errors['email'])): ?><small class="text-danger"><?= htmlspecialchars((string)$errors['email'], ENT_QUOTES, 'UTF-8') ?></small><?php endif; ?>
        </div>
        <div class="mb-3">
            <label>Heslo <?= $mode === 'edit' ? '(volitelné)' : '' ?></label>
            <input type="password" name="password" <?= $mode === 'add' ? 'required' : '' ?>>
            <?php if (!empty($errors['password'])): ?><small class="text-danger"><?= htmlspecialchars((string)$errors['password'], ENT_QUOTES, 'UTF-8') ?></small><?php endif; ?>
        </div>
        <div class="mb-3">
            <label>Role</label>
            <select name="role">
                <option value="user" <?= (($user['role'] ?? 'user') === 'user') ? 'selected' : '' ?>>user</option>
                <option value="admin" <?= (($user['role'] ?? '') === 'admin') ? 'selected' : '' ?>>admin</option>
            </select>
            <?php if (!empty($errors['role'])): ?><small class="text-danger"><?= htmlspecialchars((string)$errors['role'], ENT_QUOTES, 'UTF-8') ?></small><?php endif; ?>
        </div>
        <div class="mb-4">
            <label><input type="checkbox" name="suspend" value="1" <?= ((int)($user['suspend'] ?? 0) === 1) ? 'checked' : '' ?>> Suspend</label>
        </div>
        <button class="btn btn-primary" type="submit">Uložit</button>
        <a class="btn btn-light" href="<?= htmlspecialchars($url('admin/users'), ENT_QUOTES, 'UTF-8') ?>">Zpět</a>
    </form>
</div>
