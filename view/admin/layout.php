<?php
declare(strict_types=1);
$currentPath = trim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '', '/');
$authUser = $_SESSION['auth'] ?? null;
$isUsersList = str_ends_with($currentPath, 'admin/users');
$isContentList = str_ends_with($currentPath, 'admin/content');
$isMediaList = str_ends_with($currentPath, 'admin/media');
$isTermsList = str_ends_with($currentPath, 'admin/terms');
$editorIcons = file_get_contents(__DIR__ . '/../../assets/editor/icons.svg') ?: '';
?>
<!doctype html>
<html lang="cs">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title><?= htmlspecialchars((string)$pageTitle, ENT_QUOTES, 'UTF-8') ?> | Admin</title>
    <link rel="stylesheet" href="<?= htmlspecialchars($url('assets/css/style.css'), ENT_QUOTES, 'UTF-8') ?>">
    <link rel="stylesheet" href="<?= htmlspecialchars($url('assets/css/admin.css'), ENT_QUOTES, 'UTF-8') ?>">
    <link rel="stylesheet" href="<?= htmlspecialchars($url('assets/editor/style.css'), ENT_QUOTES, 'UTF-8') ?>">
    <script defer src="<?= htmlspecialchars($url('assets/js/flash.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
    <script defer src="<?= htmlspecialchars($url('assets/js/modal.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
    <script defer src="<?= htmlspecialchars($url('assets/js/admin-auth-check.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
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
<body
    data-auth-check-endpoint="<?= htmlspecialchars($url('admin/api/v1/auth/check'), ENT_QUOTES, 'UTF-8') ?>"
    data-home-url="<?= htmlspecialchars($url(''), ENT_QUOTES, 'UTF-8') ?>"
>
<?= $editorIcons ?>
<div class="admin-shell">
    <aside class="admin-sidebar">
        <h2 class="admin-brand">TinyCMS Admin</h2>
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
                <span>Odhlásit</span>
            </a>
        </div>
    </aside>
    <div class="admin-menu-overlay" data-admin-menu-close></div>
    <main class="admin-main">
        <div class="admin-header-spacer d-flex justify-between align-center">
            <div class="d-flex align-center gap-2">
                <button class="btn btn-light btn-icon admin-menu-toggle" type="button" data-admin-menu-toggle aria-label="Otevřít menu" title="Otevřít menu">
                    <?= $icon('menu') ?>
                    <span class="sr-only">Otevřít menu</span>
                </button>
                <strong><?= htmlspecialchars((string)$pageTitle, ENT_QUOTES, 'UTF-8') ?></strong>
            </div>
            <?php if ($isUsersList): ?>
            <a class="btn btn-primary" href="<?= htmlspecialchars($url('admin/users/add'), ENT_QUOTES, 'UTF-8') ?>">
                <?= $icon('add') ?>
                <span>Přidat uživatele</span>
            </a>
            <?php elseif ($isContentList): ?>
            <a class="btn btn-primary" href="<?= htmlspecialchars($url('admin/content/add'), ENT_QUOTES, 'UTF-8') ?>">
                <?= $icon('add') ?>
                <span>Přidat obsah</span>
            </a>
            <?php elseif ($isMediaList): ?>
            <a class="btn btn-primary" href="<?= htmlspecialchars($url('admin/media/add'), ENT_QUOTES, 'UTF-8') ?>">
                <?= $icon('add') ?>
                <span>Přidat médium</span>
            </a>
            <?php elseif ($isTermsList): ?>
            <a class="btn btn-primary" href="<?= htmlspecialchars($url('admin/terms/add'), ENT_QUOTES, 'UTF-8') ?>">
                <?= $icon('add') ?>
                <span>Přidat štítek</span>
            </a>
            <?php endif; ?>
        </div>
        <section class="admin-content p-2">
            <?php foreach ($flashes as $flash): ?>
            <div class="flash flash-<?= htmlspecialchars((string)($flash['type'] ?? 'info'), ENT_QUOTES, 'UTF-8') ?>">
                <span><?= htmlspecialchars((string)($flash['message'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                <button type="button" data-flash-close aria-label="Zavřít notifikaci" title="Zavřít notifikaci">
                    <?= $icon('cancel') ?>
                </button>
            </div>
            <?php endforeach; ?>
            <?= $content ?>
        </section>
    </main>
</div>
<div class="modal-overlay" data-auth-check-modal>
    <div class="modal">
        <p data-auth-check-modal-text>Byli jste odhlášeni. Přihlaste se znovu.</p>
        <form method="post" action="<?= htmlspecialchars($url('login'), ENT_QUOTES, 'UTF-8') ?>" data-auth-check-login hidden>
            <?= $csrfField() ?>
            <div class="mb-3">
                <label>Email</label>
                <div class="input-with-icon">
                    <span class="input-with-icon-symbol" aria-hidden="true"><?= $icon('email') ?></span>
                    <input class="input-with-icon-field" type="email" name="email" required>
                </div>
            </div>
            <div class="mb-3">
                <label>Heslo</label>
                <div class="input-with-icon">
                    <input class="input-with-icon-toggle" type="password" name="password" data-password-input required>
                    <button class="input-with-icon-action" type="button" data-password-toggle aria-label="Zobrazit heslo" title="Zobrazit heslo">
                        <?= $icon('show') ?>
                    </button>
                </div>
            </div>
            <div class="mb-3">
                <label><input type="checkbox" name="remember" value="1"> Zapamatovat si mě</label>
            </div>
            <button class="btn btn-primary w-100" type="submit">Přihlásit</button>
        </form>
        <div class="modal-actions">
            <button class="btn btn-light" type="button" data-auth-check-modal-close>Zavřít</button>
        </div>
    </div>
</div>
</body>
</html>
