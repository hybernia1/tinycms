<?php
declare(strict_types=1);
$currentPath = trim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '', '/');
$authUser = $_SESSION['auth'] ?? null;
$isUsersList = str_ends_with($currentPath, 'admin/users');
$isContentList = str_ends_with($currentPath, 'admin/content');
$isMediaList = str_ends_with($currentPath, 'admin/media');
$isTermsList = str_ends_with($currentPath, 'admin/terms');
?>
<!doctype html>
<html lang="<?= htmlspecialchars((string)$lang, ENT_QUOTES, 'UTF-8') ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title><?= htmlspecialchars((string)$pageTitle, ENT_QUOTES, 'UTF-8') ?> | <?= htmlspecialchars($t('admin.title_suffix'), ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="<?= htmlspecialchars($url('assets/css/style.css'), ENT_QUOTES, 'UTF-8') ?>">
    <link rel="stylesheet" href="<?= htmlspecialchars($url('assets/css/admin.css'), ENT_QUOTES, 'UTF-8') ?>">
    <link rel="stylesheet" href="<?= htmlspecialchars($url('assets/editor/style.css'), ENT_QUOTES, 'UTF-8') ?>">
    <script>
        window.tinycmsI18n = <?= json_encode([
            'common' => [
                'delete' => $t('common.delete'),
                'close_notice' => $t('admin.close_notice'),
                'invalid_data' => $t('common.invalid_data', 'Invalid data.'),
            ],
            'content' => [
                'planned' => $t('content.planned'),
                'switch_to_draft' => $t('content.switch_to_draft'),
                'publish' => $t('content.publish'),
                'choose_image' => $t('content.choose_image', 'Choose image'),
                'deleted' => $t('content.deleted', 'Content deleted.'),
                'published' => $t('content.published', 'Content published.'),
                'switched_to_draft' => $t('content.switched_to_draft', 'Content switched to draft.'),
            ],
            'terms' => [
                'deleted' => $t('terms.deleted', 'Tag deleted.'),
                'delete' => $t('terms.delete'),
            ],
            'media' => [
                'deleted' => $t('media.deleted', 'Media deleted.'),
                'delete' => $t('media.delete'),
                'no_preview' => $t('media.no_preview', 'No preview'),
            ],
            'users' => [
                'deleted' => $t('users.deleted', 'User deleted.'),
                'suspended' => $t('users.suspended', 'User suspended.'),
                'unsuspended' => $t('users.unsuspended', 'User unsuspended.'),
                'status_suspended_single' => $t('users.status.suspended_single'),
                'delete' => $t('users.delete'),
                'suspend' => $t('users.suspend'),
                'unsuspend' => $t('users.unsuspend'),
            ],
            'editor' => [
                'placeholder' => $t('editor.placeholder', 'Start writing content…'),
                'align_left' => $t('editor.align_left', 'Left'),
                'align_center' => $t('editor.align_center', 'Center'),
                'align_right' => $t('editor.align_right', 'Right'),
                'list_bulleted' => $t('editor.list_bulleted', 'Bulleted list'),
                'list_numbered' => $t('editor.list_numbered', 'Numbered list'),
                'alignment' => $t('editor.alignment', 'Alignment'),
                'insert_link' => $t('editor.insert_link', 'Insert link'),
                'open_new_window' => $t('editor.open_new_window', 'Open in new window'),
                'add_nofollow' => $t('editor.add_nofollow', 'Add nofollow'),
                'clear' => $t('editor.clear', 'Clear'),
                'insert_image' => $t('editor.insert_image', 'Insert image'),
                'page_break' => $t('editor.page_break', 'Page break'),
                'background_color' => $t('editor.background_color', 'Background color'),
                'focus_mode' => $t('editor.focus_mode', 'Focus mode'),
                'focus_mode_exit' => $t('editor.focus_mode_exit', 'Exit focus mode'),
                'unlink' => $t('editor.unlink', 'Remove link'),
                'bold' => $t('editor.bold', 'Bold'),
                'italic' => $t('editor.italic', 'Italic'),
                'cancel' => $t('common.cancel'),
                'save' => $t('common.save'),
            ],
            'modal' => [
                'confirm_delete_type' => $t('modal.confirm_delete_type', 'Do you really want to delete this %s?'),
                'default_type' => $t('modal.default_type', 'item'),
            ],
            'auth' => [
                'show_password' => $t('front.login.show_password'),
                'hide_password' => $t('auth.hide_password', 'Hide password'),
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    </script>
    <script defer src="<?= htmlspecialchars($url('assets/js/flash.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
    <script defer src="<?= htmlspecialchars($url('assets/js/modal.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
    <script defer src="<?= htmlspecialchars($url('assets/js/admin-menu.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
    <script defer src="<?= htmlspecialchars($url('assets/js/custom-select.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
    <script defer src="<?= htmlspecialchars($url('assets/js/custom-datetime.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
    <script defer src="<?= htmlspecialchars($url('assets/js/password-toggle.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
    <script defer src="<?= htmlspecialchars($url('assets/js/media-library-modal.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
    <script defer src="<?= htmlspecialchars($url('assets/js/list-api.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
    <script defer src="<?= htmlspecialchars($url('assets/js/tag-picker.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
    <script defer src="<?= htmlspecialchars($url('assets/js/content-autosave.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
    <script defer src="<?= htmlspecialchars($url('assets/editor/editor.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
</head>
<body>
<div class="admin-shell">
    <aside class="admin-sidebar">
        <h2 class="admin-brand"><?= htmlspecialchars($t('admin.brand'), ENT_QUOTES, 'UTF-8') ?></h2>
        <nav class="admin-nav">
            <?php foreach ($adminMenu as $item):
                $itemPath = trim(parse_url((string)$item['url'], PHP_URL_PATH) ?? '', '/');
                $active = $itemPath !== '' && str_starts_with($currentPath, $itemPath);
            ?>
            <a class="admin-nav-link<?= $active ? ' active' : '' ?>" href="<?= htmlspecialchars((string)$item['url'], ENT_QUOTES, 'UTF-8') ?>">
                <?= htmlspecialchars((string)$item['label'], ENT_QUOTES, 'UTF-8') ?>
            </a>
            <?php endforeach; ?>
        </nav>
        <div class="admin-sidebar-bottom">
            <?php if (is_array($authUser)): ?>
            <div class="admin-user-meta">
                <div class="admin-user-name"><?= htmlspecialchars((string)($authUser['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                <div class="text-muted"><?= htmlspecialchars((string)($authUser['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
            </div>
            <?php endif; ?>
            <a class="btn btn-light w-100" href="<?= htmlspecialchars($url('admin/logout'), ENT_QUOTES, 'UTF-8') ?>">
                <?= $icon('logout') ?>
                <span><?= htmlspecialchars($t('admin.logout'), ENT_QUOTES, 'UTF-8') ?></span>
            </a>
        </div>
    </aside>
    <div class="admin-menu-overlay" data-admin-menu-close></div>
    <main class="admin-main">
        <div class="admin-header-spacer d-flex justify-between align-center">
            <div class="d-flex align-center gap-2">
                <button class="btn btn-light btn-icon admin-menu-toggle" type="button" data-admin-menu-toggle aria-label="<?= htmlspecialchars($t('admin.open_menu'), ENT_QUOTES, 'UTF-8') ?>" title="<?= htmlspecialchars($t('admin.open_menu'), ENT_QUOTES, 'UTF-8') ?>">
                    <?= $icon('menu') ?>
                    <span class="sr-only"><?= htmlspecialchars($t('admin.open_menu'), ENT_QUOTES, 'UTF-8') ?></span>
                </button>
                <strong><?= htmlspecialchars((string)$pageTitle, ENT_QUOTES, 'UTF-8') ?></strong>
            </div>
            <?php if ($isUsersList): ?>
            <a class="btn btn-primary" href="<?= htmlspecialchars($url('admin/users/add'), ENT_QUOTES, 'UTF-8') ?>">
                <?= $icon('add') ?>
                <span><?= htmlspecialchars($t('admin.add_user'), ENT_QUOTES, 'UTF-8') ?></span>
            </a>
            <?php elseif ($isContentList): ?>
            <a class="btn btn-primary" href="<?= htmlspecialchars($url('admin/content/add'), ENT_QUOTES, 'UTF-8') ?>">
                <?= $icon('add') ?>
                <span><?= htmlspecialchars($t('admin.add_content'), ENT_QUOTES, 'UTF-8') ?></span>
            </a>
            <?php elseif ($isMediaList): ?>
            <a class="btn btn-primary" href="<?= htmlspecialchars($url('admin/media/add'), ENT_QUOTES, 'UTF-8') ?>">
                <?= $icon('add') ?>
                <span><?= htmlspecialchars($t('admin.add_media'), ENT_QUOTES, 'UTF-8') ?></span>
            </a>
            <?php elseif ($isTermsList): ?>
            <a class="btn btn-primary" href="<?= htmlspecialchars($url('admin/terms/add'), ENT_QUOTES, 'UTF-8') ?>">
                <?= $icon('add') ?>
                <span><?= htmlspecialchars($t('admin.add_term'), ENT_QUOTES, 'UTF-8') ?></span>
            </a>
            <?php endif; ?>
        </div>
        <section class="admin-content p-2">
            <?php foreach ($flashes as $flash): ?>
            <div class="flash flash-<?= htmlspecialchars((string)($flash['type'] ?? 'info'), ENT_QUOTES, 'UTF-8') ?>">
                <span><?= htmlspecialchars((string)($flash['message'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                <button type="button" data-flash-close aria-label="<?= htmlspecialchars($t('admin.close_notice'), ENT_QUOTES, 'UTF-8') ?>" title="<?= htmlspecialchars($t('admin.close_notice'), ENT_QUOTES, 'UTF-8') ?>">
                    <?= $icon('cancel') ?>
                </button>
            </div>
            <?php endforeach; ?>
            <?= $content ?>
        </section>
    </main>
</div>
</body>
</html>
