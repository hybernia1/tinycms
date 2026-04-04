<div class="container py-5">
    <div class="row">
        <div class="col-12 col-md-6">
            <div class="card p-5">
                <h1 class="m-0 mb-2">Instalace</h1>
                <p class="text-muted mt-2 mb-4">Krok 2/3: Admin účet</p>
                <?php if ($message !== ''): ?>
                <p class="mb-3 text-danger"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p>
                <?php endif; ?>
                <form method="post" action="<?= htmlspecialchars($url('install/admin'), ENT_QUOTES, 'UTF-8') ?>">
                    <?= $csrfField() ?>
                    <div class="mb-3">
                        <label>Jméno</label>
                        <input type="text" name="name" value="<?= htmlspecialchars((string)($old['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
                        <?php if (!empty($errors['name'])): ?>
                        <small class="text-danger"><?= htmlspecialchars((string)$errors['name'], ENT_QUOTES, 'UTF-8') ?></small>
                        <?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <label>E-mail</label>
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
                    <button class="btn btn-primary" type="submit">Dokončit instalaci</button>
                </form>
            </div>
        </div>
    </div>
</div>
