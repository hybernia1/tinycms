<div class="container py-5">
    <div class="row">
        <div class="col-12 col-md-6">
            <div class="card p-5">
                <?php if (trim((string)($siteLogo ?? '')) !== ''): ?>
                <p class="mb-3"><img src="<?= htmlspecialchars($url((string)$siteLogo), ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars((string)($siteName ?? 'TinyCMS'), ENT_QUOTES, 'UTF-8') ?>" style="max-height:64px"></p>
                <?php endif; ?>
                <h1 class="m-0 mb-4"><?= htmlspecialchars($t('front.register.title', 'Register'), ENT_QUOTES, 'UTF-8') ?></h1>
                <?php if (($message ?? '') !== ''): ?>
                <p class="mb-3 text-danger"><?= htmlspecialchars((string)$message, ENT_QUOTES, 'UTF-8') ?></p>
                <?php endif; ?>
                <form method="post" action="<?= htmlspecialchars($url('register'), ENT_QUOTES, 'UTF-8') ?>">
                    <?= $csrfField() ?>
                    <div class="mb-3">
                        <label><?= htmlspecialchars($t('common.name', 'Name'), ENT_QUOTES, 'UTF-8') ?></label>
                        <input type="text" name="name" value="<?= htmlspecialchars((string)($old['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
                        <?php if (!empty($errors['name'])): ?><small class="text-danger"><?= htmlspecialchars((string)$errors['name'], ENT_QUOTES, 'UTF-8') ?></small><?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <label><?= htmlspecialchars($t('common.email', 'Email'), ENT_QUOTES, 'UTF-8') ?></label>
                        <input type="email" name="email" value="<?= htmlspecialchars((string)($old['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
                        <?php if (!empty($errors['email'])): ?><small class="text-danger"><?= htmlspecialchars((string)$errors['email'], ENT_QUOTES, 'UTF-8') ?></small><?php endif; ?>
                    </div>
                    <div class="mb-4">
                        <label><?= htmlspecialchars($t('common.password', 'Password'), ENT_QUOTES, 'UTF-8') ?></label>
                        <input type="password" name="password" required>
                        <?php if (!empty($errors['password'])): ?><small class="text-danger"><?= htmlspecialchars((string)$errors['password'], ENT_QUOTES, 'UTF-8') ?></small><?php endif; ?>
                    </div>
                    <button class="btn btn-primary" type="submit"><?= htmlspecialchars($t('front.register.submit', 'Create account'), ENT_QUOTES, 'UTF-8') ?></button>
                </form>
                <p class="mt-4 mb-0"><a href="<?= htmlspecialchars($url('login'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($t('front.login.title', 'Login'), ENT_QUOTES, 'UTF-8') ?></a></p>
            </div>
        </div>
    </div>
</div>
