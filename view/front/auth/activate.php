<div class="container py-5">
    <div class="row">
        <div class="col-12 col-md-6">
            <div class="card p-5">
                <h1 class="m-0 mb-4"><?= htmlspecialchars($t('front.activate.title', 'Account activation'), ENT_QUOTES, 'UTF-8') ?></h1>
                <p class="mb-3 <?= ($success ?? false) ? '' : 'text-danger' ?>"><?= htmlspecialchars((string)($message ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
                <?php if (!($success ?? false)): ?>
                <p class="mb-2"><a href="<?= htmlspecialchars($url('lost'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($t('front.lost.title', 'Lost access'), ENT_QUOTES, 'UTF-8') ?></a></p>
                <?php endif; ?>
                <p class="m-0"><a href="<?= htmlspecialchars($url('login'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($t('front.login.title', 'Login'), ENT_QUOTES, 'UTF-8') ?></a></p>
            </div>
        </div>
    </div>
</div>
