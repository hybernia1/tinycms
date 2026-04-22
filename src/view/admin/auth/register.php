<div class="container py-5">
    <div class="row justify-center">
        <div class="col-12 col-md-6">
            <div class="card p-5">
                <div class="auth-logo mb-4">
                    <img src="<?= $escUrl($url(ASSETS_DIR . 'svg/logo.svg')) ?>" alt="TinyCMS">
                </div>
                <h1 class="m-0 mb-4"><?= $escHtml($t('auth.register')) ?></h1>
                <p class="mb-3 text-danger" data-api-form-message hidden></p>
                <form method="post" action="<?= $escUrl($url('admin/api/v1/auth/register')) ?>" data-api-submit>
                    <?= $csrfField() ?>
                    <div class="mb-3">
                        <label><?= $escHtml($t('common.name')) ?></label>
                        <input type="text" name="name" value="<?= $escHtml((string)($old['name'] ?? '')) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label><?= $escHtml($t('common.email')) ?></label>
                        <input type="email" name="email" value="<?= $escHtml((string)($old['email'] ?? '')) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label><?= $escHtml($t('common.password')) ?></label>
                        <div class="field-with-icon">
                            <input class="field-control-with-end-icon" type="password" name="password" data-password-input required>
                            <button class="field-overlay field-overlay-end field-icon-button" type="button" data-password-toggle aria-label="<?= $escHtml($t('auth.show_password')) ?>" title="<?= $escHtml($t('auth.show_password')) ?>">
                                <?= $icon('show') ?>
                            </button>
                        </div>
                    </div>
                    <button class="btn btn-primary" type="submit"><?= $escHtml($t('auth.register')) ?></button>
                </form>
                <p class="mt-3 mb-0">
                    <a href="<?= $escUrl($url('auth/login')) ?>"><?= $escHtml($t('auth.have_account')) ?></a>
                </p>
            </div>
        </div>
    </div>
</div>
