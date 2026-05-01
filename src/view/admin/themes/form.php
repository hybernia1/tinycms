<?php
if (!defined('BASE_DIR')) {
    exit;
}

$themes = is_array($themes ?? null) ? $themes : [];
$activeTheme = (string)($activeTheme ?? 'default');
?>
<div class="theme-grid">
    <?php foreach ($themes as $slug => $theme): ?>
        <?php
            $slug = (string)$slug;
            $isActive = $slug === $activeTheme;
            $name = (string)($theme['name'] ?? $slug);
            $description = trim((string)($theme['description'] ?? ''));
            $version = trim((string)($theme['version'] ?? ''));
            $author = trim((string)($theme['author'] ?? ''));
        ?>
        <article class="theme-card<?= $isActive ? ' active' : '' ?>">
            <div class="theme-card-preview">
                <div class="theme-card-preview-header">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
                <div class="theme-card-preview-body">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
            </div>

            <div class="theme-card-body">
                <div class="theme-card-title">
                    <h2><?= esc_html($name) ?></h2>
                    <?php if ($isActive): ?>
                        <span class="badge"><?= esc_html(t('themes.active')) ?></span>
                    <?php elseif ($version !== ''): ?>
                        <span class="badge"><?= esc_html($version) ?></span>
                    <?php endif; ?>
                </div>

                <?php if ($description !== ''): ?>
                    <p class="text-muted"><?= esc_html($description) ?></p>
                <?php endif; ?>

                <dl class="theme-card-meta">
                    <div>
                        <dt><?= esc_html(t('common.author')) ?></dt>
                        <dd><?= esc_html($author !== '' ? $author : '-') ?></dd>
                    </div>
                    <div>
                        <dt><?= esc_html(t('themes.slug')) ?></dt>
                        <dd><code><?= esc_html($slug) ?></code></dd>
                    </div>
                </dl>

                <?php if ((array)($theme['features'] ?? []) !== []): ?>
                    <div class="theme-card-features">
                        <?php foreach ((array)$theme['features'] as $feature): ?>
                            <?php $feature = (string)$feature; ?>
                            <span class="badge"><?= esc_html(t('themes.feature_labels.' . $feature, $feature)) ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="theme-card-actions">
                <?php if ($isActive): ?>
                    <a class="btn btn-primary" href="<?= esc_url($url('customizer')) ?>">
                        <?= icon('settings') ?>
                        <span><?= esc_html(t('themes.customize')) ?></span>
                    </a>
                <?php else: ?>
                    <form method="post" action="<?= esc_url($url('admin/api/v1/themes')) ?>" data-api-submit>
                        <?= $csrfField() ?>
                        <input type="hidden" name="theme[front_theme]" value="<?= esc_attr($slug) ?>">
                        <button class="btn btn-light" type="submit">
                            <span><?= esc_html(t('themes.activate')) ?></span>
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </article>
    <?php endforeach; ?>
</div>
