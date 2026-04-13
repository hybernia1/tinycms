<div class="container py-5" data-install-content>
    <div class="row">
        <div class="col-12 col-md-6">
            <div class="card p-5">
                <h1 class="m-0 mb-2"><?= htmlspecialchars($t('install.title'), ENT_QUOTES, 'UTF-8') ?></h1>
                <p class="text-muted mt-2 mb-4"><?= htmlspecialchars(sprintf($t('install.step'), 1, $t('install.language')), ENT_QUOTES, 'UTF-8') ?></p>
                <?php if ($message !== ''): ?>
                <p class="mb-3 text-danger"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p>
                <?php endif; ?>
                <form method="post" action="<?= htmlspecialchars($url('install'), ENT_QUOTES, 'UTF-8') ?>">
                    <?= $csrfField() ?>
                    <div class="mb-4">
                        <label><?= htmlspecialchars($t('install.choose_language'), ENT_QUOTES, 'UTF-8') ?></label>
                        <select name="lang" required>
                            <?php foreach ($locales as $locale): ?>
                            <option value="<?= htmlspecialchars((string)$locale, ENT_QUOTES, 'UTF-8') ?>"<?= $selectedLang === (string)$locale ? ' selected' : '' ?>>
                                <?= htmlspecialchars((string)($localeLabels[$locale] ?? strtoupper((string)$locale)), ENT_QUOTES, 'UTF-8') ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button class="btn btn-primary" type="submit"><?= htmlspecialchars($t('install.language_submit'), ENT_QUOTES, 'UTF-8') ?></button>
                </form>
            </div>
        </div>
    </div>
</div>
