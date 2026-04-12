<?php
declare(strict_types=1);
$currentPath = trim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '', '/');
$authUser = $_SESSION['auth'] ?? null;
$headerAction = is_array($headerAction ?? null) ? $headerAction : [];
?>
<!doctype html>
<html lang="<?= htmlspecialchars((string)$lang, ENT_QUOTES, 'UTF-8') ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title><?= htmlspecialchars((string)$pageTitle, ENT_QUOTES, 'UTF-8') ?> | <?= htmlspecialchars($t('admin.title_suffix'), ENT_QUOTES, 'UTF-8') ?></title>
    <?php if (!empty($siteFavicon)): ?>
        <link rel="icon" href="<?= htmlspecialchars($url((string)$siteFavicon), ENT_QUOTES, 'UTF-8') ?>">
    <?php endif; ?>
    <link rel="stylesheet" href="<?= htmlspecialchars($url('assets/css/style.css'), ENT_QUOTES, 'UTF-8') ?>">
    <link rel="stylesheet" href="<?= htmlspecialchars($url('assets/css/admin.css'), ENT_QUOTES, 'UTF-8') ?>">
    <link rel="stylesheet" href="<?= htmlspecialchars($url('assets/editor/style.css'), ENT_QUOTES, 'UTF-8') ?>">
    <script>window.tinycmsI18n = <?= json_encode($adminI18n ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;</script>
    <script defer src="<?= htmlspecialchars($url('assets/js/i18n.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
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
    <script defer src="<?= htmlspecialchars($url('assets/js/action-menu.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
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
                $itemUrl = (string)($item['url'] ?? '');
                $itemPath = trim(parse_url($itemUrl, PHP_URL_PATH) ?? '', '/');
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
                <strong data-admin-page-title><?= htmlspecialchars((string)$pageTitle, ENT_QUOTES, 'UTF-8') ?></strong>
            </div>
            <?php if (($headerAction['type'] ?? '') === 'submit'): ?>
                <button class="btn btn-primary" type="button" data-save-action-form-submit="<?= htmlspecialchars((string)($headerAction['form'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    <span><?= htmlspecialchars((string)($headerAction['label'] ?? $t('common.save')), ENT_QUOTES, 'UTF-8') ?></span>
                </button>
            <?php elseif (($headerAction['type'] ?? '') === 'link'): ?>
                <a class="btn btn-primary" href="<?= htmlspecialchars($url((string)($headerAction['href'] ?? '')), ENT_QUOTES, 'UTF-8') ?>">
                    <?= $icon((string)($headerAction['icon'] ?? 'add')) ?>
                    <span><?= htmlspecialchars((string)($headerAction['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                </a>
            <?php elseif (($headerAction['type'] ?? '') === 'save-menu'): ?>
                <div class="admin-header-action-menu" data-save-action-menu data-save-action-form="<?= htmlspecialchars((string)($headerAction['form'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    <div class="admin-header-action-split">
                        <button class="btn btn-primary admin-header-action-main" type="button" data-save-action-primary>
                            <span><?= htmlspecialchars($t('common.save'), ENT_QUOTES, 'UTF-8') ?></span>
                        </button>
                        <button class="btn btn-primary btn-icon admin-header-action-toggle" type="button" data-save-action-toggle aria-expanded="false" aria-label="<?= htmlspecialchars($t('common.actions'), ENT_QUOTES, 'UTF-8') ?>">
                            <?= $icon('next', 'icon content-action-summary-arrow') ?>
                        </button>
                    </div>
                    <div class="admin-header-action-options" hidden>
                        <div class="admin-header-action-group">
                            <button class="btn btn-light admin-header-action-option" type="button" data-save-action-submit>
                                <span><?= htmlspecialchars($t('common.save'), ENT_QUOTES, 'UTF-8') ?></span>
                            </button>
                        </div>
                        <div class="admin-header-action-group admin-header-action-group-danger">
                            <button class="btn btn-danger admin-header-action-option" type="button" data-save-action-delete data-modal-open data-modal-target="<?= htmlspecialchars((string)($headerAction['delete_modal_target'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                <span><?= htmlspecialchars($t('common.delete'), ENT_QUOTES, 'UTF-8') ?></span>
                                <?= $icon('delete') ?>
                            </button>
                        </div>
                    </div>
                </div>
            <?php elseif (($headerAction['type'] ?? '') === 'content-menu'): ?>
                <div class="admin-header-action-menu" data-content-action-menu>
                    <div class="admin-header-action-split">
                        <button class="btn btn-primary admin-header-action-main" type="button" data-content-action-primary>
                            <span data-content-action-label><?= htmlspecialchars($t('content.statuses.draft'), ENT_QUOTES, 'UTF-8') ?></span>
                        </button>
                        <button class="btn btn-primary btn-icon admin-header-action-toggle" type="button" data-content-action-toggle aria-expanded="false" aria-label="<?= htmlspecialchars($t('common.actions'), ENT_QUOTES, 'UTF-8') ?>">
                            <?= $icon('next', 'icon content-action-summary-arrow') ?>
                        </button>
                    </div>
                    <div class="admin-header-action-options" hidden>
                        <div class="admin-header-action-group">
                            <button class="btn btn-light admin-header-action-option" type="button" data-content-action-submit="published">
                                <span><?= htmlspecialchars($t('content.publish'), ENT_QUOTES, 'UTF-8') ?></span>
                                <span data-content-action-check="published"><?= $icon('success') ?></span>
                            </button>
                            <button class="btn btn-light admin-header-action-option" type="button" data-content-action-submit="draft">
                                <span><?= htmlspecialchars($t('content.statuses.draft'), ENT_QUOTES, 'UTF-8') ?></span>
                                <span data-content-action-check="draft"><?= $icon('success') ?></span>
                            </button>
                        </div>
                        <?php if (!empty($headerAction['delete_modal_target'])): ?>
                            <div class="admin-header-action-group admin-header-action-group-danger">
                                <button
                                    class="btn btn-danger admin-header-action-option"
                                    type="button"
                                    data-content-action-delete
                                    data-modal-open
                                    data-modal-target="<?= htmlspecialchars((string)$headerAction['delete_modal_target'], ENT_QUOTES, 'UTF-8') ?>"
                                >
                                    <span><?= htmlspecialchars($t('common.delete'), ENT_QUOTES, 'UTF-8') ?></span>
                                    <?= $icon('delete') ?>
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
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
