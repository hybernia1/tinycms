<?php
declare(strict_types=1);

namespace App\View;

use App\Service\Feature\ThemeService;
use App\Service\Support\I18n;

final class FrontPageView
{
    public function __construct(
        private View $view,
        private ThemeService $themes
    ) {
    }

    public function home(?array $user, array $site, array $posts = []): void
    {
        $siteName = (string)($site['name'] ?? 'TinyCMS');
        $theme = $this->themes->resolveTheme((string)($site['theme'] ?? 'default'));
        $this->view->renderTheme($theme, 'index', array_merge(
            [
                'user' => $user,
                'posts' => $posts,
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
            ],
            $this->frontSiteData($site)
        ));
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

        $this->view->renderTheme($resolvedTheme, 'content', array_merge(
            [
                'item' => $item,
                'themeName' => $resolvedTheme,
                'pageTitle' => (string)($item['name'] ?? I18n::t('admin.menu.content')),
                'metaTitle' => (string)($item['name'] ?? I18n::t('admin.menu.content')),
                'metaDescription' => (string)($item['excerpt'] ?? ''),
                'metaKeywords' => array_values(array_filter($terms, static fn(string $term): bool => $term !== '')),
                'metaPath' => (string)($item['slug'] ?? ''),
                'shortlinkPath' => (string)($item['id'] ?? ''),
                'metaOgType' => 'article',
                'metaOgImage' => $ogImage,
                'metaPublishedTime' => (string)($item['created'] ?? ''),
            ],
            $this->frontSiteData($site)
        ));
    }

    public function termArchive(array $term, array $posts, array $pagination, string $theme, array $site = []): void
    {
        $resolvedTheme = $this->themes->resolveTheme($theme);
        $termSlug = (string)($term['slug'] ?? '');
        $termName = (string)($term['name'] ?? '');
        $this->view->renderTheme($resolvedTheme, 'terms', array_merge(
            [
                'term' => $term,
                'posts' => $posts,
                'pagination' => $pagination,
                'themeName' => $resolvedTheme,
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
            ],
            $this->frontSiteData($site)
        ));
    }

    public function search(array $posts, array $pagination, string $query, string $theme, array $site = []): void
    {
        $resolvedTheme = $this->themes->resolveTheme($theme);
        $title = $query === '' ? I18n::t('front.search.title') : I18n::t('front.search.results_for') . ': ' . $query;

        $this->view->renderTheme($resolvedTheme, 'search', array_merge(
            [
                'posts' => $posts,
                'pagination' => $pagination,
                'query' => $query,
                'themeName' => $resolvedTheme,
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
            ],
            $this->frontSiteData($site)
        ));
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

    private function frontSiteData(array $site): array
    {
        return [
            'siteName' => (string)($site['name'] ?? 'TinyCMS'),
            'siteAuthor' => (string)($site['author'] ?? 'Admin'),
            'siteFooter' => (string)($site['footer'] ?? '© TinyCMS'),
            'siteFavicon' => (string)($site['favicon'] ?? ''),
            'siteLogo' => (string)($site['logo'] ?? ''),
        ];
    }
}
