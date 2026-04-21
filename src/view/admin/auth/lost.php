<div class="container py-5">
    <div class="row justify-center">
        <div class="col-12 col-md-6">
            <div class="card p-5">
                <div class="auth-logo mb-4">
                    <img src="<?= $e($url(ASSETS_DIR . 'svg/logo.svg')) ?>" alt="TinyCMS">
                </div>
                <h1 class="m-0 mb-4"><?= $e($t('auth.lost_password')) ?></h1>
                <p class="mb-3" data-api-form-message hidden></p>
                <?php if (($tokenValid ?? false) === true): ?>
                    <form method="post" action="<?= $e($url('admin/api/v1/auth/lost/reset')) ?>" data-api-submit>
                        <?= $csrfField() ?>
                        <input type="hidden" name="token" value="<?= $e((string)($token ?? '')) ?>">
                        <div class="mb-3">
                            <label><?= $e($t('auth.new_password')) ?></label>
                            <div class="field-with-icon">
                                <input class="field-control-with-end-icon" type="password" name="password" data-password-input required>
                                <button class="field-overlay field-overlay-end field-icon-button" type="button" data-password-toggle aria-label="<?= $e($t('auth.show_password')) ?>" title="<?= $e($t('auth.show_password')) ?>">
                                    <?= $icon('show') ?>
                                </button>
                            </div>
                        </div>
                        <button class="btn btn-primary" type="submit"><?= $e($t('auth.reset_password')) ?></button>
                    </form>
                <?php else: ?>
                    <?php if (trim((string)($token ?? '')) !== ''): ?>
                        <p class="mb-3 text-danger"><?= $e($t('auth.reset_token_invalid')) ?></p>
                    <?php endif; ?>
                    <form method="post" action="<?= $e($url('admin/api/v1/auth/lost')) ?>" data-api-submit data-stay-on-page>
                        <?= $csrfField() ?>
                        <div class="mb-3">
                            <label><?= $e($t('common.email')) ?></label>
                            <div class="field-with-icon">
                                <span class="field-overlay field-overlay-start field-icon" aria-hidden="true"><?= $icon('email') ?></span>
                                <input class="field-control-with-start-icon" type="email" name="email" value="<?= $e((string)($old['email'] ?? '')) ?>" required>
                            </div>
                        </div>
                        <button class="btn btn-primary" type="submit"><?= $e($t('auth.send_reset_link')) ?></button>
                    </form>
                <?php endif; ?>
                <p class="mt-3 mb-0">
                    <a href="<?= $e($url('auth/login')) ?>"><?= $e($t('auth.back_to_login')) ?></a>
                </p>
            </div>
        </div>
    </div>
</div>
