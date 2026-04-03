<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\AuthService;
use App\Service\CsrfService;
use App\Service\FlashService;
use App\Service\SettingsService;
use App\View\PageView;

final class AdminSettingsController
{
    private PageView $pages;
    private AuthService $authService;
    private SettingsService $settings;
    private FlashService $flash;
    private CsrfService $csrf;

    public function __construct(PageView $pages, AuthService $authService, SettingsService $settings, FlashService $flash, CsrfService $csrf)
    {
        $this->pages = $pages;
        $this->authService = $authService;
        $this->settings = $settings;
        $this->flash = $flash;
        $this->csrf = $csrf;
    }

    public function form(callable $redirect): void
    {
        if (!$this->guard($redirect)) {
            return;
        }

        $fields = $this->settings->fields();
        $values = array_replace($this->settings->defaults(), $this->settings->values());
        $this->pages->adminSettingsForm($fields, $values);
    }

    public function submit(callable $redirect): void
    {
        if (!$this->guard($redirect)) {
            return;
        }

        if (!$this->csrf->verify((string)($_POST['_csrf'] ?? ''))) {
            $this->flash->add('error', 'Bezpečnostní token vypršel, odešlete formulář znovu.');
            $redirect('admin/settings');
        }

        $this->settings->save((array)($_POST['settings'] ?? []));
        $this->flash->add('success', 'Nastavení uloženo.');
        $redirect('admin/settings');
    }

    private function guard(callable $redirect): bool
    {
        if (!$this->authService->auth()->check()) {
            $redirect('login');
            return false;
        }

        if (!$this->authService->canAccessAdmin()) {
            $this->flash->add('info', 'Nemáte přístup do administrace.');
            $redirect('');
            return false;
        }

        return true;
    }
}
