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

        $groups = $this->settings->groups();
        $activeGroup = (string)($_GET['group'] ?? array_key_first($groups));

        if (!isset($groups[$activeGroup])) {
            $activeGroup = (string)array_key_first($groups);
        }

        $values = array_replace_recursive($this->settings->defaults(), $this->settings->values());
        $this->pages->adminSettingsForm($groups, $values, $activeGroup);
    }

    public function submit(callable $redirect): void
    {
        if (!$this->guard($redirect)) {
            return;
        }

        $group = trim((string)($_POST['group'] ?? ''));

        if (!$this->csrf->verify((string)($_POST['_csrf'] ?? ''))) {
            $this->flash->add('error', 'Bezpečnostní token vypršel, odešlete formulář znovu.');
            $redirect('admin/settings?group=' . urlencode($group));
        }

        $this->settings->save((array)($_POST['settings'] ?? []));
        $this->flash->add('success', 'Nastavení uloženo.');
        $redirect('admin/settings?group=' . urlencode($group));
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
