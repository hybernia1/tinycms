<div class="container py-5">
    <div class="row justify-center">
        <div class="col-12 col-md-6">
            <div class="card p-5">
                <div class="auth-logo mb-4">
                    <img src="<?= htmlspecialchars($url('assets/svg/logo.svg'), ENT_QUOTES, 'UTF-8') ?>" alt="TinyCMS">
                </div>
                <h1 class="m-0 mb-4"><?= htmlspecialchars($t('auth.login'), ENT_QUOTES, 'UTF-8') ?></h1>
                <?php if ($message !== ''): ?>
                <p class="mb-3 text-danger"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p>
                <?php endif; ?>
                <form method="post" action="<?= htmlspecialchars($url('admin/login'), ENT_QUOTES, 'UTF-8') ?>">
                    <?= $csrfField() ?>
                    <div class="mb-3">
                        <label><?= htmlspecialchars($t('common.email'), ENT_QUOTES, 'UTF-8') ?></label>
                        <div class="field-with-icon">
                            <span class="field-overlay field-overlay-start field-icon" aria-hidden="true"><?= $icon('email') ?></span>
                            <input class="field-control-with-start-icon" type="email" name="email" value="<?= htmlspecialchars((string)($old['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
                        </div>
                        <?php if (!empty($errors['email'])): ?>
                        <small class="text-danger"><?= htmlspecialchars((string)$errors['email'], ENT_QUOTES, 'UTF-8') ?></small>
                        <?php endif; ?>
                    </div>
                    <div class="mb-4">
                        <label><?= htmlspecialchars($t('common.password'), ENT_QUOTES, 'UTF-8') ?></label>
                        <div class="field-with-icon">
                            <input class="field-control-with-end-icon" type="password" name="password" data-password-input required>
                            <button class="field-overlay field-overlay-end field-icon-button" type="button" data-password-toggle aria-label="<?= htmlspecialchars($t('auth.show_password'), ENT_QUOTES, 'UTF-8') ?>" title="<?= htmlspecialchars($t('auth.show_password'), ENT_QUOTES, 'UTF-8') ?>">
                                <?= $icon('show') ?>
                            </button>
                        </div>
                        <?php if (!empty($errors['password'])): ?>
                        <small class="text-danger"><?= htmlspecialchars((string)$errors['password'], ENT_QUOTES, 'UTF-8') ?></small>
                        <?php endif; ?>
                    </div>
                    <div class="mb-4">
                        <label><input type="checkbox" name="remember" value="1" <?= ((int)($old['remember'] ?? 0) === 1) ? 'checked' : '' ?>> <?= htmlspecialchars($t('auth.remember'), ENT_QUOTES, 'UTF-8') ?></label>
                    </div>
                    <button class="btn btn-primary" type="submit"><?= htmlspecialchars($t('auth.login'), ENT_QUOTES, 'UTF-8') ?></button>
                </form>
            </div>
        </div>
    </div>
</div>
