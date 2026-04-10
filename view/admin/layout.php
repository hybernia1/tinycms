<?php
declare(strict_types=1);
$currentPath = trim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '', '/');
$authUser = $_SESSION['auth'] ?? null;
$isUsersList = str_ends_with($currentPath, 'admin/users');
$isContentList = str_ends_with($currentPath, 'admin/content');
$isMediaList = str_ends_with($currentPath, 'admin/media');
$isMediaEdit = str_ends_with($currentPath, 'admin/media/edit');
$isTermsList = str_ends_with($currentPath, 'admin/terms');
$isUsersEdit = str_ends_with($currentPath, 'admin/users/edit');
$isContentEdit = str_ends_with($currentPath, 'admin/content/edit');
$isTermsEdit = str_ends_with($currentPath, 'admin/terms/edit');
?>
<!doctype html>
<html lang="<?= htmlspecialchars((string)$lang, ENT_QUOTES, 'UTF-8') ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title><?= htmlspecialchars((string)$pageTitle, ENT_QUOTES, 'UTF-8') ?> | <?= htmlspecialchars($t('admin.title_suffix'), ENT_QUOTES, 'UTF-8') ?></title>
    <?php if (!empty($siteLogo)): ?>
        <meta property="og:image" content="<?= htmlspecialchars($url((string)$siteLogo), ENT_QUOTES, 'UTF-8') ?>">
    <?php endif; ?>
    <?php if (!empty($siteFavicon)): ?>
        <link rel="icon" href="<?= htmlspecialchars($url((string)$siteFavicon), ENT_QUOTES, 'UTF-8') ?>">
    <?php endif; ?>
    <link rel="stylesheet" href="<?= htmlspecialchars($url('assets/css/style.css'), ENT_QUOTES, 'UTF-8') ?>">
    <link rel="stylesheet" href="<?= htmlspecialchars($url('assets/css/admin.css'), ENT_QUOTES, 'UTF-8') ?>">
    <link rel="stylesheet" href="<?= htmlspecialchars($url('assets/editor/style.css'), ENT_QUOTES, 'UTF-8') ?>">
    <script>
        window.tinycmsI18n = <?= json_encode([
            'common' => [
                'delete' => $t('common.delete'),
                'close_notice' => $t('admin.close_notice'),
                'invalid_data' => $t('common.invalid_data'),
            ],
            'content' => [
                'planned' => $t('content.planned'),
                'switch_to_draft' => $t('content.switch_to_draft'),
                'publish' => $t('content.publish'),
                'statuses' => [
                    'draft' => $t('content.statuses.draft'),
                    'published' => $t('content.statuses.published'),
                ],
                'choose_image' => $t('content.choose_image'),
                'deleted' => $t('content.deleted'),
                'published' => $t('content.published'),
                'switched_to_draft' => $t('content.switched_to_draft'),
            ],
            'terms' => [
                'deleted' => $t('terms.deleted'),
                'delete' => $t('terms.delete'),
            ],
            'media' => [
                'deleted' => $t('media.deleted'),
                'delete' => $t('media.delete'),
                'no_preview' => $t('media.no_preview'),
            ],
            'users' => [
                'deleted' => $t('users.deleted'),
                'suspended' => $t('users.suspended'),
                'unsuspended' => $t('users.unsuspended'),
                'status_suspended_single' => $t('users.status.suspended_single'),
                'roles' => [
                    'editor' => $t('users.roles.editor'),
                    'admin' => $t('users.roles.admin'),
                ],
                'delete' => $t('users.delete'),
                'suspend' => $t('users.suspend'),
                'unsuspend' => $t('users.unsuspend'),
            ],
            'editor' => [
                'placeholder' => $t('editor.placeholder'),
                'headings' => $t('editor.headings'),
                'paragraph' => $t('editor.paragraph'),
                'heading_1' => $t('editor.heading_1'),
                'heading_2' => $t('editor.heading_2'),
                'heading_3' => $t('editor.heading_3'),
                'heading_4' => $t('editor.heading_4'),
                'heading_5' => $t('editor.heading_5'),
                'heading_6' => $t('editor.heading_6'),
                'lists' => $t('editor.lists'),
                'quote' => $t('editor.quote'),
                'align_left' => $t('editor.align_left'),
                'align_center' => $t('editor.align_center'),
                'align_right' => $t('editor.align_right'),
                'align_justify' => $t('editor.align_justify'),
                'list_bulleted' => $t('editor.list_bulleted'),
                'list_numbered' => $t('editor.list_numbered'),
                'alignment' => $t('editor.alignment'),
                'insert_link' => $t('editor.insert_link'),
                'open_new_window' => $t('editor.open_new_window'),
                'add_nofollow' => $t('editor.add_nofollow'),
                'clear' => $t('editor.clear'),
                'text_color' => $t('editor.text_color'),
                'insert_image' => $t('editor.insert_image'),
                'page_break' => $t('editor.page_break'),
                'background_color' => $t('editor.background_color'),
                'focus_mode' => $t('editor.focus_mode'),
                'focus_mode_exit' => $t('editor.focus_mode_exit'),
                'unlink' => $t('editor.unlink'),
                'remove_link' => $t('editor.remove_link'),
                'link_title' => $t('editor.link_title'),
                'bold' => $t('editor.bold'),
                'italic' => $t('editor.italic'),
                'cancel' => $t('common.cancel'),
                'save' => $t('common.save'),
            ],
            'datetime' => [
                'pick_date_time' => $t('datetime.pick_date_time'),
                'today' => $t('datetime.today'),
                'clear' => $t('datetime.clear'),
                'prev_month' => $t('datetime.prev_month'),
                'next_month' => $t('datetime.next_month'),
                'weekdays_short' => [
                    $t('datetime.weekdays_short.mon'),
                    $t('datetime.weekdays_short.tue'),
                    $t('datetime.weekdays_short.wed'),
                    $t('datetime.weekdays_short.thu'),
                    $t('datetime.weekdays_short.fri'),
                    $t('datetime.weekdays_short.sat'),
                    $t('datetime.weekdays_short.sun'),
                ],
                'months' => [
                    $t('datetime.months.jan'),
                    $t('datetime.months.feb'),
                    $t('datetime.months.mar'),
                    $t('datetime.months.apr'),
                    $t('datetime.months.may'),
                    $t('datetime.months.jun'),
                    $t('datetime.months.jul'),
                    $t('datetime.months.aug'),
                    $t('datetime.months.sep'),
                    $t('datetime.months.oct'),
                    $t('datetime.months.nov'),
                    $t('datetime.months.dec'),
                ],
            ],
            'modal' => [
                'confirm_delete_type' => $t('modal.confirm_delete_type'),
                'default_type' => $t('modal.default_type'),
            ],
            'auth' => [
                'show_password' => $t('front.login.show_password'),
                'hide_password' => $t('auth.hide_password'),
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    </script>
    <script defer src="<?= htmlspecialchars($url('assets/js/flash.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
    <script defer src="<?= htmlspecialchars($url('assets/js/loader.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
    <script defer src="<?= htmlspecialchars($url('assets/js/modal.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
    <script defer src="<?= htmlspecialchars($url('assets/js/admin-menu.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
    <script defer src="<?= htmlspecialchars($url('assets/js/custom-select.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
    <script defer src="<?= htmlspecialchars($url('assets/js/custom-datetime.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
    <script defer src="<?= htmlspecialchars($url('assets/js/password-toggle.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
    <script defer src="<?= htmlspecialchars($url('assets/js/custom-upload.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
    <script defer src="<?= htmlspecialchars($url('assets/js/media-library-modal.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
    <script defer src="<?= htmlspecialchars($url('assets/js/list-api.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
    <script defer src="<?= htmlspecialchars($url('assets/js/tag-picker.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
    <script defer src="<?= htmlspecialchars($url('assets/js/content-autosave.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
    <script defer src="<?= htmlspecialchars($url('assets/js/content-action-menu.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
    <script defer src="<?= htmlspecialchars($url('assets/editor/editor.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
</head>
<body>
<script>
    (function () {
        var collapsed = (document.cookie.split(';').map(function (part) { return part.trim(); }).indexOf('tinycms_admin_sidebar=collapsed') !== -1);
        if (collapsed) {
            document.body.classList.add('admin-sidebar-collapsed');
        }
    })();
</script>
<div class="admin-shell">
    <aside class="admin-sidebar">
        <a class="admin-brand" href="<?= htmlspecialchars($url('admin/dashboard'), ENT_QUOTES, 'UTF-8') ?>" aria-label="<?= htmlspecialchars($t('admin.brand'), ENT_QUOTES, 'UTF-8') ?>">
            <img src="<?= htmlspecialchars($url('assets/svg/logo.svg'), ENT_QUOTES, 'UTF-8') ?>" alt="">
        </a>
        <nav class="admin-nav">
            <?php foreach ($adminMenu as $item):
                $role = (string)($authUser['role'] ?? '');
                $itemUrl = (string)($item['url'] ?? '');
                $itemPath = trim(parse_url($itemUrl, PHP_URL_PATH) ?? '', '/');
                if ($role !== 'admin' && in_array($itemPath, ['admin/users', 'admin/settings'], true)) {
                    continue;
                }
                $active = $itemPath !== '' && str_starts_with($currentPath, $itemPath);
            ?>
            <a class="admin-nav-link<?= $active ? ' active' : '' ?>" href="<?= htmlspecialchars((string)$item['url'], ENT_QUOTES, 'UTF-8') ?>">
                <?php if (!empty($item['icon'])): ?>
                <?= $icon((string)$item['icon'], 'icon admin-nav-link-icon') ?>
                <?php endif; ?>
                <span><?= htmlspecialchars((string)$item['label'], ENT_QUOTES, 'UTF-8') ?></span>
            </a>
            <?php endforeach; ?>
        </nav>
        <div class="admin-sidebar-bottom">
            <?php if (is_array($authUser)): ?>
            <div class="admin-user-meta">
                <div class="admin-user-name"><?= htmlspecialchars((string)($authUser['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
            </div>
            <?php endif; ?>
            <a class="admin-nav-link" href="<?= htmlspecialchars($url('admin/logout'), ENT_QUOTES, 'UTF-8') ?>">
                <?= $icon('logout') ?>
                <span><?= htmlspecialchars($t('admin.logout'), ENT_QUOTES, 'UTF-8') ?></span>
            </a>
        </div>
    </aside>
    <div class="admin-menu-overlay" data-admin-menu-close></div>
    <main class="admin-main">
        <div class="admin-header-spacer d-flex justify-between align-center p-2">
            <div class="d-flex align-center gap-2">
                <button class="btn btn-light btn-icon admin-sidebar-toggle" type="button" data-admin-sidebar-toggle aria-label="<?= htmlspecialchars($t('admin.open_menu'), ENT_QUOTES, 'UTF-8') ?>" title="<?= htmlspecialchars($t('admin.open_menu'), ENT_QUOTES, 'UTF-8') ?>">
                    <?= $icon('menu') ?>
                    <span class="sr-only"><?= htmlspecialchars($t('admin.open_menu'), ENT_QUOTES, 'UTF-8') ?></span>
                </button>
                <button class="btn btn-light btn-icon admin-menu-toggle" type="button" data-admin-menu-toggle aria-label="<?= htmlspecialchars($t('admin.open_menu'), ENT_QUOTES, 'UTF-8') ?>" title="<?= htmlspecialchars($t('admin.open_menu'), ENT_QUOTES, 'UTF-8') ?>">
                    <?= $icon('menu') ?>
                    <span class="sr-only"><?= htmlspecialchars($t('admin.open_menu'), ENT_QUOTES, 'UTF-8') ?></span>
                </button>
                <strong><?= htmlspecialchars((string)$pageTitle, ENT_QUOTES, 'UTF-8') ?></strong>
            </div>
            <?php if ($isMediaEdit && isset($navigation) && is_array($navigation)): ?>
            <?php $prevMediaId = (int)($navigation['prev'] ?? 0); $nextMediaId = (int)($navigation['next'] ?? 0); ?>
            <div class="d-flex align-center gap-2">
                <?php if ($prevMediaId > 0): ?>
                <a class="btn btn-light" href="<?= htmlspecialchars($url('admin/media/edit?id=' . $prevMediaId), ENT_QUOTES, 'UTF-8') ?>">
                    <?= $icon('prev') ?>
                    <span><?= htmlspecialchars($t('common.previous'), ENT_QUOTES, 'UTF-8') ?></span>
                </a>
                <?php endif; ?>
                <?php if ($nextMediaId > 0): ?>
                <a class="btn btn-light" href="<?= htmlspecialchars($url('admin/media/edit?id=' . $nextMediaId), ENT_QUOTES, 'UTF-8') ?>">
                    <span><?= htmlspecialchars($t('common.next'), ENT_QUOTES, 'UTF-8') ?></span>
                    <?= $icon('next') ?>
                </a>
                <?php endif; ?>
            </div>
            <?php elseif ($isUsersList || $isUsersEdit): ?>
            <a class="btn btn-primary" href="<?= htmlspecialchars($url('admin/users/add'), ENT_QUOTES, 'UTF-8') ?>">
                <?= $icon('add') ?>
                <span><?= htmlspecialchars($t('admin.add_user'), ENT_QUOTES, 'UTF-8') ?></span>
            </a>
            <?php elseif ($isContentEdit): ?>
            <details class="admin-header-action-menu" data-content-action-menu>
                <summary class="btn btn-primary">
                    <?= $icon('add') ?>
                    <span><?= htmlspecialchars($t('common.actions'), ENT_QUOTES, 'UTF-8') ?></span>
                </summary>
                <div class="admin-header-action-options">
                    <button class="btn btn-light" type="button" data-content-action-submit="published"><?= htmlspecialchars($t('content.publish'), ENT_QUOTES, 'UTF-8') ?></button>
                    <button class="btn btn-light" type="button" data-content-action-submit="draft"><?= htmlspecialchars($t('content.statuses.draft'), ENT_QUOTES, 'UTF-8') ?></button>
                    <button class="btn btn-danger" type="button" data-content-action-delete><?= htmlspecialchars($t('common.delete'), ENT_QUOTES, 'UTF-8') ?></button>
                </div>
            </details>
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
            <?php elseif ($isTermsList || $isTermsEdit): ?>
            <a class="btn btn-primary" href="<?= htmlspecialchars($url('admin/terms/add'), ENT_QUOTES, 'UTF-8') ?>">
                <?= $icon('add') ?>
                <span><?= htmlspecialchars($t('admin.add_term'), ENT_QUOTES, 'UTF-8') ?></span>
            </a>
            <?php endif; ?>
        </div>
        <section class="admin-content p-2">
            <?php foreach ($flashes as $flash): ?>
            <?php $flashType = (string)($flash['type'] ?? 'warning'); ?>
            <?php $flashIcon = $flashType === 'success' ? 'success' : ($flashType === 'error' ? 'error' : 'warning'); ?>
            <div class="flash flash-<?= htmlspecialchars($flashType === 'info' ? 'warning' : $flashType, ENT_QUOTES, 'UTF-8') ?>">
                <span class="d-flex align-center gap-2"><?= $icon($flashIcon) ?><span><?= htmlspecialchars((string)($flash['message'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span></span>
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
