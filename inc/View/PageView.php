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

    public function home(?array $user, array $site): void
    {
        $siteName = (string)($site['name'] ?? 'TinyCMS');
        $this->view->render('front', 'front/index', [
            'user' => $user,
            'siteName' => $siteName,
            'siteFooter' => (string)($site['footer'] ?? '© TinyCMS'),
            'siteAuthor' => (string)($site['author'] ?? 'Admin'),
            'pageTitle' => $siteName,
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

    public function adminUsersList(array $pagination, array $allowedPerPage, string $status, string $query): void
    {
        $this->view->render('admin', 'admin/users/list', [
            'pagination' => $pagination,
            'allowedPerPage' => $allowedPerPage,
            'status' => $status,
            'query' => $query,
            'pageTitle' => 'Uživatelé',
        ]);
    }


    public function adminSettingsForm(array $groups, array $values, string $activeGroup): void
    {
        $this->view->render('admin', 'admin/settings/form', [
            'groups' => $groups,
            'values' => $values,
            'activeGroup' => $activeGroup,
            'pageTitle' => 'Nastavení',
        ]);
    }
    public function adminUsersForm(string $mode, array $user, array $errors): void
    {
        $this->view->render('admin', 'admin/users/form', [
            'mode' => $mode,
            'user' => $user,
            'errors' => $errors,
            'pageTitle' => $mode === 'add' ? 'Přidat uživatele' : 'Upravit uživatele',
        ]);
    }
}
