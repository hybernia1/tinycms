<div class="container py-5">
    <div class="row">
        <div class="col-12 col-md-6">
            <div class="card p-5">
                <?php if (trim((string)($siteLogo ?? '')) !== ''): ?>
                <p class="mb-3"><img src="<?= htmlspecialchars($url((string)$siteLogo), ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars((string)($siteName ?? 'TinyCMS'), ENT_QUOTES, 'UTF-8') ?>" style="max-height:64px"></p>
                <?php endif; ?>
                <h1 class="m-0 mb-4"><?= htmlspecialchars($t('front.login.title', 'Login'), ENT_QUOTES, 'UTF-8') ?></h1>
                <?php if ($message !== ''): ?>
                <p class="mb-3 text-danger"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p>
                <?php endif; ?>
                <form method="post" action="<?= htmlspecialchars($url('login'), ENT_QUOTES, 'UTF-8') ?>">
                    <?= $csrfField() ?>
                    <div class="mb-3">
                        <label><?= htmlspecialchars($t('common.email', 'Email'), ENT_QUOTES, 'UTF-8') ?></label>
                        <div class="input-with-icon">
                            <span class="input-with-icon-symbol" aria-hidden="true"><?= $icon('email') ?></span>
                            <input class="input-with-icon-field" type="email" name="email" value="<?= htmlspecialchars((string)($old['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
                        </div>
                        <?php if (!empty($errors['email'])): ?>
                        <small class="text-danger"><?= htmlspecialchars((string)$errors['email'], ENT_QUOTES, 'UTF-8') ?></small>
                        <?php endif; ?>
                    </div>
                    <div class="mb-4">
                        <label><?= htmlspecialchars($t('common.password', 'Password'), ENT_QUOTES, 'UTF-8') ?></label>
                        <div class="input-with-icon">
                            <input class="input-with-icon-toggle" type="password" name="password" data-password-input required>
                            <button class="input-with-icon-action" type="button" data-password-toggle aria-label="<?= htmlspecialchars($t('front.login.show_password', 'Show password'), ENT_QUOTES, 'UTF-8') ?>" title="<?= htmlspecialchars($t('front.login.show_password', 'Show password'), ENT_QUOTES, 'UTF-8') ?>">
                                <?= $icon('show') ?>
                            </button>
                        </div>
                        <?php if (!empty($errors['password'])): ?>
                        <small class="text-danger"><?= htmlspecialchars((string)$errors['password'], ENT_QUOTES, 'UTF-8') ?></small>
                        <?php endif; ?>
                    </div>
                    <div class="mb-4">
                        <label><input type="checkbox" name="remember" value="1" <?= ((int)($old['remember'] ?? 0) === 1) ? 'checked' : '' ?>> <?= htmlspecialchars($t('front.login.remember', 'Remember me'), ENT_QUOTES, 'UTF-8') ?></label>
                    </div>
                    <button class="btn btn-primary" type="submit"><?= htmlspecialchars($t('front.login.submit', 'Sign in'), ENT_QUOTES, 'UTF-8') ?></button>
                </form>
                <p class="mt-4 mb-2"><a href="<?= htmlspecialchars($url('lost'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($t('front.lost.title', 'Lost access'), ENT_QUOTES, 'UTF-8') ?></a></p>
                <?php if ((bool)($allowRegistration ?? true)): ?>
                <p class="m-0"><a href="<?= htmlspecialchars($url('register'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($t('front.register.title', 'Register'), ENT_QUOTES, 'UTF-8') ?></a></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
