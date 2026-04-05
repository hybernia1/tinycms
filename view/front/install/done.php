<div class="container py-5">
    <div class="row">
        <div class="col-12 col-md-6">
            <div class="card p-5">
                <h1 class="m-0 mb-3"><?= htmlspecialchars($t('install.done'), ENT_QUOTES, 'UTF-8') ?></h1>
                <p class="mb-4"><?= htmlspecialchars(sprintf($t('install.step'), 4, $t('install.ready')), ENT_QUOTES, 'UTF-8') ?></p>
                <a class="btn btn-primary" href="<?= htmlspecialchars($url('admin/dashboard'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($t('install.go_admin'), ENT_QUOTES, 'UTF-8') ?></a>
            </div>
        </div>
    </div>
</div>
