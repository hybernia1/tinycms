<?php
if (!defined('BASE_DIR')) {
    exit;
}

$dashboard = is_array($dashboard ?? null) ? $dashboard : [];
$stats = is_array($dashboard['stats'] ?? null) ? $dashboard['stats'] : [];
$recentContent = is_array($dashboard['recent_content'] ?? null) ? $dashboard['recent_content'] : [];
$recentComments = is_array($dashboard['recent_comments'] ?? null) ? $dashboard['recent_comments'] : [];
$statCards = [
    ['key' => 'content_all', 'icon' => 'content', 'href' => 'admin/content'],
    ['key' => 'content_published', 'icon' => 'success', 'href' => 'admin/content?status=published'],
    ['key' => 'content_draft', 'icon' => 'concept', 'href' => 'admin/content?status=draft'],
    ['key' => 'comments_pending', 'icon' => 'comments', 'href' => 'admin/comments?status=draft'],
    ['key' => 'media_all', 'icon' => 'media', 'href' => 'admin/media'],
    ['key' => 'users_all', 'icon' => 'users', 'href' => 'admin/users'],
];
$quickActions = [
    ['label' => t('admin.add_content'), 'icon' => 'add', 'href' => 'admin/content/add'],
    ['label' => t('admin.add_media'), 'icon' => 'upload', 'href' => 'admin/media/add'],
    ['label' => t('admin.menu.menu'), 'icon' => 'menu', 'href' => 'admin/menu'],
    ['label' => t('themes.customizer'), 'icon' => 'brush', 'href' => 'customizer'],
];
$cleanText = static function (string $value, int $limit): string {
    $text = trim(preg_replace('/\s+/u', ' ', strip_tags(html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8'))) ?? '');
    return mb_strlen($text) > $limit ? mb_substr($text, 0, max(0, $limit - 3)) . '...' : $text;
};
?>
<div class="dashboard-stack">
    <div class="dashboard-stats-grid">
        <?php foreach ($statCards as $card): ?>
            <?php $key = (string)$card['key']; ?>
            <a class="card dashboard-stat-card" href="<?= esc_url($url((string)$card['href'])) ?>">
                <span class="dashboard-stat-icon"><?= icon((string)$card['icon']) ?></span>
                <span class="dashboard-stat-value"><?= (int)($stats[$key] ?? 0) ?></span>
                <span class="dashboard-stat-label"><?= esc_html(t('admin.dashboard.stats.' . $key)) ?></span>
            </a>
        <?php endforeach; ?>
    </div>

    <div class="dashboard-quick-actions">
        <?php foreach ($quickActions as $action): ?>
            <a class="btn btn-light" href="<?= esc_url($url((string)$action['href'])) ?>">
                <?= icon((string)$action['icon']) ?>
                <span><?= esc_html((string)$action['label']) ?></span>
            </a>
        <?php endforeach; ?>
    </div>

    <div class="dashboard-panels">
        <section class="card dashboard-panel">
            <div class="dashboard-panel-header">
                <h2><?= esc_html(t('admin.dashboard.recent_content')) ?></h2>
                <a href="<?= esc_url($url('admin/content')) ?>"><?= esc_html(t('common.all')) ?></a>
            </div>
            <div class="dashboard-list">
                <?php if ($recentContent === []): ?>
                    <p class="m-0 text-muted"><?= esc_html(t('common.no_results')) ?></p>
                <?php endif; ?>
                <?php foreach ($recentContent as $item): ?>
                    <?php
                    $id = (int)($item['id'] ?? 0);
                    $name = trim((string)($item['name'] ?? ''));
                    $status = trim((string)($item['status'] ?? 'draft'));
                    ?>
                    <a class="dashboard-list-item" href="<?= esc_url($url('admin/content/edit?id=' . $id)) ?>">
                        <span class="dashboard-item-main">
                            <span><?= esc_html($name !== '' ? $name : '#' . $id) ?></span>
                            <span class="text-muted small"><?= esc_html($formatDateTime((string)($item['created'] ?? ''))) ?></span>
                        </span>
                        <span class="badge"><?= esc_html(t('content.statuses.' . $status, ucfirst($status))) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="card dashboard-panel">
            <div class="dashboard-panel-header">
                <h2><?= esc_html(t('admin.dashboard.recent_comments')) ?></h2>
                <a href="<?= esc_url($url('admin/comments')) ?>"><?= esc_html(t('common.all')) ?></a>
            </div>
            <div class="dashboard-list">
                <?php if ($recentComments === []): ?>
                    <p class="m-0 text-muted"><?= esc_html(t('common.no_results')) ?></p>
                <?php endif; ?>
                <?php foreach ($recentComments as $item): ?>
                    <?php
                    $id = (int)($item['id'] ?? 0);
                    $body = $cleanText((string)($item['body'] ?? ''), 110);
                    $author = trim((string)($item['author_name'] ?? ''));
                    if ($author === '') {
                        $author = trim((string)($item['author_email'] ?? ''));
                    }
                    $status = trim((string)($item['status'] ?? 'draft'));
                    ?>
                    <a class="dashboard-list-item" href="<?= esc_url($url('admin/comments/edit?id=' . $id)) ?>">
                        <span class="dashboard-item-main">
                            <span><?= esc_html($body !== '' ? $body : t('comments.empty_body')) ?></span>
                            <span class="text-muted small"><?= esc_html($author !== '' ? $author : t('common.no_author')) ?> - <?= esc_html($formatDateTime((string)($item['created'] ?? ''))) ?></span>
                        </span>
                        <span class="badge"><?= esc_html(t('comments.statuses.' . $status, ucfirst($status))) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
    </div>
</div>
