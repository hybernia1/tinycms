<?php
declare(strict_types=1);

namespace App\View;

use App\Service\Feature\ThemeService;
use App\Service\Support\I18n;

final class PageView
{
    private View $view;
    private ThemeService $themes;

    public function __construct(View $view, ThemeService $themes)
    {
        $this->view = $view;
        $this->themes = $themes;
    }

    public function home(?array $user, array $site, array $posts = []): void
    {
        $siteName = (string)($site['name'] ?? 'TinyCMS');
        $theme = $this->themes->resolveTheme((string)($site['theme'] ?? 'default'));
        $this->view->renderTheme($theme, 'index', [
            'user' => $user,
            'posts' => $posts,
            'siteName' => $siteName,
            'siteFooter' => (string)($site['footer'] ?? '© TinyCMS'),
            'siteAuthor' => (string)($site['author'] ?? 'Admin'),
            'themeName' => $theme,
            'pageTitle' => $siteName,
        ]);
    }

    public function loginForm(array $state): void
    {
        $state['pageTitle'] = 'Login';
        $this->view->render('front/layout', 'front/auth/login', $state);
    }

    public function contentDetail(array $item, string $theme): void
    {
        $resolvedTheme = $this->themes->resolveTheme($theme);
        $this->view->renderTheme($resolvedTheme, 'content', [
            'item' => $item,
            'themeName' => $resolvedTheme,
            'pageTitle' => (string)($item['name'] ?? I18n::t('admin.menu.content', 'Content')),
        ]);
    }

    public function termArchive(array $term, array $posts, array $pagination, string $theme): void
    {
        $resolvedTheme = $this->themes->resolveTheme($theme);
        $this->view->renderTheme($resolvedTheme, 'terms', [
            'term' => $term,
            'posts' => $posts,
            'pagination' => $pagination,
            'themeName' => $resolvedTheme,
            'pageTitle' => (string)($term['name'] ?? I18n::t('admin.menu.terms', 'Tags')),
        ]);
    }

    public function adminDashboard(?array $user): void
    {
        $this->view->render('admin/layout', 'admin/dashboard', [
            'user' => $user,
            'adminMenu' => $this->adminMenu(),
            'pageTitle' => I18n::t('admin.menu.dashboard', 'Dashboard'),
        ]);
    }

    public function adminUsersList(array $pagination, array $allowedPerPage, string $status, string $query): void
    {
        $this->view->render('admin/layout', 'admin/users/list', [
            'pagination' => $pagination,
            'allowedPerPage' => $allowedPerPage,
            'status' => $status,
            'query' => $query,
            'adminMenu' => $this->adminMenu(),
            'pageTitle' => I18n::t('admin.menu.users', 'Users'),
        ]);
    }

    public function adminSettingsForm(array $fields, array $values): void
    {
        $this->view->render('admin/layout', 'admin/settings/form', [
            'fields' => $fields,
            'values' => $values,
            'adminMenu' => $this->adminMenu(),
            'pageTitle' => I18n::t('admin.menu.settings', 'Settings'),
        ]);
    }

    public function adminUsersForm(string $mode, array $user, array $errors): void
    {
        $this->view->render('admin/layout', 'admin/users/form', [
            'mode' => $mode,
            'user' => $user,
            'errors' => $errors,
            'adminMenu' => $this->adminMenu(),
            'pageTitle' => $mode === 'add' ? I18n::t('admin.add_user', 'Add user') : I18n::t('admin.edit_user', 'Edit user'),
        ]);
    }

    public function adminContentList(array $pagination, array $allowedPerPage, string $status, string $query, array $availableStatuses): void
    {
        $this->view->render('admin/layout', 'admin/content/list', [
            'pagination' => $pagination,
            'allowedPerPage' => $allowedPerPage,
            'status' => $status,
            'query' => $query,
            'availableStatuses' => $availableStatuses,
            'adminMenu' => $this->adminMenu(),
            'pageTitle' => I18n::t('admin.menu.content', 'Content'),
        ]);
    }

    public function adminContentForm(string $mode, array $item, array $errors, array $availableStatuses, array $authors, array $selectedTerms = []): void
    {
        $this->view->render('admin/layout', 'admin/content/form', [
            'mode' => $mode,
            'item' => $item,
            'errors' => $errors,
            'availableStatuses' => $availableStatuses,
            'authors' => $authors,
            'selectedTerms' => $selectedTerms,
            'adminMenu' => $this->adminMenu(),
            'pageTitle' => $mode === 'add' ? I18n::t('admin.add_content', 'Add content') : I18n::t('admin.edit_content', 'Edit content'),
        ]);
    }

    public function adminTermList(array $pagination, array $allowedPerPage, string $query): void
    {
        $this->view->render('admin/layout', 'admin/terms/list', [
            'pagination' => $pagination,
            'allowedPerPage' => $allowedPerPage,
            'query' => $query,
            'adminMenu' => $this->adminMenu(),
            'pageTitle' => I18n::t('admin.menu.terms', 'Tags'),
        ]);
    }

    public function adminTermForm(string $mode, array $item, array $errors): void
    {
        $this->view->render('admin/layout', 'admin/terms/form', [
            'mode' => $mode,
            'item' => $item,
            'errors' => $errors,
            'adminMenu' => $this->adminMenu(),
            'pageTitle' => $mode === 'add' ? I18n::t('admin.add_term', 'Add tag') : I18n::t('admin.edit_term', 'Edit tag'),
        ]);
    }

    public function adminMediaList(array $pagination, array $allowedPerPage, string $query): void
    {
        $this->view->render('admin/layout', 'admin/media/list', [
            'pagination' => $pagination,
            'allowedPerPage' => $allowedPerPage,
            'query' => $query,
            'adminMenu' => $this->adminMenu(),
            'pageTitle' => I18n::t('admin.menu.media', 'Media'),
        ]);
    }

    public function adminMediaForm(string $mode, array $item, array $errors, array $authors, array $usages = []): void
    {
        $this->view->render('admin/layout', 'admin/media/form', [
            'mode' => $mode,
            'item' => $item,
            'errors' => $errors,
            'authors' => $authors,
            'usages' => $usages,
            'adminMenu' => $this->adminMenu(),
            'pageTitle' => $mode === 'add' ? I18n::t('admin.add_media', 'Add media') : I18n::t('admin.edit_media', 'Edit media'),
        ]);
    }

    private function adminMenu(): array
    {
        return [
            ['label' => I18n::t('admin.menu.dashboard', 'Dashboard'), 'url' => 'admin/dashboard'],
            ['label' => I18n::t('admin.menu.users', 'Users'), 'url' => 'admin/users'],
            ['label' => I18n::t('admin.menu.content', 'Content'), 'url' => 'admin/content'],
            ['label' => I18n::t('admin.menu.media', 'Media'), 'url' => 'admin/media'],
            ['label' => I18n::t('admin.menu.terms', 'Tags'), 'url' => 'admin/terms'],
            ['label' => I18n::t('admin.menu.settings', 'Settings'), 'url' => 'admin/settings'],
        ];
    }
}
