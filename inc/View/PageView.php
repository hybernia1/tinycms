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
            'pageTitle' => 'TinyCMS',
        ]);
    }

    public function loginForm(array $state): void
    {
        $state['pageTitle'] = 'Login';
        $this->view->render('login', 'login/form', $state);
    }

    public function adminDashboard(?array $user): void
    {
        $this->view->render('admin', 'admin/dashboard', [
            'user' => $user,
            'pageTitle' => 'Dashboard',
        ]);
    }

    public function adminUsersList(array $users, string $status): void
    {
        $this->view->render('admin', 'admin/users/list', [
            'users' => $users,
            'status' => $status,
            'pageTitle' => 'Uživatelé',
        ]);
    }

    public function adminUsersForm(string $mode, array $user, array $errors, string $message): void
    {
        $this->view->render('admin', 'admin/users/form', [
            'mode' => $mode,
            'user' => $user,
            'errors' => $errors,
            'message' => $message,
            'pageTitle' => $mode === 'add' ? 'Přidat uživatele' : 'Upravit uživatele',
        ]);
    }
}
