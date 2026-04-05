<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\Feature\AuthService;
use App\Service\Support\CsrfService;
use App\Service\Support\FlashService;
use App\Service\Feature\SettingsService;
use App\Service\Support\I18n;
use App\View\PageView;

final class AdminSettingsController extends BaseAdminController
{
    private PageView $pages;
    private SettingsService $settings;

    public function __construct(PageView $pages, AuthService $authService, SettingsService $settings, FlashService $flash, CsrfService $csrf)
    {
        parent::__construct($authService, $flash, $csrf);
        $this->pages = $pages;
        $this->settings = $settings;
    }

    public function form(callable $redirect): void
    {
        if (!$this->guardAdmin($redirect)) {
            return;
        }

        $fields = $this->settings->fields();
        $values = array_replace($this->settings->defaults(), $this->settings->values());
        $this->pages->adminSettingsForm($fields, $values);
    }

    public function submit(callable $redirect): void
    {
        if (
            !$this->guardAdmin($redirect)
            || !$this->guardCsrf($redirect, 'admin/settings', I18n::t('common.csrf_expired'))
        ) {
            return;
        }

        $this->settings->save((array)($_POST['settings'] ?? []));
        $this->flash->add('success', I18n::t('settings.saved', 'Settings saved.'));
        $redirect('admin/settings');
    }
}
