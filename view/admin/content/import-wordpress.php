<form method="post" action="<?= htmlspecialchars($url('admin/content/import/wordpress'), ENT_QUOTES, 'UTF-8') ?>" class="card p-4" style="max-width:680px;">
    <?= $csrfField() ?>
    <div class="mb-3">
        <label><?= htmlspecialchars($t('content.wp_import_site_url', 'WordPress URL'), ENT_QUOTES, 'UTF-8') ?></label>
        <input type="url" name="site_url" placeholder="https://example.com" required>
        <small class="text-muted"><?= htmlspecialchars($t('content.wp_import_site_url_hint', 'URL of source WordPress website.'), ENT_QUOTES, 'UTF-8') ?></small>
    </div>
    <div class="mb-3">
        <label><?= htmlspecialchars($t('content.wp_import_mode', 'Import mode'), ENT_QUOTES, 'UTF-8') ?></label>
        <select name="import_mode" id="wp-import-mode">
            <option value="count"><?= htmlspecialchars($t('content.wp_import_last_n', 'Last N posts'), ENT_QUOTES, 'UTF-8') ?></option>
            <option value="all"><?= htmlspecialchars($t('content.wp_import_all', 'All posts'), ENT_QUOTES, 'UTF-8') ?></option>
        </select>
    </div>
    <div class="mb-3" id="wp-import-count-wrap">
        <label><?= htmlspecialchars($t('content.wp_import_count', 'Count'), ENT_QUOTES, 'UTF-8') ?></label>
        <input type="number" name="count" min="1" value="10">
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-primary" type="submit"><?= htmlspecialchars($t('content.wp_import_submit', 'Import'), ENT_QUOTES, 'UTF-8') ?></button>
        <a class="btn btn-light" href="<?= htmlspecialchars($url('admin/content'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($t('common.back', 'Back'), ENT_QUOTES, 'UTF-8') ?></a>
    </div>
</form>
<script>
    (function () {
        const mode = document.getElementById('wp-import-mode');
        const wrap = document.getElementById('wp-import-count-wrap');
        if (!mode || !wrap) {
            return;
        }

        const sync = () => {
            wrap.style.display = mode.value === 'all' ? 'none' : '';
        };

        mode.addEventListener('change', sync);
        sync();
    })();
</script>
