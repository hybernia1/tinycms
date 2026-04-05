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
        ], $posts, $pagination);
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

        $this->pages->contentDetail($this->toDetailItem($item, $slug));
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
            'message' => (string)($result['message'] ?? I18n::t('auth.login_failed', 'Login failed.')),
            'old' => ['email' => trim((string)($_POST['email'] ?? '')), 'remember' => (int)((int)($_POST['remember'] ?? 0) === 1)],
        ]);
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
            'excerpt' => (string)($item['excerpt'] ?? ''),
            'created' => (string)($item['created'] ?? ''),
            'slug' => $slug,
            'url' => $slug,
        ];
    }

    private function toDetailItem(array $item, string $slug): array
    {
        return [
            'slug' => $slug,
            'id' => (int)($item['id'] ?? 0),
            'name' => (string)($item['name'] ?? ''),
            'excerpt' => (string)($item['excerpt'] ?? ''),
            'body' => (string)($item['body'] ?? ''),
            'created' => (string)($item['created'] ?? ''),
        ];
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
