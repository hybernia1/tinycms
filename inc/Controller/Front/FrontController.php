<?php
declare(strict_types=1);

namespace App\Controller\Front;

use App\Service\Feature\AuthService;
use App\Service\Feature\ContentService;
use App\Service\Feature\TermService;
use App\Service\Support\CsrfService;
use App\Service\Feature\SettingsService;
use App\Service\Support\I18n;
use App\Service\Support\PaginationConfig;
use App\Service\Support\SluggerService;
use App\View\PageView;

final class FrontController
{
    private PageView $pages;
    private AuthService $authService;
    private CsrfService $csrf;
    private SettingsService $settings;
    private ContentService $contentService;
    private TermService $termService;
    private SluggerService $slugger;

    public function __construct(PageView $pages, AuthService $authService, CsrfService $csrf, SettingsService $settings, ContentService $contentService, TermService $termService, SluggerService $slugger)
    {
        $this->pages = $pages;
        $this->authService = $authService;
        $this->csrf = $csrf;
        $this->settings = $settings;
        $this->contentService = $contentService;
        $this->termService = $termService;
        $this->slugger = $slugger;
    }

    public function home(): void
    {
        $site = $this->siteData();
        $posts = array_map(fn(array $item): array => $this->toPublicListItem($item), $this->contentService->listPublished(PaginationConfig::perPage()));

        $this->pages->home($this->authService->auth()->user(), $site, $posts);
    }

    public function entry(array $params, callable $redirect): void
    {
        $slug = trim((string)($params['slug'] ?? ''));

        if ($slug === 'robots.txt') {
            $this->robots();
            return;
        }

        if ($slug === 'sitemap.xml') {
            $this->sitemapIndex();
            return;
        }

        if (preg_match('/^sitemap-content(?:-(\d+))?\.xml$/', $slug, $matches) === 1) {
            $this->sitemapContent((int)($matches[1] ?? 1));
            return;
        }

        if (preg_match('/^sitemap-terms(?:-(\d+))?\.xml$/', $slug, $matches) === 1) {
            $this->sitemapTerms((int)($matches[1] ?? 1));
            return;
        }

        $this->contentDetail($slug, $redirect);
    }

    public function termArchive(array $params, callable $redirect): void
    {
        $requestedSlug = trim((string)($params['slug'] ?? ''));
        $id = $this->slugger->extractId($requestedSlug);
        $term = $id > 0 ? $this->termService->find($id) : null;

        if ($term === null) {
            $this->notFound();
        }

        $slug = $this->slugger->slug((string)($term['name'] ?? ''), (int)($term['id'] ?? 0));
        if ($requestedSlug !== $slug) {
            $redirect('term/' . $slug, true);
        }

        $page = (int)($_GET['page'] ?? 1);
        $pagination = $this->contentService->paginatePublishedByTerm((int)($term['id'] ?? 0), $page, PaginationConfig::perPage());
        $posts = array_map(fn(array $item): array => $this->toPublicListItem($item), (array)($pagination['data'] ?? []));

        $this->pages->termArchive([
            'id' => (int)($term['id'] ?? 0),
            'name' => (string)($term['name'] ?? ''),
            'slug' => $slug,
        ], $posts, $pagination, $this->currentTheme(), $this->siteData());
    }

    public function search(): void
    {
        $query = trim((string)($_GET['q'] ?? ''));
        $page = (int)($_GET['page'] ?? 1);
        $pagination = $this->contentService->paginatePublishedSearch($query, $page, PaginationConfig::perPage());
        $posts = array_map(fn(array $item): array => $this->toPublicListItem($item), (array)($pagination['data'] ?? []));
        $site = $this->siteData();

        $this->pages->search($posts, $pagination, $query, $this->currentTheme(), $site);
    }

