<div class="container py-5">
    <div class="row justify-center">
        <div class="col-12 col-md-6">
            <div class="card p-5">
                <div class="auth-logo mb-4">
                    <img src="<?= esc_url($url(ASSETS_DIR . 'svg/logo.svg')) ?>" alt="TinyCMS">
                </div>
                <h1 class="m-0 mb-2"><?= esc_html(t('install.title')) ?></h1>
                <p class="text-muted mt-2 mb-4"><?= esc_html(sprintf(t('install.step'), 2, t('install.database'))) ?></p>
                <?php if ($message !== ''): ?>
                <p class="mb-3 text-danger"><?= esc_html($message) ?></p>
                <?php endif; ?>
                <?php if (!empty($errors['db'])): ?>
                <p class="mb-3 text-danger"><?= esc_html((string)$errors['db']) ?></p>
                <?php endif; ?>
                <form method="post" action="<?= esc_url($url('install/db')) ?>">
                    <?= $csrfField() ?>
                    <div class="mb-3">
                        <label><?= esc_html(t('install.db_host')) ?></label>
                        <input type="text" name="db_host" value="<?= esc_attr((string)($old['db_host'] ?? '')) ?>" required>
                        <?php if (!empty($errors['db_host'])): ?>
                        <small class="text-danger"><?= esc_html((string)$errors['db_host']) ?></small>
                        <?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <label><?= esc_html(t('install.db_name')) ?></label>
                        <input type="text" name="db_name" value="<?= esc_attr((string)($old['db_name'] ?? '')) ?>" required>
                        <?php if (!empty($errors['db_name'])): ?>
                        <small class="text-danger"><?= esc_html((string)$errors['db_name']) ?></small>
                        <?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <label><?= esc_html(t('install.db_user')) ?></label>
                        <input type="text" name="db_user" value="<?= esc_attr((string)($old['db_user'] ?? '')) ?>" required>
                        <?php if (!empty($errors['db_user'])): ?>
                        <small class="text-danger"><?= esc_html((string)$errors['db_user']) ?></small>
                        <?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <label><?= esc_html(t('install.db_pass')) ?></label>
                        <input type="password" name="db_pass" value="<?= esc_attr((string)($old['db_pass'] ?? '')) ?>">
                    </div>
                    <div class="mb-3">
                        <label><?= esc_html(t('install.db_prefix')) ?></label>
                        <input type="text" name="db_prefix" value="<?= esc_attr((string)($old['db_prefix'] ?? 'tiny_')) ?>">
                        <?php if (!empty($errors['db_prefix'])): ?>
                        <small class="text-danger"><?= esc_html((string)$errors['db_prefix']) ?></small>
                        <?php endif; ?>
                    </div>
                    <button class="btn btn-primary" type="submit"><?= esc_html(t('common.next')) ?></button>
                </form>
            </div>
        </div>
    </div>
</div>
