<div class="container py-5">
    <div class="row justify-center">
        <div class="col-12 col-md-6">
            <div class="card p-5">
                <div class="auth-logo mb-4">
                    <img src="<?= esc_url($url(ASSETS_DIR . 'svg/logo.svg')) ?>" alt="TinyCMS">
                </div>
                <h1 class="m-0 mb-4"><?= esc_html(t('auth.register')) ?></h1>
                <p class="mb-3 text-danger" data-api-form-message hidden></p>
                <form method="post" action="<?= esc_url($url('admin/api/v1/auth/register')) ?>" data-api-submit>
                    <?= $csrfField() ?>
                    <div class="mb-3">
                        <label><?= esc_html(t('common.name')) ?></label>
                        <input type="text" name="name" value="<?= esc_attr((string)($old['name'] ?? '')) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label><?= esc_html(t('common.email')) ?></label>
                        <input type="email" name="email" value="<?= esc_attr((string)($old['email'] ?? '')) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label><?= esc_html(t('common.password')) ?></label>
                        <div class="field-with-icon">
                            <input class="field-control-with-end-icon" type="password" name="password" data-password-input required>
                            <button class="field-overlay field-overlay-end field-icon-button" type="button" data-password-toggle aria-label="<?= esc_attr(t('auth.show_password')) ?>" title="<?= esc_attr(t('auth.show_password')) ?>">
                                <?= icon('show') ?>
                            </button>
                        </div>
                    </div>
                    <button class="btn btn-primary" type="submit"><?= esc_html(t('auth.register')) ?></button>
                </form>
                <p class="mt-3 mb-0">
                    <a href="<?= esc_url($url('auth/login')) ?>"><?= esc_html(t('auth.have_account')) ?></a>
                </p>
            </div>
        </div>
    </div>
</div>
