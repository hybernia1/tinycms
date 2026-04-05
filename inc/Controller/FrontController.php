<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\Feature\AuthService;
use App\Service\Feature\ContentService;
use App\Service\Feature\TermService;
use App\Service\Support\CsrfService;
use App\Service\Feature\SettingsService;
use App\Service\Support\I18n;
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
        $settings = $this->settings->resolved();
        $site = [
            'name' => (string)($settings['sitename'] ?? 'TinyCMS'),
            'footer' => (string)($settings['sitefooter'] ?? '© TinyCMS'),
            'author' => (string)($settings['siteauthor'] ?? 'Admin'),
            'theme' => (string)($settings['theme'] ?? 'default'),
            'meta_title' => (string)($settings['meta_title'] ?? $settings['sitename'] ?? 'TinyCMS'),
            'meta_description' => (string)($settings['meta_description'] ?? ''),
            'logo' => (string)($settings['site_logo'] ?? ''),
            'favicon' => (string)($settings['site_favicon'] ?? ''),
            'allow_registration' => (int)($settings['allow_registration'] ?? '1') === 1,
        ];
        $posts = array_map(fn(array $item): array => $this->toPublicListItem($item), $this->contentService->listPublished(30));

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
        $pagination = $this->contentService->paginatePublishedByTerm((int)($term['id'] ?? 0), $page, 10);
        $posts = array_map(fn(array $item): array => $this->toPublicListItem($item), (array)($pagination['data'] ?? []));

        $this->pages->termArchive([
            'id' => (int)($term['id'] ?? 0),
            'name' => (string)($term['name'] ?? ''),
            'slug' => $slug,
            'body' => (string)($term['body'] ?? ''),
        ], $posts, $pagination, $this->currentTheme());
    }

    public function search(): void
    {
        $query = trim((string)($_GET['q'] ?? ''));
        $page = (int)($_GET['page'] ?? 1);
        $pagination = $this->contentService->paginatePublishedSearch($query, $page, 10);
        $posts = array_map(fn(array $item): array => $this->toPublicListItem($item), (array)($pagination['data'] ?? []));
        $settings = $this->settings->resolved();
        $site = [
            'footer' => (string)($settings['sitefooter'] ?? '© TinyCMS'),
        ];

        $this->pages->search($posts, $pagination, $query, $this->currentTheme(), $site);
    }

    public function feed(): void
    {
        $settings = $this->settings->resolved();
        $siteName = (string)($settings['sitename'] ?? 'TinyCMS');
        $description = (string)($settings['meta_description'] ?? '');
        $items = array_map(fn(array $item): array => $this->toRssItem($item), $this->contentService->listPublishedFeed(100));

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

        $items = array_map(fn(array $item): array => $this->toRssItem($item), $this->contentService->listPublishedByTermFeed((int)($term['id'] ?? 0), 100));
        $termName = (string)($term['name'] ?? '');

        $this->pages->rssFeed([
            'title' => $termName,
            'link' => $this->absoluteUrl('term/' . $slug),
            'self' => $this->absoluteUrl('term/' . $slug . '/feed'),
            'description' => I18n::t('front.term.meta_description_prefix', 'Articles on topic') . ': ' . $termName,
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
        $this->pages->contentDetail($this->toDetailItem($item, $slug, $terms), $this->currentTheme());
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
            'allowRegistration' => $this->authService->isRegistrationAllowed(),
        ], $this->siteSettings());
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
                'allowRegistration' => $this->authService->isRegistrationAllowed(),
            ], $this->siteSettings());
            return;
        }

        $result = $this->authService->login($_POST);

        if (($result['success'] ?? false) === true) {
            $redirect((string)$result['redirect']);
        }

        $this->pages->loginForm([
            'errors' => $result['errors'] ?? [],
            'message' => (string)($result['message'] ?? I18n::t('auth.login_failed', 'Login failed.')),
            'old' => ['email' => trim((string)($_POST['email'] ?? '')), 'remember' => (int)((int)($_POST['remember'] ?? 0) === 1)],
            'allowRegistration' => $this->authService->isRegistrationAllowed(),
        ], $this->siteSettings());
    }

    public function registerForm(callable $redirect): void
    {
        if ($this->authService->auth()->check()) {
            $redirect($this->authService->redirectAfterLogin());
        }

        if (!$this->authService->isRegistrationAllowed()) {
            $this->pages->loginForm([
                'errors' => [],
                'message' => I18n::t('auth.registration_disabled', 'Registration is disabled.'),
                'old' => ['email' => '', 'remember' => 0],
                'allowRegistration' => false,
            ], $this->siteSettings());
            return;
        }

        $this->pages->registerForm(['errors' => [], 'message' => '', 'old' => ['name' => '', 'email' => '']], $this->siteSettings());
    }

    public function registerSubmit(callable $redirect): void
    {
        if ($this->authService->auth()->check()) {
            $redirect($this->authService->redirectAfterLogin());
        }

        if (!$this->csrf->verify((string)($_POST['_csrf'] ?? ''))) {
            $this->pages->registerForm(['errors' => [], 'message' => I18n::t('common.csrf_expired'), 'old' => ['name' => trim((string)($_POST['name'] ?? '')), 'email' => trim((string)($_POST['email'] ?? ''))]], $this->siteSettings());
            return;
        }

        $baseUrl = $this->absoluteUrl('/');
        $result = $this->authService->register($_POST, rtrim($baseUrl, '/'));
        if (($result['success'] ?? false) === true) {
            $this->pages->loginForm([
                'errors' => [],
                'message' => (string)($result['message'] ?? ''),
                'old' => ['email' => trim((string)($_POST['email'] ?? '')), 'remember' => 0],
                'allowRegistration' => $this->authService->isRegistrationAllowed(),
            ], $this->siteSettings());
            return;
        }

        $this->pages->registerForm([
            'errors' => (array)($result['errors'] ?? []),
            'message' => (string)($result['message'] ?? ''),
            'old' => ['name' => trim((string)($_POST['name'] ?? '')), 'email' => trim((string)($_POST['email'] ?? ''))],
        ], $this->siteSettings());
    }

    public function activateForm(): void
    {
        $result = $this->authService->activate(trim((string)($_GET['token'] ?? '')));
        $this->pages->activationResult($result, $this->siteSettings());
    }

    public function lostForm(): void
    {
        $this->pages->lostForm(['message' => '', 'errors' => [], 'old' => ['email' => '', 'mode' => 'password']], $this->siteSettings());
    }

    public function lostSubmit(): void
    {
        if (!$this->csrf->verify((string)($_POST['_csrf'] ?? ''))) {
            $this->pages->lostForm(['message' => I18n::t('common.csrf_expired'), 'errors' => [], 'old' => ['email' => trim((string)($_POST['email'] ?? '')), 'mode' => trim((string)($_POST['mode'] ?? 'password'))]], $this->siteSettings());
            return;
        }

        $result = $this->authService->lost($_POST, rtrim($this->absoluteUrl('/'), '/'));
        $this->pages->lostForm(['message' => (string)($result['message'] ?? ''), 'errors' => (array)($result['errors'] ?? []), 'old' => ['email' => trim((string)($_POST['email'] ?? '')), 'mode' => trim((string)($_POST['mode'] ?? 'password'))]], $this->siteSettings());
    }

    private function siteSettings(): array
    {
        $settings = $this->settings->resolved();
        return [
            'siteName' => (string)($settings['sitename'] ?? 'TinyCMS'),
            'siteFooter' => (string)($settings['sitefooter'] ?? '© TinyCMS'),
            'siteLogo' => (string)($settings['site_logo'] ?? ''),
            'siteFavicon' => (string)($settings['site_favicon'] ?? ''),
            'allowRegistration' => (int)($settings['allow_registration'] ?? '1') === 1,
        ];
    }


    private function notFound(): void
    {
        http_response_code(404);
        echo '404';
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

    private function robots(): void
    {
        header('Content-Type: text/plain; charset=utf-8');
        echo "User-agent: *\n";
        echo "Allow: /\n";
        echo 'Sitemap: ' . $this->absoluteUrl('sitemap.xml') . "\n";
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

        header('Content-Type: application/xml; charset=utf-8');
        echo $this->renderSitemapIndex($items);
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

        header('Content-Type: application/xml; charset=utf-8');
        echo $this->renderSitemapUrlSet($urls);
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

        header('Content-Type: application/xml; charset=utf-8');
        echo $this->renderSitemapUrlSet($urls);
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

    private function renderSitemapIndex(array $paths): string
    {
        $items = array_map(fn(string $path): string => '<sitemap><loc>' . htmlspecialchars($this->absoluteUrl($path), ENT_XML1, 'UTF-8') . '</loc></sitemap>', $paths);
        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'
            . implode('', $items)
            . '</sitemapindex>';
    }

    private function renderSitemapUrlSet(array $urls): string
    {
        $items = array_map(static function (array $url): string {
            $xml = '<url><loc>' . htmlspecialchars((string)($url['loc'] ?? ''), ENT_XML1, 'UTF-8') . '</loc>';
            if ((string)($url['lastmod'] ?? '') !== '') {
                $xml .= '<lastmod>' . htmlspecialchars((string)$url['lastmod'], ENT_XML1, 'UTF-8') . '</lastmod>';
            }
            return $xml . '</url>';
        }, $urls);

        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'
            . implode('', $items)
            . '</urlset>';
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
