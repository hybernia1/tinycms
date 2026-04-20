<?php
declare(strict_types=1);

namespace App\Controller\Install;

use App\Service\Application\Install as InstallService;
use App\Service\Support\Csrf;
use App\Service\Support\I18n;
use App\View\View;

final class Install
{
    private View $view;
    private Csrf $csrf;
    private InstallService $installService;

    public function __construct(View $view, Csrf $csrf, InstallService $installService)
    {
        $this->view = $view;
        $this->csrf = $csrf;
        $this->installService = $installService;

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

    public function formDb(): void
    {
        $state = $_SESSION['install'] ?? [];
        $this->applyInstallLocale($state);

        $this->view->render('install/layout', 'install/step-db', [
            'pageTitle' => I18n::t('install.page_database'),
            'errors' => (array)($state['errors_db'] ?? []),
            'old' => (array)($state['db'] ?? ['db_host' => '127.0.0.1', 'db_name' => '', 'db_user' => '', 'db_pass' => '', 'db_prefix' => 'tiny_']),
            'message' => (string)($state['message'] ?? ''),
        ]);

        unset($_SESSION['install']['errors_db'], $_SESSION['install']['message']);
    }

    public function submitDb(callable $redirect): void
    {
        $this->applyInstallLocale($_SESSION['install'] ?? []);

        if (!$this->csrf->verify((string)($_POST['_csrf'] ?? ''))) {
            $_SESSION['install']['message'] = I18n::t('common.invalid_csrf');
            $_SESSION['install']['db_valid'] = false;
            $redirect('install/db');
        }

        $result = $this->installService->validateDatabaseInput($_POST);

        if ($result['errors'] !== []) {
            $_SESSION['install']['errors_db'] = $result['errors'];
            $_SESSION['install']['db'] = $result['values'];
            $_SESSION['install']['db_valid'] = false;
            $redirect('install/db');
        }

        $prefixError = $this->installService->canInstallOnPrefix($result['values']);

        if ($prefixError !== null) {
            $_SESSION['install']['errors_db'] = ['db' => $prefixError];
            $_SESSION['install']['db'] = $result['values'];
            $_SESSION['install']['db_valid'] = false;
            $redirect('install/db');
        }

        $_SESSION['install']['db'] = $result['values'];
        $_SESSION['install']['db_valid'] = true;
        $_SESSION['install']['errors_admin'] = [];
        $_SESSION['install']['admin'] = ['name' => '', 'email' => '', 'password' => '', 'website_url' => ''];

        $redirect('install/admin');
    }

    public function formAdmin(callable $redirect): void
    {
        if (!isset($_SESSION['install']['db']) || ($_SESSION['install']['db_valid'] ?? false) !== true) {
            $redirect('install/db');
        }

        $state = $_SESSION['install'] ?? [];
        $this->applyInstallLocale($state);

        $this->view->render('install/layout', 'install/step-admin', [
            'pageTitle' => I18n::t('install.page_admin'),
            'errors' => (array)($state['errors_admin'] ?? []),
            'old' => (array)($state['admin'] ?? ['name' => '', 'email' => '', 'password' => '', 'website_url' => '']),
            'message' => (string)($state['message'] ?? ''),
        ]);

        unset($_SESSION['install']['errors_admin'], $_SESSION['install']['message']);
    }

    public function submitAdmin(callable $redirect): void
    {
        if (!isset($_SESSION['install']['db']) || ($_SESSION['install']['db_valid'] ?? false) !== true) {
            $redirect('install/db');
        }

        $this->applyInstallLocale($_SESSION['install'] ?? []);

        if (!$this->csrf->verify((string)($_POST['_csrf'] ?? ''))) {
            $_SESSION['install']['message'] = I18n::t('common.invalid_csrf');
            $redirect('install/admin');
        }

        $result = $this->installService->validateAdminInput($_POST);

        if ($result['errors'] !== []) {
            $_SESSION['install']['errors_admin'] = $result['errors'];
            $_SESSION['install']['admin'] = $result['values'];
            $redirect('install/admin');
        }

        $_SESSION['install']['admin'] = $result['values'];

        $install = $this->installService->install(
            (array)$_SESSION['install']['db'],
            (array)$_SESSION['install']['admin'],
            (string)($_SESSION['install']['lang'] ?? APP_LANG)
        );

        if (($install['success'] ?? false) !== true) {
            $_SESSION['install']['message'] = (string)($install['message'] ?? I18n::t('install.failed'));
            $redirect('install/admin');
        }

        $_SESSION['install']['done'] = true;
        $redirect('install/done');
    }

    public function done(callable $redirect): void
    {
        if (!isset($_SESSION['install']['done']) || $_SESSION['install']['done'] !== true) {
            $redirect('install/db');
        }

        $this->applyInstallLocale($_SESSION['install'] ?? []);
        unset($_SESSION['install']);

        $this->view->render('install/layout', 'install/done', [
            'pageTitle' => I18n::t('install.page_done'),
        ]);
    }

    public function formLanguage(): void
    {
        $state = $_SESSION['install'] ?? [];
        $selected = (string)($state['lang'] ?? APP_LANG);
        I18n::setLocale($selected);
        $locales = I18n::availableLocales();
        $localeLabels = [];
        foreach ($locales as $locale) {
            $localeLabels[$locale] = I18n::languageLabel($locale);
        }

        $this->view->render('install/layout', 'install/step-language', [
            'pageTitle' => I18n::t('install.page_language'),
            'message' => (string)($state['message'] ?? ''),
            'selectedLang' => $selected,
            'locales' => $locales,
            'localeLabels' => $localeLabels,
        ]);

        unset($_SESSION['install']['message']);
    }

    public function submitLanguage(callable $redirect): void
    {
        if (!$this->csrf->verify((string)($_POST['_csrf'] ?? ''))) {
            $_SESSION['install']['message'] = I18n::t('common.invalid_csrf');
            $redirect('install');
        }

        $lang = strtolower(trim((string)($_POST['lang'] ?? APP_LANG)));
        $locales = I18n::availableLocales();
        if (!in_array($lang, $locales, true)) {
            $lang = (string)APP_LANG;
        }

        $_SESSION['install']['lang'] = $lang;
        I18n::setLocale($lang);
        $redirect('install/db');
    }

    private function applyInstallLocale(array $state): void
    {
        I18n::setLocale((string)($state['lang'] ?? APP_LANG));
    }
}