    public function feed(): void
    {
        $settings = $this->settings->resolved();
        $siteName = (string)($settings['sitename'] ?? 'TinyCMS');
        $description = (string)($settings['meta_description'] ?? '');
        $items = array_map(fn(array $item): array => $this->toRssItem($item), $this->contentService->listPublishedFeed(PaginationConfig::perPage()));

        $this->pages->rssFeed([
            'title' => $siteName,
            'link' => $this->absoluteUrl('/'),
            'self' => $this->absoluteUrl('feed'),
            'description' => $description !== '' ? $description : $siteName,
        ], $items);
    }

    public function termFeed(array $params, callable $redirect): void
    {
        $requestedSlug = trim((string)($params['slug'] ?? ''));
        $id = $this->slugger->extractId($requestedSlug);
        $term = $id > 0 ? $this->termService->find($id) : null;

        if ($term === null) {
            $this->notFound();
        }

        $slug = $this->slugger->slug((string)($term['name'] ?? ''), (int)($term['id'] ?? 0));
        if ($requestedSlug !== $slug) {
            $redirect('term/' . $slug . '/feed', true);
        }

        $items = array_map(fn(array $item): array => $this->toRssItem($item), $this->contentService->listPublishedByTermFeed((int)($term['id'] ?? 0), PaginationConfig::perPage()));
        $termName = (string)($term['name'] ?? '');

        $this->pages->rssFeed([
            'title' => $termName,
            'link' => $this->absoluteUrl('term/' . $slug),
            'self' => $this->absoluteUrl('term/' . $slug . '/feed'),
            'description' => I18n::t('front.term.meta_description_prefix') . ': ' . $termName,
        ], $items);
    }

    private function contentDetail(string $requestedSlug, callable $redirect): void
    {
        $id = $this->slugger->extractId($requestedSlug);
        $item = $id > 0 ? $this->contentService->findPublished($id) : null;

        if ($item === null) {
            $this->notFound();
        }

        $slug = $this->slugger->slug((string)($item['name'] ?? ''), (int)($item['id'] ?? 0));
        if ($requestedSlug !== $slug) {
            $redirect($slug, true);
        }

        $terms = $this->termService->listByContent((int)($item['id'] ?? 0));
        $this->pages->contentDetail($this->toDetailItem($item, $slug, $terms), $this->currentTheme(), $this->siteData());
    }

    public function loginForm(callable $redirect): void
    {
        if ($this->authService->auth()->check()) {
            $redirect($this->authService->redirectAfterLogin());
        }

        $this->pages->loginForm([
            'errors' => [],
            'message' => '',
            'old' => ['email' => '', 'remember' => 0],
        ]);
    }

    public function loginSubmit(callable $redirect): void
    {
        if ($this->authService->auth()->check()) {
            $redirect($this->authService->redirectAfterLogin());
        }

        if (!$this->csrf->verify((string)($_POST['_csrf'] ?? ''))) {
            $this->pages->loginForm([
                'errors' => [],
                'message' => I18n::t('common.csrf_expired'),
                'old' => ['email' => trim((string)($_POST['email'] ?? '')), 'remember' => (int)((int)($_POST['remember'] ?? 0) === 1)],
            ]);
            return;
        }

        $result = $this->authService->login($_POST);

        if (($result['success'] ?? false) === true) {
            $redirect((string)$result['redirect']);
        }

        $this->pages->loginForm([
            'errors' => $result['errors'] ?? [],
            'message' => (string)($result['message'] ?? I18n::t('auth.login_failed')),
            'old' => ['email' => trim((string)($_POST['email'] ?? '')), 'remember' => (int)((int)($_POST['remember'] ?? 0) === 1)],
        ]);
    }

    public function notFoundResponse(): void
    {
        $this->notFound();
    }

    private function notFound(): void
    {
        $this->pages->notFound((string)($_SERVER['REQUEST_URI'] ?? '/'));
        exit;
    }

