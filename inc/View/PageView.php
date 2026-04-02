<?php
declare(strict_types=1);

namespace App\View;

final class PageView
{
    private View $view;

    public function __construct(View $view)
    {
        $this->view = $view;
    }

    public function home(?array $user): void
    {
        $this->view->render('front', 'front/index', [
            'user' => $user,
        ]);
    }

    public function loginForm(array $state): void
    {
        $this->view->render('login', 'login/form', $state);
    }

    public function adminDashboard(?array $user): void
    {
        $this->view->render('admin', 'admin/dashboard', [
            'user' => $user,
        ]);
    }
}
