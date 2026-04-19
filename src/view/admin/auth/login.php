<div class="container py-5">
    <div class="row justify-center">
        <div class="col-12 col-md-6">
            <div class="card p-5">
                <div class="auth-logo mb-4">
                    <img src="<?= $e($url(ASSETS_DIR . 'svg/logo.svg')) ?>" alt="TinyCMS">
                </div>
                <h1 class="m-0 mb-4"><?= $e($t('auth.login')) ?></h1>
                <p class="mb-3 text-danger" data-api-form-message hidden></p>
                <form method="post" action="<?= $e($url('admin/api/v1/auth/login')) ?>" data-api-submit>
                    <?= $csrfField() ?>
                    <div class="mb-3">
                        <label><?= $e($t('common.email')) ?></label>
                        <div class="field-with-icon">
                            <span class="field-overlay field-overlay-start field-icon" aria-hidden="true"><?= $icon('email') ?></span>
                            <input class="field-control-with-start-icon" type="email" name="email" value="<?= $e((string)($old['email'] ?? '')) ?>" required>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label><?= $e($t('common.password')) ?></label>
                        <div class="field-with-icon">
                            <input class="field-control-with-end-icon" type="password" name="password" data-password-input required>
                            <button class="field-overlay field-overlay-end field-icon-button" type="button" data-password-toggle aria-label="<?= $e($t('auth.show_password')) ?>" title="<?= $e($t('auth.show_password')) ?>">
                                <?= $icon('show') ?>
                            </button>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label><input type="checkbox" name="remember" value="1" <?= ((int)($old['remember'] ?? 0) === 1) ? 'checked' : '' ?>> <?= $e($t('auth.remember')) ?></label>
                    </div>
                    <button class="btn btn-primary" type="submit"><?= $e($t('auth.login')) ?></button>
                </form>
                <?php if (($allowRegistration ?? false) === true): ?>
                    <p class="mt-3 mb-0">
                        <a href="<?= $e($url('auth/register')) ?>"><?= $e($t('auth.create_account')) ?></a>
                    </p>
                <?php endif; ?>
                <p class="mt-2 mb-0">
                    <a href="<?= $e($url('auth/lost')) ?>"><?= $e($t('auth.lost_password')) ?></a>
                </p>
            </div>
        </div>
    </div>
</div>