    private function toPublicListItem(array $item): array
    {
        $id = (int)($item['id'] ?? 0);
        $slug = $this->slugger->slug((string)($item['name'] ?? ''), $id);

        return [
            'id' => $id,
            'name' => (string)($item['name'] ?? ''),
            'excerpt' => $this->plainExcerpt((string)($item['excerpt'] ?? '')),
            'created' => (string)($item['created'] ?? ''),
            'slug' => $slug,
            'url' => $slug,
            'thumbnail' => $this->thumbnailData($item),
        ];
    }

    private function toDetailItem(array $item, string $slug, array $terms): array
    {
        return [
            'slug' => $slug,
            'id' => (int)($item['id'] ?? 0),
            'name' => (string)($item['name'] ?? ''),
            'excerpt' => $this->plainExcerpt((string)($item['excerpt'] ?? '')),
            'body' => (string)($item['body'] ?? ''),
            'created' => (string)($item['created'] ?? ''),
            'thumbnail' => $this->thumbnailData($item),
            'terms' => $this->toPublicTerms($terms),
        ];
    }

    private function toRssItem(array $item): array
    {
        $id = (int)($item['id'] ?? 0);
        $slug = $this->slugger->slug((string)($item['name'] ?? ''), $id);
        $link = $this->absoluteUrl($slug);
        $description = trim((string)($item['excerpt'] ?? ''));
        if ($description === '') {
            $description = trim((string)($item['body'] ?? ''));
        }

        $timestamp = strtotime((string)($item['created'] ?? ''));
        $pubDate = $timestamp === false ? gmdate(DATE_RSS) : gmdate(DATE_RSS, $timestamp);

        return [
            'title' => (string)($item['name'] ?? ''),
            'link' => $link,
            'guid' => $link,
            'pubDate' => $pubDate,
            'description' => $description,
        ];
    }




    private function toPublicTerms(array $terms): array
    {
        $result = [];
        foreach ($terms as $term) {
            $id = (int)($term['id'] ?? 0);
            $name = trim((string)($term['name'] ?? ''));
            if ($id <= 0 || $name === '') {
                continue;
            }

            $result[] = [
                'id' => $id,
                'name' => $name,
                'slug' => $this->slugger->slug($name, $id),
            ];
        }

        return $result;
    }

    private function plainExcerpt(string $excerpt): string
    {
        $plain = trim(strip_tags($excerpt));
        return preg_replace('/\s+/u', ' ', $plain) ?? '';
    }

    private function thumbnailData(array $item): array
    {
        $path = trim((string)($item['thumbnail_path'] ?? ''));
        $webp = trim((string)($item['thumbnail_path_webp'] ?? ''));

        if ($path === '' && $webp === '') {
            return [];
        }

        return [
            'path' => $path,
            'webp' => $webp,
            'webp_sources' => $this->buildWebpSources($webp),
        ];
    }

    private function buildWebpSources(string $webpPath): array
    {
        if ($webpPath === '') {
            return [];
        }

        $sources = [
            ['path' => $webpPath, 'width' => 1024],
        ];

        foreach ($this->thumbSuffixes() as $suffix => $width) {
            $variant = (string)(preg_replace('/\.webp$/i', $suffix, $webpPath) ?? '');
            if ($variant !== '') {
                $sources[] = ['path' => $variant, 'width' => $width];
            }
        }

        usort($sources, static fn(array $a, array $b): int => ((int)$a['width']) <=> ((int)$b['width']));
        return $sources;
    }

    private function thumbSuffixes(): array
    {
        $raw = defined('MEDIA_THUMB_VARIANTS') && is_array(MEDIA_THUMB_VARIANTS) ? MEDIA_THUMB_VARIANTS : [];
        $suffixes = [];

        foreach ($raw as $variant) {
            if (!is_array($variant)) {
                continue;
            }

            $suffix = trim((string)($variant['suffix'] ?? ''));
            $width = (int)($variant['width'] ?? 0);
            if ($suffix === '' || $width <= 0 || !str_ends_with(strtolower($suffix), '.webp')) {
                continue;
            }

            $suffixes[$suffix] = $width;
        }

        if ($suffixes === []) {
            return [
                '_100x100.webp' => 100,
                '_w768.webp' => 768,
            ];
        }

        return $suffixes;
    }

