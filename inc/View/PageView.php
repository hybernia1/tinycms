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

    public function home(?array $user, array $site, array $posts = []): void
    {
        $siteName = (string)($site['name'] ?? 'TinyCMS');
        $this->view->render('front', 'front/index', [
            'user' => $user,
            'posts' => $posts,
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

    public function contentDetail(array $item): void
    {
        $this->view->render('front', 'front/content', [
            'item' => $item,
            'pageTitle' => (string)($item['name'] ?? 'Obsah'),
        ]);
    }

    public function adminDashboard(?array $user): void
    {
        $this->view->render('admin', 'admin/dashboard', [
            'user' => $user,
            'adminMenu' => $this->adminMenu(),
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
            'adminMenu' => $this->adminMenu(),
            'pageTitle' => 'Uživatelé',
        ]);
    }

    public function adminSettingsForm(array $fields, array $values): void
    {
        $this->view->render('admin', 'admin/settings/form', [
            'fields' => $fields,
            'values' => $values,
            'adminMenu' => $this->adminMenu(),
            'pageTitle' => 'Nastavení',
        ]);
    }

    public function adminUsersForm(string $mode, array $user, array $errors): void
    {
        $this->view->render('admin', 'admin/users/form', [
            'mode' => $mode,
            'user' => $user,
            'errors' => $errors,
            'adminMenu' => $this->adminMenu(),
            'pageTitle' => $mode === 'add' ? 'Přidat uživatele' : 'Upravit uživatele',
        ]);
    }

    public function adminContentList(array $pagination, array $allowedPerPage, string $status, string $query, array $availableStatuses): void
    {
        $this->view->render('admin', 'admin/content/list', [
            'pagination' => $pagination,
            'allowedPerPage' => $allowedPerPage,
            'status' => $status,
            'query' => $query,
            'availableStatuses' => $availableStatuses,
            'adminMenu' => $this->adminMenu(),
            'pageTitle' => 'Obsah',
        ]);
    }

    public function adminContentForm(string $mode, array $item, array $errors, array $availableStatuses, array $authors): void
    {
        $this->view->render('admin', 'admin/content/form', [
            'mode' => $mode,
            'item' => $item,
            'errors' => $errors,
            'availableStatuses' => $availableStatuses,
            'authors' => $authors,
            'adminMenu' => $this->adminMenu(),
            'pageTitle' => $mode === 'add' ? 'Přidat obsah' : 'Upravit obsah',
        ]);
    }

    private function adminMenu(): array
    {
        return [
            ['label' => 'Dashboard', 'url' => 'admin/dashboard'],
            ['label' => 'Uživatelé', 'url' => 'admin/users'],
            ['label' => 'Obsah', 'url' => 'admin/content'],
            ['label' => 'Nastavení', 'url' => 'admin/settings'],
        ];
    }
}
