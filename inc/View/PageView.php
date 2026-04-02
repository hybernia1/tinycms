<?php
declare(strict_types=1);

namespace App\View;

use App\Service\SettingsService;

final class PageView
{
    private View $view;
    private SettingsService $settings;

    public function __construct(View $view, SettingsService $settings)
    {
        $this->view = $view;
        $this->settings = $settings;
    }

    public function home(?array $user, array $site): void
    {
        $siteName = (string)($site['name'] ?? 'TinyCMS');
        $this->view->render('front', 'front/index', [
            'user' => $user,
            'siteName' => $siteName,
            'siteFooter' => (string)($site['footer'] ?? '© TinyCMS'),
            'siteAuthor' => (string)($site['author'] ?? 'Admin'),
            'theme' => $this->theme(),
            'pageTitle' => $siteName,
        ]);
    }

    public function loginForm(array $state): void
    {
        $state['pageTitle'] = 'Login';
        $state['theme'] = $this->theme();
        $this->view->render('login', 'login/form', $state);
    }

    public function adminDashboard(?array $user): void
    {
        $this->view->render('admin', 'admin/dashboard', [
            'user' => $user,
            'theme' => $this->theme(),
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
            'theme' => $this->theme(),
            'pageTitle' => 'Uživatelé',
        ]);
    }

    public function adminSettingsForm(array $groups, array $values, string $activeGroup): void
    {
        $this->view->render('admin', 'admin/settings/form', [
            'groups' => $groups,
            'values' => $values,
            'activeGroup' => $activeGroup,
            'theme' => $this->theme(),
            'pageTitle' => 'Nastavení',
        ]);
    }

    public function adminUsersForm(string $mode, array $user, array $errors): void
    {
        $this->view->render('admin', 'admin/users/form', [
            'mode' => $mode,
            'user' => $user,
            'errors' => $errors,
            'theme' => $this->theme(),
            'pageTitle' => $mode === 'add' ? 'Přidat uživatele' : 'Upravit uživatele',
        ]);
    }

    private function theme(): string
    {
        $settings = $this->settings->resolved();
        $theme = (string)($settings['custom']['theme'] ?? 'light');

        return in_array($theme, ['light', 'dark'], true) ? $theme : 'light';
    }
}
