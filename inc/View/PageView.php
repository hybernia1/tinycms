<?php
declare(strict_types=1);

namespace App\View;

use App\Service\ContentTypeService;
use App\Service\SettingsService;

final class PageView
{
    private View $view;
    private SettingsService $settings;
    private ContentTypeService $contentTypes;

    public function __construct(View $view, SettingsService $settings, ContentTypeService $contentTypes)
    {
        $this->view = $view;
        $this->settings = $settings;
        $this->contentTypes = $contentTypes;
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
            'adminMenu' => $this->adminMenu(),
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
            'adminMenu' => $this->adminMenu(),
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
            'adminMenu' => $this->adminMenu(),
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
            'adminMenu' => $this->adminMenu(),
            'theme' => $this->theme(),
            'pageTitle' => $mode === 'add' ? 'Přidat uživatele' : 'Upravit uživatele',
        ]);
    }

    public function adminContentList(array $pagination, array $allowedPerPage, string $status, string $query, array $contentType, array $availableStatuses): void
    {
        $this->view->render('admin', 'admin/content/list', [
            'pagination' => $pagination,
            'allowedPerPage' => $allowedPerPage,
            'status' => $status,
            'query' => $query,
            'contentType' => $contentType,
            'availableStatuses' => $availableStatuses,
            'currentContentType' => $contentType,
            'adminMenu' => $this->adminMenu(),
            'theme' => $this->theme(),
            'pageTitle' => (string)($contentType['label_plural'] ?? 'Content'),
        ]);
    }

    public function adminContentForm(string $mode, array $item, array $errors, array $contentType, array $availableStatuses): void
    {
        $this->view->render('admin', 'admin/content/form', [
            'mode' => $mode,
            'item' => $item,
            'errors' => $errors,
            'contentType' => $contentType,
            'availableStatuses' => $availableStatuses,
            'currentContentType' => $contentType,
            'adminMenu' => $this->adminMenu(),
            'theme' => $this->theme(),
            'pageTitle' => $mode === 'add'
                ? 'Přidat ' . (string)($contentType['label_singular'] ?? 'obsah')
                : 'Upravit ' . (string)($contentType['label_singular'] ?? 'obsah'),
        ]);
    }

    private function theme(): string
    {
        $settings = $this->settings->resolved();
        $theme = (string)($settings['custom']['theme'] ?? 'light');

        return in_array($theme, ['light', 'dark'], true) ? $theme : 'light';
    }

    private function adminMenu(): array
    {
        $items = [
            ['label' => 'Dashboard', 'url' => 'admin/dashboard'],
            ['label' => 'Uživatelé', 'url' => 'admin/users'],
        ];

        foreach ($this->contentTypes->all() as $type) {
            $items[] = [
                'label' => (string)($type['label_plural'] ?? 'Content'),
                'url' => 'admin/content?type=' . urlencode((string)($type['type'] ?? 'post')),
            ];
        }

        $items[] = ['label' => 'Nastavení', 'url' => 'admin/settings'];

        return $items;
    }
}
