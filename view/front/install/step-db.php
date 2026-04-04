<div class="container py-5">
    <div class="row">
        <div class="col-12 col-md-6">
            <div class="card p-5">
                <h1 class="m-0 mb-2">Instalace</h1>
                <p class="text-muted mt-2 mb-4">Krok 1/3: Databáze</p>
                <?php if ($message !== ''): ?>
                <p class="mb-3 text-danger"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p>
                <?php endif; ?>
                <?php if (!empty($errors['db'])): ?>
                <p class="mb-3 text-danger"><?= htmlspecialchars((string)$errors['db'], ENT_QUOTES, 'UTF-8') ?></p>
                <?php endif; ?>
                <form method="post" action="<?= htmlspecialchars($url('install'), ENT_QUOTES, 'UTF-8') ?>">
                    <?= $csrfField() ?>
                    <div class="mb-3">
                        <label>DB Host</label>
                        <input type="text" name="db_host" value="<?= htmlspecialchars((string)($old['db_host'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
                        <?php if (!empty($errors['db_host'])): ?>
                        <small class="text-danger"><?= htmlspecialchars((string)$errors['db_host'], ENT_QUOTES, 'UTF-8') ?></small>
                        <?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <label>DB Name</label>
                        <input type="text" name="db_name" value="<?= htmlspecialchars((string)($old['db_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
                        <?php if (!empty($errors['db_name'])): ?>
                        <small class="text-danger"><?= htmlspecialchars((string)$errors['db_name'], ENT_QUOTES, 'UTF-8') ?></small>
                        <?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <label>DB User</label>
                        <input type="text" name="db_user" value="<?= htmlspecialchars((string)($old['db_user'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
                        <?php if (!empty($errors['db_user'])): ?>
                        <small class="text-danger"><?= htmlspecialchars((string)$errors['db_user'], ENT_QUOTES, 'UTF-8') ?></small>
                        <?php endif; ?>
                    </div>
                    <div class="mb-4">
                        <label>DB Pass</label>
                        <input type="password" name="db_pass" value="<?= htmlspecialchars((string)($old['db_pass'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <button class="btn btn-primary" type="submit">Další krok</button>
                </form>
            </div>
        </div>
    </div>
</div>
