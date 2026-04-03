<?php
declare(strict_types=1);

namespace App\View;

use App\Service\ContentTypeService;
use App\Service\DateTimeService;
use App\Service\SettingsService;

final class PageView
{
    private View $view;
    private SettingsService $settings;
    private ContentTypeService $contentTypes;
    private DateTimeService $dateTime;

    public function __construct(View $view, SettingsService $settings, ContentTypeService $contentTypes, DateTimeService $dateTime)
    {
        $this->view = $view;
        $this->settings = $settings;
        $this->contentTypes = $contentTypes;
        $this->dateTime = $dateTime;
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
            'theme' => $this->theme(),
            'pageTitle' => $siteName,
            'dateTime' => $this->dateTime,
            'siteTitle' => $this->siteTitle(),
        ]);
    }

    public function loginForm(array $state): void
    {
        $state['pageTitle'] = 'Login';
        $state['theme'] = $this->theme();
        $state['siteTitle'] = $this->siteTitle();
        $this->view->render('login', 'login/form', $state);
    }

    public function contentDetail(array $item): void
    {
        $this->view->render('front', 'front/content', [
            'item' => $item,
            'theme' => $this->theme(),
            'pageTitle' => (string)($item['name'] ?? 'Obsah'),
            'dateTime' => $this->dateTime,
            'siteTitle' => $this->siteTitle(),
        ]);
    }

    public function adminDashboard(?array $user): void
    {
        $this->view->render('admin', 'admin/dashboard', [
            'user' => $user,
            'adminMenu' => $this->adminMenu(),
            'theme' => $this->theme(),
            'pageTitle' => 'Dashboard',
            'dateTime' => $this->dateTime,
            'siteTitle' => $this->siteTitle(),
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
            'dateTime' => $this->dateTime,
            'siteTitle' => $this->siteTitle(),
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
            'dateTime' => $this->dateTime,
            'siteTitle' => $this->siteTitle(),
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
            'dateTime' => $this->dateTime,
            'siteTitle' => $this->siteTitle(),
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
            'dateTime' => $this->dateTime,
            'siteTitle' => $this->siteTitle(),
        ]);
    }

    public function adminContentForm(string $mode, array $item, array $errors, array $contentType, array $availableStatuses, array $authors): void
    {
        $this->view->render('admin', 'admin/content/form', [
            'mode' => $mode,
            'item' => $item,
            'errors' => $errors,
            'contentType' => $contentType,
            'availableStatuses' => $availableStatuses,
            'authors' => $authors,
            'currentContentType' => $contentType,
            'adminMenu' => $this->adminMenu(),
            'theme' => $this->theme(),
            'pageTitle' => $mode === 'add'
                ? 'Přidat ' . (string)($contentType['label_singular'] ?? 'obsah')
                : 'Upravit ' . (string)($contentType['label_singular'] ?? 'obsah'),
            'dateTime' => $this->dateTime,
            'siteTitle' => $this->siteTitle(),
        ]);
    }

    private function theme(): string
    {
        $settings = $this->settings->resolved();
        $theme = (string)($settings['custom']['theme'] ?? 'light');

        return in_array($theme, ['light', 'dark'], true) ? $theme : 'light';
    }

    private function siteTitle(): string
    {
        $settings = $this->settings->resolved();
        return (string)($settings['main']['sitename'] ?? 'TinyCMS');
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
