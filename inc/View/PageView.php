<?php
declare(strict_types=1);

namespace App\View;

use App\Service\Feature\ThemeService;
use App\Service\Feature\SettingsService;
use App\Service\Feature\UploadService;
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
                    'title' => I18n::t('front.feed.title'),
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
            'pageTitle' => (string)($item['name'] ?? I18n::t('admin.menu.content')),
            'metaTitle' => (string)($item['name'] ?? I18n::t('admin.menu.content')),
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
            'pageTitle' => $termName !== '' ? $termName : I18n::t('admin.menu.terms'),
            'metaTitle' => $termName !== '' ? $termName : I18n::t('admin.menu.terms'),
            'metaDescription' => I18n::t('front.term.meta_description_prefix') . ': ' . $termName,
            'metaKeywords' => [$termName],
            'metaPath' => $termSlug !== '' ? 'term/' . $termSlug : '',
            'metaOgType' => 'website',
            'metaSearchUrlTemplate' => 'search?q={search_term_string}',
            'metaAlternateLinks' => $termSlug !== '' ? [
                [
                    'rel' => 'alternate',
                    'type' => 'application/rss+xml',
                    'title' => I18n::t('front.feed.title'),
                    'href' => 'term/' . $termSlug . '/feed',
                ],
            ] : [],
        ]);
    }

    public function search(array $posts, array $pagination, string $query, string $theme, array $site = []): void
    {
        $resolvedTheme = $this->themes->resolveTheme($theme);
        $title = $query === '' ? I18n::t('front.search.title') : I18n::t('front.search.results_for') . ': ' . $query;

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
                ? I18n::t('front.search.meta_description')
                : I18n::t('front.search.meta_description_prefix') . ': ' . $query,
            'metaKeywords' => $query !== '' ? [$query] : [],
            'metaPath' => $query !== '' ? 'search?q=' . rawurlencode($query) : 'search',
            'metaRobots' => 'noindex,follow',
            'metaOgType' => 'website',
            'metaSearchUrlTemplate' => 'search?q={search_term_string}',
        ]);
    }

    public function rssFeed(array $channel, array $items): void
    {
        $this->view->render('front/xml/layout', 'front/rss/feed', [
            'channel' => $channel,
            'items' => $items,
            'contentType' => 'application/rss+xml; charset=utf-8',
        ]);
    }

    public function robots(string $sitemapUrl): void
    {
        $this->view->render('front/plain/layout', 'front/robots', [
            'sitemapUrl' => $sitemapUrl,
        ]);
    }

    public function sitemapIndex(array $paths): void
    {
        $this->view->render('front/xml/layout', 'front/sitemap/index', [
            'paths' => $paths,
        ]);
    }

    public function sitemapUrlSet(array $urls): void
    {
        $this->view->render('front/xml/layout', 'front/sitemap/urlset', [
            'urls' => $urls,
        ]);
    }


    public function notFound(string $requestUri = ''): void
    {
        http_response_code(404);

        $path = trim((string)(parse_url($requestUri !== '' ? $requestUri : (string)($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?? ''), '/');
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $accept = strtolower((string)($_SERVER['HTTP_ACCEPT'] ?? ''));

        $mode = match (true) {
            in_array($ext, ['png', 'jpg', 'jpeg', 'gif', 'webp', 'svg', 'ico', 'avif'], true) => 'image',
            $ext === 'xml' || str_contains($accept, 'xml') => 'document',
            $ext === 'txt' || str_contains($accept, 'text/plain') => 'text',
            default => 'html',
        };

        $layout = match ($mode) {
            'document', 'text', 'image' => 'front/plain/layout',
            default => 'front/layout',
        };

        $payload = ['notFoundMode' => $mode, 'pageTitle' => '404'];
        if ($mode === 'image') {
            $payload['contentType'] = 'image/svg+xml; charset=utf-8';
        }
        $this->view->render($layout, 'front/errors/404', $payload);
    }

    public function adminDashboard(?array $user): void
    {
        $this->renderAdmin('admin/dashboard', [
            'user' => $user,
            'adminMenu' => $this->adminMenu(),
            'pageTitle' => I18n::t('admin.menu.dashboard'),
        ]);
    }

    public function adminUsersList(array $pagination, array $allowedPerPage, string $status, string $query, array $statusCounts): void
    {
        $this->renderAdmin('admin/users/list', [
            'pagination' => $pagination,
            'allowedPerPage' => $allowedPerPage,
            'status' => $status,
            'query' => $query,
            'statusCounts' => $statusCounts,
            'adminMenu' => $this->adminMenu(),
            'pageTitle' => I18n::t('admin.menu.users'),
            'headerAction' => $this->linkHeaderAction('admin/users/add', I18n::t('admin.add_user')),
        ]);
    }

    public function adminSettingsForm(array $fields, array $values): void
    {
        $this->renderAdmin('admin/settings/form', [
            'fields' => $fields,
            'values' => $values,
            'adminMenu' => $this->adminMenu(),
            'pageTitle' => I18n::t('admin.menu.settings'),
            'headerAction' => $this->submitHeaderAction('#settings-form'),
        ]);
    }

    public function adminUsersForm(string $mode, array $user, array $errors): void
    {
        $this->renderAdmin('admin/users/form', [
            'mode' => $mode,
            'user' => $user,
            'errors' => $errors,
            'adminMenu' => $this->adminMenu(),
            'pageTitle' => $mode === 'add' ? I18n::t('admin.add_user') : I18n::t('admin.edit_user'),
            'headerAction' => $this->submitHeaderAction('#users-form'),
        ]);
    }

    public function adminContentList(array $pagination, array $allowedPerPage, string $status, string $query, array $availableStatuses, array $statusCounts): void
    {
        $this->renderAdmin('admin/content/list', [
            'pagination' => $pagination,
            'allowedPerPage' => $allowedPerPage,
            'status' => $status,
            'query' => $query,
            'availableStatuses' => $availableStatuses,
            'statusCounts' => $statusCounts,
            'adminMenu' => $this->adminMenu(),
            'pageTitle' => I18n::t('admin.menu.content'),
            'headerAction' => $this->linkHeaderAction('admin/content/add', I18n::t('admin.add_content')),
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
            'pageTitle' => $mode === 'add' ? I18n::t('admin.add_content') : I18n::t('admin.edit_content'),
            'headerAction' => $this->contentMenuHeaderAction($mode === 'edit'),
        ]);
    }

    public function adminTermList(array $pagination, array $allowedPerPage, string $status, string $query, array $statusCounts): void
    {
        $this->renderAdmin('admin/terms/list', [
            'pagination' => $pagination,
            'allowedPerPage' => $allowedPerPage,
            'status' => $status,
            'query' => $query,
            'statusCounts' => $statusCounts,
            'adminMenu' => $this->adminMenu(),
            'pageTitle' => I18n::t('admin.menu.terms'),
            'headerAction' => $this->linkHeaderAction('admin/terms/add', I18n::t('admin.add_term')),
        ]);
    }

    public function adminTermForm(string $mode, array $item, array $errors, array $usages = []): void
    {
        $this->renderAdmin('admin/terms/form', [
            'mode' => $mode,
            'item' => $item,
            'errors' => $errors,
            'usages' => $usages,
            'adminMenu' => $this->adminMenu(),
            'pageTitle' => $mode === 'add' ? I18n::t('admin.add_term') : I18n::t('admin.edit_term'),
            'headerAction' => $this->submitHeaderAction('#terms-form'),
        ]);
    }

    public function adminMediaList(array $pagination, array $allowedPerPage, string $status, string $query, array $statusCounts): void
    {
        $this->renderAdmin('admin/media/list', [
            'pagination' => $pagination,
            'allowedPerPage' => $allowedPerPage,
            'status' => $status,
            'query' => $query,
            'statusCounts' => $statusCounts,
            'adminMenu' => $this->adminMenu(),
            'pageTitle' => I18n::t('admin.menu.media'),
            'headerAction' => $this->linkHeaderAction('admin/media/add', I18n::t('admin.add_media')),
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
            'pageTitle' => $mode === 'add' ? I18n::t('admin.add_media') : I18n::t('admin.edit_media'),
            'headerAction' => $mode === 'edit'
                ? $this->saveMenuHeaderAction('#media-form', '#media-delete-modal')
                : $this->submitHeaderAction('#media-form'),
        ]);
    }

    private function submitHeaderAction(string $formSelector): array
    {
        return ['type' => 'submit', 'form' => $formSelector, 'label' => I18n::t('common.save')];
    }

    private function linkHeaderAction(string $href, string $label): array
    {
        return ['type' => 'link', 'href' => $href, 'label' => $label, 'icon' => 'add'];
    }

    private function saveMenuHeaderAction(string $formSelector, string $deleteModalTarget): array
    {
        return ['type' => 'save-menu', 'form' => $formSelector, 'delete_modal_target' => $deleteModalTarget];
    }

    private function contentMenuHeaderAction(bool $canDelete): array
    {
        return [
            'type' => 'content-menu',
            'delete_modal_target' => $canDelete ? '#content-delete-modal' : '',
        ];
    }

    private function renderAdmin(string $template, array $data): void
    {
        $this->view->render('admin/layout', $template, array_merge(
            $data,
            $this->adminBranding(),
            [
                'imageUploadAccept' => UploadService::imageAccept(),
                'siteImageUploadAccept' => UploadService::siteImageAccept(),
                'imageUploadTypesLabel' => UploadService::imageExtensionsLabel(),
                'siteImageUploadTypesLabel' => UploadService::siteImageExtensionsLabel(),
            ]
        ));
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
            ['label' => I18n::t('admin.menu.dashboard'), 'url' => 'admin/dashboard', 'icon' => 'dashboard'],
            ['label' => I18n::t('admin.menu.users'), 'url' => 'admin/users', 'icon' => 'users'],
            ['label' => I18n::t('admin.menu.content'), 'url' => 'admin/content', 'icon' => 'content'],
            ['label' => I18n::t('admin.menu.media'), 'url' => 'admin/media', 'icon' => 'media'],
            ['label' => I18n::t('admin.menu.terms'), 'url' => 'admin/terms', 'icon' => 'terms'],
            ['label' => I18n::t('admin.menu.settings'), 'url' => 'admin/settings', 'icon' => 'settings'],
        ];
    }
}
