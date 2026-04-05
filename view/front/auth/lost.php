<div class="container py-5">
    <div class="row">
        <div class="col-12 col-md-6">
            <div class="card p-5">
                <h1 class="m-0 mb-4"><?= htmlspecialchars($t('front.lost.title', 'Lost access'), ENT_QUOTES, 'UTF-8') ?></h1>
                <?php if (($message ?? '') !== ''): ?>
                <p class="mb-3"><?= htmlspecialchars((string)$message, ENT_QUOTES, 'UTF-8') ?></p>
                <?php endif; ?>
                <form method="post" action="<?= htmlspecialchars($url('lost'), ENT_QUOTES, 'UTF-8') ?>">
                    <?= $csrfField() ?>
                    <div class="mb-3">
                        <label><?= htmlspecialchars($t('common.email', 'Email'), ENT_QUOTES, 'UTF-8') ?></label>
                        <input type="email" name="email" value="<?= htmlspecialchars((string)($old['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>
                    <div class="mb-4">
                        <label><?= htmlspecialchars($t('front.lost.mode', 'Action'), ENT_QUOTES, 'UTF-8') ?></label>
                        <select name="mode">
                            <option value="password" <?= (($old['mode'] ?? 'password') === 'password') ? 'selected' : '' ?>><?= htmlspecialchars($t('front.lost.password', 'Send new password'), ENT_QUOTES, 'UTF-8') ?></option>
                            <option value="activation" <?= (($old['mode'] ?? 'password') === 'activation') ? 'selected' : '' ?>><?= htmlspecialchars($t('front.lost.activation', 'Send activation token'), ENT_QUOTES, 'UTF-8') ?></option>
                        </select>
                    </div>
                    <button class="btn btn-primary" type="submit"><?= htmlspecialchars($t('front.lost.submit', 'Send'), ENT_QUOTES, 'UTF-8') ?></button>
                </form>
                <p class="mt-4 mb-0"><a href="<?= htmlspecialchars($url('login'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($t('front.login.title', 'Login'), ENT_QUOTES, 'UTF-8') ?></a></p>
            </div>
        </div>
    </div>
</div>
