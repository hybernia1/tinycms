<div class="container py-5" data-install-content>
    <div class="row justify-center">
        <div class="col-12 col-md-6">
            <div class="card p-5">
                <div class="auth-logo mb-4">
                    <img src="<?= $escUrl($url(ASSETS_DIR . 'svg/logo.svg')) ?>" alt="TinyCMS">
                </div>
                <h1 class="m-0 mb-2"><?= $escHtml($t('install.title')) ?></h1>
                <p class="text-muted mt-2 mb-4"><?= $escHtml(sprintf($t('install.step'), 1, $t('install.language'))) ?></p>
                <?php if ($message !== ''): ?>
                <p class="mb-3 text-danger"><?= $escHtml($message) ?></p>
                <?php endif; ?>
                <form method="post" action="<?= $escUrl($url('install')) ?>">
                    <?= $csrfField() ?>
                    <div class="mb-3">
                        <label><?= $escHtml($t('install.choose_language')) ?></label>
                        <select name="lang" required>
                            <?php foreach ($locales as $locale): ?>
                            <option value="<?= $escHtml((string)$locale) ?>"<?= $selectedLang === (string)$locale ? ' selected' : '' ?>>
                                <?= $escHtml((string)($localeLabels[$locale] ?? strtoupper((string)$locale))) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button class="btn btn-primary" type="submit"><?= $escHtml($t('install.language_submit')) ?></button>
                </form>
            </div>
        </div>
    </div>
</div>
