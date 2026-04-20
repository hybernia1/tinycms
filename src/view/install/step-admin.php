<div class="container py-5">
    <div class="row justify-center">
        <div class="col-12 col-md-6">
            <div class="card p-5">
                <div class="auth-logo mb-4">
                    <img src="<?= $e($url(ASSETS_DIR . 'svg/logo.svg')) ?>" alt="TinyCMS">
                </div>
                <h1 class="m-0 mb-2"><?= $e($t('install.title')) ?></h1>
                <p class="text-muted mt-2 mb-4"><?= $e(sprintf($t('install.step'), 3, $t('install.admin'))) ?></p>
                <?php if ($message !== ''): ?>
                <p class="mb-3 text-danger"><?= $e($message) ?></p>
                <?php endif; ?>
                <form method="post" action="<?= $e($url('install/admin')) ?>">
                    <?= $csrfField() ?>
                    <div class="mb-3">
                        <label><?= $e($t('install.name')) ?></label>
                        <input type="text" name="name" value="<?= $e((string)($old['name'] ?? '')) ?>" required>
                        <?php if (!empty($errors['name'])): ?>
                        <small class="text-danger"><?= $e((string)$errors['name']) ?></small>
                        <?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <label><?= $e($t('install.email')) ?></label>
                        <input type="email" name="email" value="<?= $e((string)($old['email'] ?? '')) ?>" required>
                        <?php if (!empty($errors['email'])): ?>
                        <small class="text-danger"><?= $e((string)$errors['email']) ?></small>
                        <?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <label><?= $e($t('install.website_url')) ?></label>
                        <input type="url" name="website_url" value="<?= $e((string)($old['website_url'] ?? '')) ?>" required>
                        <?php if (!empty($errors['website_url'])): ?>
                        <small class="text-danger"><?= $e((string)$errors['website_url']) ?></small>
                        <?php endif; ?>
                    </div>
                    <div class="mb-4">
                        <label><?= $e($t('install.password')) ?></label>
                        <input type="password" name="password" required>
                        <?php if (!empty($errors['password'])): ?>
                        <small class="text-danger"><?= $e((string)$errors['password']) ?></small>
                        <?php endif; ?>
                    </div>
                    <button class="btn btn-primary" type="submit"><?= $e($t('install.complete')) ?></button>
                </form>
            </div>
        </div>
    </div>
</div>
