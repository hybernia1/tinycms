<div class="container py-5">
    <div class="row justify-center">
        <div class="col-12 col-md-6">
            <div class="card p-5">
                <div class="auth-logo mb-4">
                    <img src="<?= $escUrl($url(ASSETS_DIR . 'svg/logo.svg')) ?>" alt="TinyCMS">
                </div>
                <h1 class="m-0 mb-4"><?= $escHtml($t('auth.lost_password')) ?></h1>
                <p class="mb-3" data-api-form-message hidden></p>
                <?php if (($tokenValid ?? false) === true): ?>
                    <form method="post" action="<?= $escUrl($url('admin/api/v1/auth/lost/reset')) ?>" data-api-submit>
                        <?= $csrfField() ?>
                        <input type="hidden" name="token" value="<?= $escHtml((string)($token ?? '')) ?>">
                        <div class="mb-3">
                            <label><?= $escHtml($t('auth.new_password')) ?></label>
                            <div class="field-with-icon">
                                <input class="field-control-with-end-icon" type="password" name="password" data-password-input required>
                                <button class="field-overlay field-overlay-end field-icon-button" type="button" data-password-toggle aria-label="<?= $escHtml($t('auth.show_password')) ?>" title="<?= $escHtml($t('auth.show_password')) ?>">
                                    <?= $icon('show') ?>
                                </button>
                            </div>
                        </div>
                        <button class="btn btn-primary" type="submit"><?= $escHtml($t('auth.reset_password')) ?></button>
                    </form>
                <?php else: ?>
                    <?php if (trim((string)($token ?? '')) !== ''): ?>
                        <p class="mb-3 text-danger"><?= $escHtml($t('auth.reset_token_invalid')) ?></p>
                    <?php endif; ?>
                    <form method="post" action="<?= $escUrl($url('admin/api/v1/auth/lost')) ?>" data-api-submit data-stay-on-page>
                        <?= $csrfField() ?>
                        <div class="mb-3">
                            <label><?= $escHtml($t('common.email')) ?></label>
                            <div class="field-with-icon">
                                <span class="field-overlay field-overlay-start field-icon" aria-hidden="true"><?= $icon('email') ?></span>
                                <input class="field-control-with-start-icon" type="email" name="email" value="<?= $escHtml((string)($old['email'] ?? '')) ?>" required>
                            </div>
                        </div>
                        <button class="btn btn-primary" type="submit"><?= $escHtml($t('auth.send_reset_link')) ?></button>
                    </form>
                <?php endif; ?>
                <p class="mt-3 mb-0">
                    <a href="<?= $escUrl($url('auth/login')) ?>"><?= $escHtml($t('auth.back_to_login')) ?></a>
                </p>
            </div>
        </div>
    </div>
</div>
