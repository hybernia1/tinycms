<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\Application\Auth;
use App\Service\Application\Widget as WidgetService;
use App\Service\Support\Csrf;
use App\Service\Support\Flash;
use App\View\AdminView;

final class Widget extends Admin
{
    public function __construct(
        private AdminView $pages,
        Auth $authService,
        private WidgetService $widgets,
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

        $this->pages->adminWidgetForm($this->widgets->items(), $this->widgets->definitions(), $this->widgets->areas(), $this->widgets->areaLabels());
    }
}
