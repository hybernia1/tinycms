<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\Application\Auth;
use App\Service\Application\Theme as ThemeService;
use App\Service\Support\Csrf;
use App\Service\Support\Flash;
use App\View\AdminView;

final class Theme extends Admin
{
    public function __construct(
        private AdminView $pages,
        Auth $authService,
        private ThemeService $themes,
        Flash $flash,
        Csrf $csrf
    ) {
        parent::__construct($authService, $flash, $csrf);
    }

    public function form(callable $redirect): void
    {
        $this->renderForm($redirect, 'overview');
    }

    public function sectionForm(callable $redirect, string $section): void
    {
        $this->renderForm($redirect, $section);
    }

    private function renderForm(callable $redirect, string $section): void
    {
        if (!$this->guardAdmin($redirect, false)) {
            return;
        }

        $section = strtolower(trim($section));
        if (!in_array($section, ['overview', 'settings'], true)) {
            $redirect('admin/themes');
            return;
        }

        $this->pages->adminThemeForm(
            $this->themes->themes(),
            $this->themes->active(),
            $this->themes->resolved(),
            $this->themes->fields(),
            $section
        );
    }
}
