<?php
declare(strict_types=1);
if (!defined('BASE_DIR')) {
    exit;
}

$currentPath = trim((string)($currentRoute ?? ''), '/');
$authUser = $_SESSION['auth'] ?? null;
$headerAction = is_array($headerAction ?? null) ? $headerAction : [];
?>
<!doctype html>
<html lang="<?= $escHtml((string)$lang) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title><?= $escHtml((string)$pageTitle) ?> | <?= $escHtml($t('admin.title_suffix')) ?></title>
    <?php if (!empty($siteFavicon)): ?>
        <link rel="icon" href="<?= $escUrl($url((string)$siteFavicon)) ?>">
    <?php endif; ?>
    <link rel="stylesheet" href="<?= $escUrl($url(ASSETS_DIR . 'css/style.css')) ?>">
    <link rel="stylesheet" href="<?= $escUrl($url(ASSETS_DIR . 'css/admin.css')) ?>">
    <link rel="stylesheet" href="<?= $escUrl($url(ASSETS_DIR . 'css/editor.css')) ?>">
    <script>window.tinycmsI18n = <?= json_encode($adminI18n ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;</script>
    <script defer src="<?= $escUrl($url(ASSETS_DIR . 'js/i18n.js')) ?>"></script>
    <script defer src="<?= $escUrl($url(ASSETS_DIR . 'js/api.js')) ?>"></script>
    <script defer src="<?= $escUrl($url(ASSETS_DIR . 'js/flash.js')) ?>"></script>
    <script defer src="<?= $escUrl($url(ASSETS_DIR . 'js/loader.js')) ?>"></script>
    <script defer src="<?= $escUrl($url(ASSETS_DIR . 'js/modal.js')) ?>"></script>
    <script defer src="<?= $escUrl($url(ASSETS_DIR . 'js/admin-menu.js')) ?>"></script>
    <script defer src="<?= $escUrl($url(ASSETS_DIR . 'js/settings-tabs.js')) ?>"></script>
    <script defer src="<?= $escUrl($url(ASSETS_DIR . 'js/custom-select.js')) ?>"></script>
    <script defer src="<?= $escUrl($url(ASSETS_DIR . 'js/custom-datetime.js')) ?>"></script>
    <script defer src="<?= $escUrl($url(ASSETS_DIR . 'js/password-toggle.js')) ?>"></script>
    <script defer src="<?= $escUrl($url(ASSETS_DIR . 'js/custom-upload.js')) ?>"></script>
    <script defer src="<?= $escUrl($url(ASSETS_DIR . 'js/media-library-modal.js')) ?>"></script>
    <script defer src="<?= $escUrl($url(ASSETS_DIR . 'js/tag-picker.js')) ?>"></script>
    <script defer src="<?= $escUrl($url(ASSETS_DIR . 'js/menu-builder.js')) ?>"></script>
    <script defer src="<?= $escUrl($url(ASSETS_DIR . 'js/content-autosave.js')) ?>"></script>
    <script defer src="<?= $escUrl($url(ASSETS_DIR . 'js/action-menu.js')) ?>"></script>
    <script defer src="<?= $escUrl($url(ASSETS_DIR . 'js/editor.js')) ?>"></script>
    <script defer src="<?= $escUrl($url(ASSETS_DIR . 'js/heartbeat.js')) ?>"></script>
</head>
<body data-heartbeat-endpoint="<?= $escHtml($url('admin/api/v1/heartbeat')) ?>" data-heartbeat-login-endpoint="<?= $escHtml($url('admin/api/v1/auth/login')) ?>">
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
        <a class="admin-brand" href="<?= $escUrl($url('admin/dashboard')) ?>" aria-label="<?= $escHtml($t('admin.brand')) ?>">
            <img src="<?= $escUrl($url(ASSETS_DIR . 'svg/logo.svg')) ?>" alt="">
        </a>
        <nav class="admin-nav">
            <?php foreach ($adminMenu as $item):
                $itemUrl = (string)($item['url'] ?? '');
                $itemPath = trim((string)($item['path'] ?? $itemUrl), '/');
                $active = $itemPath !== '' && str_starts_with($currentPath, $itemPath);
            ?>
            <a class="admin-nav-link<?= $active ? ' active' : '' ?>" href="<?= $escUrl((string)$item['url']) ?>">
                <?php if (!empty($item['icon'])): ?>
                <?= $icon((string)$item['icon'], 'icon admin-nav-link-icon') ?>
                <?php endif; ?>
                <span><?= $escHtml((string)$item['label']) ?></span>
            </a>
            <?php endforeach; ?>
        </nav>
        <div class="admin-sidebar-bottom">
            <?php if (is_array($authUser)): ?>
            <div class="admin-user-meta">
                <div class="admin-user-name"><?= $escHtml((string)($authUser['name'] ?? '')) ?></div>
            </div>
            <?php endif; ?>
            <a class="admin-nav-link" href="<?= $escUrl($url('admin/logout')) ?>">
                <?= $icon('logout') ?>
                <span><?= $escHtml($t('admin.logout')) ?></span>
            </a>
        </div>
    </aside>
    <div class="admin-menu-overlay" data-admin-menu-close></div>
    <main class="admin-main">
        <div class="admin-header-spacer d-flex justify-between align-center p-2">
            <div class="d-flex align-center gap-2">
                <button class="btn btn-light btn-icon admin-sidebar-toggle" type="button" data-admin-sidebar-toggle aria-label="<?= $escHtml($t('admin.open_menu')) ?>" title="<?= $escHtml($t('admin.open_menu')) ?>">
                    <?= $icon('menu') ?>
                    <span class="sr-only"><?= $escHtml($t('admin.open_menu')) ?></span>
                </button>
                <button class="btn btn-light btn-icon admin-menu-toggle" type="button" data-admin-menu-toggle aria-label="<?= $escHtml($t('admin.open_menu')) ?>" title="<?= $escHtml($t('admin.open_menu')) ?>">
                    <?= $icon('menu') ?>
                    <span class="sr-only"><?= $escHtml($t('admin.open_menu')) ?></span>
                </button>
                <strong data-admin-page-title><?= $escHtml((string)$pageTitle) ?></strong>
            </div>
            <?php if (($headerAction['type'] ?? '') === 'submit'): ?>
                <button class="btn btn-primary" type="button" data-save-action-form-submit="<?= $escHtml((string)($headerAction['form'] ?? '')) ?>">
                    <span><?= $escHtml((string)($headerAction['label'] ?? $t('common.save'))) ?></span>
                </button>
            <?php elseif (($headerAction['type'] ?? '') === 'link'): ?>
                <a class="btn btn-primary" href="<?= $escUrl($url((string)($headerAction['href'] ?? ''))) ?>">
                    <?= $icon((string)($headerAction['icon'] ?? 'add')) ?>
                    <span><?= $escHtml((string)($headerAction['label'] ?? '')) ?></span>
                </a>
            <?php elseif (($headerAction['type'] ?? '') === 'save-menu'): ?>
                <div class="admin-header-action-menu" data-save-action-menu data-save-action-form="<?= $escHtml((string)($headerAction['form'] ?? '')) ?>">
                    <div class="admin-header-action-split">
                        <button class="btn btn-primary admin-header-action-main" type="button" data-save-action-primary>
                            <span><?= $escHtml($t('common.save')) ?></span>
                        </button>
                        <button class="btn btn-primary btn-icon admin-header-action-toggle" type="button" data-save-action-toggle aria-expanded="false" aria-label="<?= $escHtml($t('common.actions')) ?>">
                            <?= $icon('next', 'icon content-action-summary-arrow') ?>
                        </button>
                    </div>
                    <div class="admin-header-action-options" hidden>
                        <div class="admin-header-action-group">
                            <button class="btn btn-light admin-header-action-option" type="button" data-save-action-submit>
                                <span><?= $escHtml($t('common.save')) ?></span>
                            </button>
                        </div>
                        <div class="admin-header-action-group admin-header-action-group-danger">
                            <button class="btn btn-danger admin-header-action-option" type="button" data-save-action-delete data-modal-open data-modal-target="<?= $escHtml((string)($headerAction['delete_modal_target'] ?? '')) ?>">
                                <span><?= $escHtml($t('common.delete')) ?></span>
                                <?= $icon('delete') ?>
                            </button>
                        </div>
                    </div>
                </div>
            <?php elseif (($headerAction['type'] ?? '') === 'content-menu'): ?>
                <div class="admin-header-action-menu" data-content-action-menu>
                    <div class="admin-header-action-split">
                        <button class="btn btn-primary admin-header-action-main" type="button" data-content-action-primary>
                            <span data-content-action-label><?= $escHtml($t('content.statuses.draft')) ?></span>
                        </button>
                        <button class="btn btn-primary btn-icon admin-header-action-toggle" type="button" data-content-action-toggle aria-expanded="false" aria-label="<?= $escHtml($t('common.actions')) ?>">
                            <?= $icon('next', 'icon content-action-summary-arrow') ?>
                        </button>
                    </div>
                    <div class="admin-header-action-options" hidden>
                        <div class="admin-header-action-group">
                            <button class="btn btn-light admin-header-action-option" type="button" data-content-action-submit="published">
                                <span><?= $escHtml($t('content.publish')) ?></span>
                                <span data-content-action-check="published"><?= $icon('success') ?></span>
                            </button>
                            <button class="btn btn-light admin-header-action-option" type="button" data-content-action-submit="draft">
                                <span><?= $escHtml($t('content.statuses.draft')) ?></span>
                                <span data-content-action-check="draft"><?= $icon('success') ?></span>
                            </button>
                        </div>
                        <div
                            class="admin-header-action-group admin-header-action-group-danger"
                            data-content-delete-group
                            <?= empty($headerAction['delete_modal_target']) ? 'hidden' : '' ?>
                        >
                            <button
                                class="btn btn-danger admin-header-action-option"
                                type="button"
                                data-content-action-delete
                                data-modal-open
                                data-modal-target="<?= $escHtml((string)($headerAction['delete_modal_target'] ?? '#content-delete-modal')) ?>"
                            >
                                <span><?= $escHtml($t('common.delete')) ?></span>
                                <?= $icon('delete') ?>
                            </button>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <section class="admin-content p-2">
            <?php foreach ($flashes as $flash): ?>
            <?php $flashType = (string)($flash['type'] ?? 'warning'); ?>
            <?php $flashIcon = $flashType === 'success' ? 'success' : ($flashType === 'error' ? 'error' : 'warning'); ?>
            <div class="flash flash-<?= $escHtml($flashType === 'info' ? 'warning' : $flashType) ?>">
                <span class="d-flex align-center gap-2"><?= $icon($flashIcon) ?><span><?= $escHtml((string)($flash['message'] ?? '')) ?></span></span>
                <button type="button" data-flash-close aria-label="<?= $escHtml($t('admin.close_notice')) ?>" title="<?= $escHtml($t('admin.close_notice')) ?>">
                    <?= $icon('cancel') ?>
                </button>
            </div>
            <?php endforeach; ?>
            <?= $content ?>
        </section>
    </main>
    <div class="admin-version-corner">TinyCMS <?= $escHtml((string)($appVersion ?? '0.9.0')) ?></div>
</div>
<div class="modal-overlay" id="session-login-modal" data-session-login-modal>
    <div class="modal session-login-modal">
        <h3 class="m-0 mb-3"><?= $escHtml($t('auth.login')) ?></h3>
        <p class="m-0 mb-3"><?= $escHtml($t('auth.session_expired')) ?></p>
        <p class="m-0 mb-3 text-danger" data-session-login-message hidden></p>
        <form method="post" action="<?= $escUrl($url('admin/api/v1/auth/login')) ?>" data-session-login-form>
            <?= $csrfField() ?>
            <div class="mb-3">
                <label><?= $escHtml($t('common.email')) ?></label>
                <div class="field-with-icon">
                    <span class="field-overlay field-overlay-start field-icon" aria-hidden="true"><?= $icon('email') ?></span>
                    <input class="field-control-with-start-icon" type="email" name="email" data-session-login-email required>
                </div>
                <small class="text-danger" data-session-login-error="email" hidden></small>
            </div>
            <div class="mb-3">
                <label><?= $escHtml($t('common.password')) ?></label>
                <div class="field-with-icon">
                    <input class="field-control-with-end-icon" type="password" name="password" data-password-input required>
                    <button class="field-overlay field-overlay-end field-icon-button" type="button" data-password-toggle aria-label="<?= $escHtml($t('auth.show_password')) ?>" title="<?= $escHtml($t('auth.show_password')) ?>">
                        <?= $icon('show') ?>
                    </button>
                </div>
                <small class="text-danger" data-session-login-error="password" hidden></small>
            </div>
            <div class="mb-4">
                <label><input type="checkbox" name="remember" value="1"> <?= $escHtml($t('auth.remember')) ?></label>
            </div>
            <button class="btn btn-primary" type="submit" data-session-login-submit><?= $escHtml($t('auth.login')) ?></button>
        </form>
    </div>
</div>
<div class="modal-overlay" id="connection-lost-modal" data-connection-lost-modal>
    <div class="modal session-login-modal">
        <h3 class="m-0 mb-3"><?= $escHtml($t('common.connection_lost')) ?></h3>
        <p class="m-0 mb-4"><?= $escHtml($t('auth.connection_lost')) ?></p>
        <button class="btn btn-primary" type="button" data-connection-lost-retry><?= $escHtml($t('common.retry')) ?></button>
    </div>
</div>
</body>
</html>
