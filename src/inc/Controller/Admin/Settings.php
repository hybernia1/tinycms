<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\Application\Auth;
use App\Service\Application\Settings as SettingsService;
use App\Service\Support\Csrf;
use App\Service\Support\Flash;
use App\View\AdminView;

final class Settings extends Admin
{
    public function __construct(
        private AdminView $pages,
        Auth $authService,
        private SettingsService $settings,
        Flash $flash,
        Csrf $csrf
    ) {
        parent::__construct($authService, $flash, $csrf);
    }

    public function form(callable $redirect): void
    {
        if (!$this->guardAdmin($redirect, false)) {
            return;
        }

        $resolved = $this->settings->resolved();
        $this->pages->adminSettingsForm($this->settings->fields(), $resolved);
    }
}
