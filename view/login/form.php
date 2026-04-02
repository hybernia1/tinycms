<div class="container py-5">
    <div class="row">
        <div class="col-6">
            <div class="card p-5">
                <h1 class="m-0 mb-4">Login</h1>
                <?php if ($message !== ''): ?>
                <p class="mb-3 text-danger"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p>
                <?php endif; ?>
                <form method="post" action="<?= htmlspecialchars($url('login'), ENT_QUOTES, 'UTF-8') ?>">
                    <?= $csrfField() ?>
                    <div class="mb-3">
                        <label>Email</label>
                        <input type="email" name="email" value="<?= htmlspecialchars((string)($old['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
                        <?php if (!empty($errors['email'])): ?>
                        <small class="text-danger"><?= htmlspecialchars((string)$errors['email'], ENT_QUOTES, 'UTF-8') ?></small>
                        <?php endif; ?>
                    </div>
                    <div class="mb-4">
                        <label>Heslo</label>
                        <input type="password" name="password" required>
                        <?php if (!empty($errors['password'])): ?>
                        <small class="text-danger"><?= htmlspecialchars((string)$errors['password'], ENT_QUOTES, 'UTF-8') ?></small>
                        <?php endif; ?>
                    </div>
                    <div class="mb-4">
                        <label><input type="checkbox" name="remember" value="1" <?= ((int)($old['remember'] ?? 0) === 1) ? 'checked' : '' ?>> Zapamatovat si mě</label>
                    </div>
                    <button class="btn btn-primary" type="submit">Přihlásit</button>
                </form>
            </div>
        </div>
    </div>
</div>
