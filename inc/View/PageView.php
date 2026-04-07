<?php
declare(strict_types=1);

namespace App\View;

use App\Service\Feature\ThemeService;
use App\Service\Feature\SettingsService;
use App\Service\Support\I18n;

final class PageView
{
    private View $view;
    private ThemeService $themes;
    private SettingsService $settings;

    public function __construct(View $view, ThemeService $themes, SettingsService $settings)
    {
        $this->view = $view;
        $this->themes = $themes;
        $this->settings = $settings;
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
            'siteFavicon' => (string)($site['favicon'] ?? ''),
            'siteLogo' => (string)($site['logo'] ?? ''),
            'themeName' => $theme,
            'pageTitle' => $siteName,
            'metaTitle' => (string)($site['meta_title'] ?? $siteName),
            'metaDescription' => (string)($site['meta_description'] ?? ''),
            'metaKeywords' => [(string)($site['name'] ?? 'TinyCMS')],
            'metaPath' => '',
            'metaOgType' => 'website',
            'metaSearchUrlTemplate' => 'search?q={search_term_string}',
            'metaAlternateLinks' => [
                [
                    'rel' => 'alternate',
                    'type' => 'application/rss+xml',
                    'title' => I18n::t('front.feed.title', 'RSS feed'),
                    'href' => 'feed',
                ],
            ],
        ]);
    }

    public function loginForm(array $state): void
    {
        $state['pageTitle'] = 'Login';
        $this->view->render('front/layout', 'front/auth/login', $state);
    }

    public function contentDetail(array $item, string $theme, array $site = []): void
    {
        $resolvedTheme = $this->themes->resolveTheme($theme);
        $terms = array_map(static fn(array $term): string => trim((string)($term['name'] ?? '')), (array)($item['terms'] ?? []));
        $thumb = (array)($item['thumbnail'] ?? []);
        $ogImage = trim((string)($thumb['path'] ?? ''));
        if ($ogImage === '') {
            $ogImage = trim((string)($thumb['webp'] ?? ''));
        }
        $this->view->renderTheme($resolvedTheme, 'content', [
            'item' => $item,
            'themeName' => $resolvedTheme,
            'siteName' => (string)($site['name'] ?? 'TinyCMS'),
            'siteAuthor' => (string)($site['author'] ?? 'Admin'),
            'siteFooter' => (string)($site['footer'] ?? '© TinyCMS'),
            'siteFavicon' => (string)($site['favicon'] ?? ''),
            'siteLogo' => (string)($site['logo'] ?? ''),
            'pageTitle' => (string)($item['name'] ?? I18n::t('admin.menu.content', 'Content')),
            'metaTitle' => (string)($item['name'] ?? I18n::t('admin.menu.content', 'Content')),
            'metaDescription' => (string)($item['excerpt'] ?? ''),
            'metaKeywords' => array_values(array_filter($terms, static fn(string $term): bool => $term !== '')),
            'metaPath' => (string)($item['slug'] ?? ''),
            'shortlinkPath' => (string)($item['id'] ?? ''),
            'metaOgType' => 'article',
            'metaOgImage' => $ogImage,
            'metaPublishedTime' => (string)($item['created'] ?? ''),
        ]);
    }

    public function termArchive(array $term, array $posts, array $pagination, string $theme, array $site = []): void
    {
        $resolvedTheme = $this->themes->resolveTheme($theme);
        $termSlug = (string)($term['slug'] ?? '');
        $termName = (string)($term['name'] ?? '');
        $this->view->renderTheme($resolvedTheme, 'terms', [
            'term' => $term,
            'posts' => $posts,
            'pagination' => $pagination,
            'themeName' => $resolvedTheme,
            'siteName' => (string)($site['name'] ?? 'TinyCMS'),
            'siteAuthor' => (string)($site['author'] ?? 'Admin'),
            'siteFooter' => (string)($site['footer'] ?? '© TinyCMS'),
            'siteFavicon' => (string)($site['favicon'] ?? ''),
            'siteLogo' => (string)($site['logo'] ?? ''),
            'pageTitle' => $termName !== '' ? $termName : I18n::t('admin.menu.terms', 'Tags'),
            'metaTitle' => $termName !== '' ? $termName : I18n::t('admin.menu.terms', 'Tags'),
            'metaDescription' => I18n::t('front.term.meta_description_prefix', 'Articles on topic') . ': ' . $termName,
            'metaKeywords' => [$termName],
            'metaPath' => $termSlug !== '' ? 'term/' . $termSlug : '',
            'metaOgType' => 'website',
            'metaSearchUrlTemplate' => 'search?q={search_term_string}',
            'metaAlternateLinks' => $termSlug !== '' ? [
                [
                    'rel' => 'alternate',
                    'type' => 'application/rss+xml',
                    'title' => I18n::t('front.feed.title', 'RSS feed'),
                    'href' => 'term/' . $termSlug . '/feed',
                ],
            ] : [],
        ]);
    }

    public function search(array $posts, array $pagination, string $query, string $theme, array $site = []): void
    {
        $resolvedTheme = $this->themes->resolveTheme($theme);
        $title = $query === '' ? I18n::t('front.search.title', 'Search') : I18n::t('front.search.results_for', 'Search results for') . ': ' . $query;

        $this->view->renderTheme($resolvedTheme, 'search', [
            'posts' => $posts,
            'pagination' => $pagination,
            'query' => $query,
            'themeName' => $resolvedTheme,
            'siteFooter' => (string)($site['footer'] ?? '© TinyCMS'),
            'siteFavicon' => (string)($site['favicon'] ?? ''),
            'siteLogo' => (string)($site['logo'] ?? ''),
            'siteName' => (string)($site['name'] ?? 'TinyCMS'),
            'siteAuthor' => (string)($site['author'] ?? 'Admin'),
            'pageTitle' => $title,
            'metaTitle' => $title,
            'metaDescription' => $query === ''
                ? I18n::t('front.search.meta_description', 'Search results on the website.')
                : I18n::t('front.search.meta_description_prefix', 'Search results for') . ': ' . $query,
            'metaKeywords' => $query !== '' ? [$query] : [],
            'metaPath' => $query !== '' ? 'search?q=' . rawurlencode($query) : 'search',
            'metaRobots' => 'noindex,follow',
            'metaOgType' => 'website',
            'metaSearchUrlTemplate' => 'search?q={search_term_string}',
        ]);
    }

    public function rssFeed(array $channel, array $items): void
    {
        $this->view->render('rss/layout', 'rss/feed', [
            'channel' => $channel,
            'items' => $items,
        ]);
    }

    public function adminDashboard(?array $user): void
    {
        $this->renderAdmin('admin/dashboard', [
            'user' => $user,
            'adminMenu' => $this->adminMenu(),
            'pageTitle' => I18n::t('admin.menu.dashboard', 'Dashboard'),
        ]);
    }

    public function adminUsersList(array $pagination, array $allowedPerPage, string $status, string $query): void
    {
        $this->renderAdmin('admin/users/list', [
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
        $this->renderAdmin('admin/settings/form', [
            'fields' => $fields,
            'values' => $values,
            'adminMenu' => $this->adminMenu(),
            'pageTitle' => I18n::t('admin.menu.settings', 'Settings'),
        ]);
    }

    public function adminUsersForm(string $mode, array $user, array $errors): void
    {
        $this->renderAdmin('admin/users/form', [
            'mode' => $mode,
            'user' => $user,
            'errors' => $errors,
            'adminMenu' => $this->adminMenu(),
            'pageTitle' => $mode === 'add' ? I18n::t('admin.add_user', 'Add user') : I18n::t('admin.edit_user', 'Edit user'),
        ]);
    }

    public function adminContentList(array $pagination, array $allowedPerPage, string $status, string $query, array $availableStatuses): void
    {
        $this->renderAdmin('admin/content/list', [
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
        $this->renderAdmin('admin/content/form', [
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
        $this->renderAdmin('admin/terms/list', [
            'pagination' => $pagination,
            'allowedPerPage' => $allowedPerPage,
            'query' => $query,
            'adminMenu' => $this->adminMenu(),
            'pageTitle' => I18n::t('admin.menu.terms', 'Tags'),
        ]);
    }

    public function adminTermForm(string $mode, array $item, array $errors): void
    {
        $this->renderAdmin('admin/terms/form', [
            'mode' => $mode,
            'item' => $item,
            'errors' => $errors,
            'adminMenu' => $this->adminMenu(),
            'pageTitle' => $mode === 'add' ? I18n::t('admin.add_term', 'Add tag') : I18n::t('admin.edit_term', 'Edit tag'),
        ]);
    }

    public function adminMediaList(array $pagination, array $allowedPerPage, string $query): void
    {
        $this->renderAdmin('admin/media/list', [
            'pagination' => $pagination,
            'allowedPerPage' => $allowedPerPage,
            'query' => $query,
            'adminMenu' => $this->adminMenu(),
            'pageTitle' => I18n::t('admin.menu.media', 'Media'),
        ]);
    }

    public function adminMediaForm(string $mode, array $item, array $errors, array $authors, array $usages = [], array $navigation = []): void
    {
        $this->renderAdmin('admin/media/form', [
            'mode' => $mode,
            'item' => $item,
            'errors' => $errors,
            'authors' => $authors,
            'usages' => $usages,
            'navigation' => $navigation,
            'adminMenu' => $this->adminMenu(),
            'pageTitle' => $mode === 'add' ? I18n::t('admin.add_media', 'Add media') : I18n::t('admin.edit_media', 'Edit media'),
        ]);
    }

    private function renderAdmin(string $template, array $data): void
    {
        $this->view->render('admin/layout', $template, array_merge($data, $this->adminBranding()));
    }

    private function adminBranding(): array
    {
        $settings = $this->settings->resolved();
        return [
            'siteName' => (string)($settings['sitename'] ?? 'TinyCMS'),
            'siteFavicon' => (string)($settings['favicon'] ?? ''),
            'siteLogo' => (string)($settings['logo'] ?? ''),
        ];
    }

    private function adminMenu(): array
    {
        return [
            ['label' => I18n::t('admin.menu.dashboard', 'Dashboard'), 'url' => 'admin/dashboard', 'icon' => 'dashboard'],
            ['label' => I18n::t('admin.menu.users', 'Users'), 'url' => 'admin/users', 'icon' => 'users'],
            ['label' => I18n::t('admin.menu.content', 'Content'), 'url' => 'admin/content', 'icon' => 'content'],
            ['label' => I18n::t('admin.menu.media', 'Media'), 'url' => 'admin/media', 'icon' => 'media'],
            ['label' => I18n::t('admin.menu.terms', 'Tags'), 'url' => 'admin/terms', 'icon' => 'terms'],
            ['label' => I18n::t('admin.menu.settings', 'Settings'), 'url' => 'admin/settings', 'icon' => 'settings'],
        ];
    }
}
