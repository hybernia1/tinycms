<div class="container py-5">
    <div class="row justify-center">
        <div class="col-12 col-md-6">
            <div class="card p-5">
                <div class="auth-logo mb-4">
                    <img src="<?= esc_url($url(ASSETS_DIR . 'svg/logo.svg')) ?>" alt="TinyCMS">
                </div>
                <h1 class="m-0 mb-3"><?= esc_html(t('install.done')) ?></h1>
                <p class="mb-4"><?= esc_html(sprintf(t('install.step'), 4, t('install.ready'))) ?></p>
                <a class="btn btn-primary" href="<?= esc_url($url('admin/dashboard')) ?>"><?= esc_html(t('install.go_admin')) ?></a>
            </div>
        </div>
    </div>
</div>
