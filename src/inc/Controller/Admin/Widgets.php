<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\Application\Auth;
use App\Service\Application\Widgets as WidgetsService;
use App\Service\Support\Csrf;
use App\Service\Support\Flash;
use App\View\AdminView;

final class Widgets extends Admin
{
    public function __construct(
        private AdminView $pages,
        Auth $authService,
        private WidgetsService $widgets,
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

        $this->widgets->boot(BASE_DIR);
        $this->pages->adminWidgetsForm($this->widgets->payload());
    }
}