    private function currentTheme(): string
    {
        return (string)($this->settings->resolved()['theme'] ?? 'default');
    }

    private function siteData(): array
    {
        $settings = $this->settings->resolved();
        return [
            'name' => (string)($settings['sitename'] ?? 'TinyCMS'),
            'footer' => (string)($settings['sitefooter'] ?? '© TinyCMS'),
            'author' => (string)($settings['siteauthor'] ?? 'Admin'),
            'theme' => (string)($settings['theme'] ?? 'default'),
            'meta_title' => (string)($settings['meta_title'] ?? $settings['sitename'] ?? 'TinyCMS'),
            'meta_description' => (string)($settings['meta_description'] ?? ''),
            'favicon' => (string)($settings['favicon'] ?? ''),
            'logo' => (string)($settings['logo'] ?? ''),
        ];
    }

    private function robots(): void
    {
        $this->pages->robots($this->absoluteUrl('sitemap.xml'));
        exit;
    }

    private function sitemapIndex(): void
    {
        $items = [];
        foreach ($this->sitemapLinks('sitemap-content', $this->contentService->publishedVisibleCount()) as $path) {
            $items[] = $path;
        }
        foreach ($this->sitemapLinks('sitemap-terms', $this->termService->totalCount()) as $path) {
            $items[] = $path;
        }

        $this->pages->sitemapIndex($items);
        exit;
    }

    private function sitemapContent(int $page): void
    {
        $links = $this->contentService->sitemapPublishedPage($page, 2000);
        if ($links === [] && $page > 1) {
            $this->notFound();
        }

        $urls = array_map(function (array $row): array {
            $slug = $this->slugger->slug((string)($row['name'] ?? ''), (int)($row['id'] ?? 0));
            return [
                'loc' => $this->absoluteUrl($slug),
                'lastmod' => $this->toIsoDate((string)($row['updated'] ?? ''), (string)($row['created'] ?? '')),
            ];
        }, $links);

        $this->pages->sitemapUrlSet($urls);
        exit;
    }

    private function sitemapTerms(int $page): void
    {
        $links = $this->termService->sitemapPage($page, 2000);
        if ($links === [] && $page > 1) {
            $this->notFound();
        }

        $urls = array_map(function (array $row): array {
            $slug = $this->slugger->slug((string)($row['name'] ?? ''), (int)($row['id'] ?? 0));
            return [
                'loc' => $this->absoluteUrl('term/' . $slug),
                'lastmod' => $this->toIsoDate((string)($row['updated'] ?? ''), (string)($row['created'] ?? '')),
            ];
        }, $links);

        $this->pages->sitemapUrlSet($urls);
        exit;
    }

    private function sitemapLinks(string $base, int $count): array
    {
        $pages = max(1, (int)ceil($count / 2000));
        $paths = [];
        for ($page = 1; $page <= $pages; $page++) {
            $paths[] = $page === 1 ? $base . '.xml' : $base . '-' . $page . '.xml';
        }

        return $paths;
    }

    private function absoluteUrl(string $path): string
    {
        $host = trim((string)($_SERVER['HTTP_HOST'] ?? ''), '/');
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $cleanPath = '/' . ltrim($path, '/');

        if ($host === '') {
            return $cleanPath;
        }

        return $scheme . '://' . $host . $cleanPath;
    }

    private function toIsoDate(string $updated, string $created): string
    {
        $value = trim($updated) !== '' ? $updated : $created;
        $timestamp = strtotime($value);
        return $timestamp === false ? '' : gmdate('c', $timestamp);
    }
}
