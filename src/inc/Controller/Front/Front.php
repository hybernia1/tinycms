<?php
declare(strict_types=1);

namespace App\Controller\Front;

use App\Service\Auth\Auth;
use App\Service\Application\Content as ContentService;
use App\Service\Application\ContentStats;
use App\Service\Application\Settings;
use App\Service\Application\Term;
use App\Service\Application\User;
use App\Service\Infrastructure\Db\Connection;
use App\Service\Infrastructure\Db\Query;
use App\Service\Support\Media;
use App\Service\Support\RequestContext;
use App\Service\Support\Slugger;
use App\Service\Support\Shortcode;
use App\View\FrontView;

final class Front
{
    private const SITEMAP_CHUNK_SIZE = 5000;
    private Query $query;
    private Slugger $slugger;

    public function __construct(
        private FrontView $view,
        private Settings $settings,
        private Term $terms,
        private User $users,
        private Auth $auth,
        private ContentStats $contentStats,
        private array $resolvedSettings = []
    ) {
        $this->query = new Query(Connection::get());
        $this->slugger = new Slugger();
    }

    public function home(): void
    {
        $settings = $this->resolvedSettings();
        $perPage = $this->resolvePerPage($settings);

        $contentId = (int)($settings['front_home_content'] ?? 0);
        $item = $contentId > 0 ? $this->findPublishedContent($contentId) : null;
        if ($item !== null) {
            $this->recordView($item);
            $this->view->homeContent($item);
            return;
        }

        $page = max(1, (int)($_GET['page'] ?? 1));
        $pagination = $this->paginatePublished($page, $perPage);
        $this->view->homeLoop($pagination);
    }

    public function content(callable $redirect, array $params): void
    {
        $slug = trim((string)($params['slug'] ?? ''));
        $id = $this->slugger->extractId($slug);
        $preview = $this->canPreview();
        $item = $preview ? $this->findPreviewContent($id) : $this->findPublishedContent($id);

        if ($item === null) {
            $this->notFound();
            return;
        }

        $canonicalSlug = $this->slugger->slug((string)($item['name'] ?? ''), (int)($item['id'] ?? 0));
        if (!$preview && $slug !== $canonicalSlug) {
            $redirect($canonicalSlug, true);
        }

        $this->recordView($item, $preview);

        $this->view->singleContent($item);
    }

    public function search(): void
    {
        $settings = $this->settings->resolved();
        $perPage = $this->resolvePerPage($settings);
        $page = max(1, (int)($_GET['page'] ?? 1));
        $query = $this->sanitizeSearch((string)($_GET['q'] ?? ''));
        $pagination = $this->paginatePublished($page, $perPage, $query);
        $this->view->searchResults($pagination, $query);
    }

    public function termArchive(callable $redirect, array $params): void
    {
        $slug = trim((string)($params['slug'] ?? ''));
        $termId = $this->slugger->extractId($slug);
        $term = $termId > 0 ? $this->terms->find($termId) : null;

        if ($term === null) {
            $this->notFound();
            return;
        }

        $canonicalSlug = $this->slugger->slug((string)($term['name'] ?? ''), (int)($term['id'] ?? 0));
        if ($slug !== $canonicalSlug) {
            $redirect('term/' . $canonicalSlug, true);
        }

        $settings = $this->settings->resolved();
        $perPage = $this->resolvePerPage($settings);
        $page = max(1, (int)($_GET['page'] ?? 1));
        $pagination = $this->paginateTermPublished($termId, $page, $perPage);

        $this->view->termArchive($term, $pagination, 'term/' . $canonicalSlug);
    }

    public function authorArchive(callable $redirect, array $params): void
    {
        $slug = trim((string)($params['slug'] ?? ''));
        $authorId = $this->slugger->extractId($slug);
        $author = $authorId > 0 ? $this->users->find($authorId) : null;

        if ($author === null) {
            $this->notFound();
            return;
        }

        $canonicalSlug = $this->slugger->slug((string)($author['name'] ?? ''), (int)($author['ID'] ?? 0));
        if ($slug !== $canonicalSlug) {
            $redirect('author/' . $canonicalSlug, true);
        }

        $settings = $this->settings->resolved();
        $perPage = $this->resolvePerPage($settings);
        $page = max(1, (int)($_GET['page'] ?? 1));
        $pagination = $this->paginateAuthorPublished($authorId, $page, $perPage);
        $this->view->authorArchive([
            'id' => (int)($author['ID'] ?? 0),
            'name' => (string)($author['name'] ?? ''),
        ], $pagination, 'author/' . $canonicalSlug);
    }


    public function notFound(): void
    {
        $this->view->notFound();
    }

    public function account(callable $redirect): void
    {
        if (!$this->auth->check()) {
            $redirect('auth/login');
        }

        $user = $this->auth->user();
        if (!is_array($user)) {
            $redirect('auth/login');
        }

        $this->view->account($user);
    }


    public function robotsTxt(): void
    {
        header('Content-Type: text/plain; charset=utf-8');
        echo "User-agent: *\n";
        echo "Allow: /\n";
        echo 'Sitemap: ' . $this->absoluteUrl('sitemap.xml') . "\n";
    }

    public function feed(): void
    {
        $settings = $this->settings->resolved();
        $perPage = $this->resolvePerPage($settings);
        $items = $this->paginatePublished(1, $perPage)['data'] ?? [];
        $title = trim((string)($settings['sitename'] ?? 'TinyCMS'));
        $description = trim((string)($settings['meta_description'] ?? ''));
        $link = $this->absoluteUrl('');
        $buildDate = '';

        header('Content-Type: application/rss+xml; charset=utf-8');
        $xml = [
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<rss version="2.0" xmlns:media="http://search.yahoo.com/mrss/">',
            '  <channel>',
            '    <title>' . $this->xml($title !== '' ? $title : 'TinyCMS') . '</title>',
            '    <link>' . $this->xml($link) . '</link>',
            '    <description>' . $this->xml($description !== '' ? $description : ($title !== '' ? $title : 'TinyCMS')) . '</description>',
        ];

        foreach ($items as $item) {
            $id = (int)($item['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }
            $slug = $this->slugger->slug((string)($item['name'] ?? ''), $id);
            $url = $this->absoluteUrl($slug);
            $date = $this->rssDate((string)($item['updated'] ?? $item['created'] ?? ''));
            if ($buildDate === '' && $date !== '') {
                $buildDate = $date;
            }
            $xml[] = '    <item>';
            $xml[] = '      <title>' . $this->xml(trim((string)($item['name'] ?? ''))) . '</title>';
            $xml[] = '      <link>' . $this->xml($url) . '</link>';
            $xml[] = '      <guid isPermaLink="true">' . $this->xml($url) . '</guid>';
            if ($date !== '') {
                $xml[] = '      <pubDate>' . $this->xml($date) . '</pubDate>';
            }
            $excerpt = $this->plainText(Shortcode::render((string)($item['excerpt'] ?? '')));
            if ($excerpt === '') {
                $excerpt = $this->plainText(Shortcode::render((string)($item['body'] ?? '')));
            }
            if ($excerpt !== '') {
                $xml[] = '      <description>' . $this->xml($excerpt) . '</description>';
            }
            $thumbnail = $this->feedThumbnailUrl((string)($item['thumbnail'] ?? ''));
            if ($thumbnail !== '') {
                $xml[] = '      <media:thumbnail url="' . $this->xml($thumbnail) . '" />';
            }
            $xml[] = '    </item>';
        }

        if ($buildDate !== '') {
            $xml[] = '    <lastBuildDate>' . $this->xml($buildDate) . '</lastBuildDate>';
        }
        $xml[] = '  </channel>';
        $xml[] = '</rss>';
        echo implode("\n", $xml);
    }

    public function sitemapIndex(): void
    {
        $contentChunks = $this->sitemapContentChunkCount();
        $termChunks = $this->sitemapTermChunkCount();
        $items = [];

        for ($chunk = 1; $chunk <= $contentChunks; $chunk++) {
            $items[] = $this->absoluteUrl('sitemap-content' . $chunk . '.xml');
        }

        for ($chunk = 1; $chunk <= $termChunks; $chunk++) {
            $items[] = $this->absoluteUrl('sitemap-terms' . $chunk . '.xml');
        }

        header('Content-Type: application/xml; charset=utf-8');
        echo '<?xml version="1.0" encoding="UTF-8"?>';
        echo '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        foreach ($items as $item) {
            echo '<sitemap><loc>' . $this->xml($item) . '</loc></sitemap>';
        }
        echo '</sitemapindex>';
    }

    public function sitemapContent(array $params): void
    {
        $chunk = max(1, (int)($params['chunk'] ?? 0));
        $rows = $this->sitemapContentChunk($chunk);
        $this->renderSitemapUrlSet(array_map(function (array $row): array {
            $id = (int)($row['id'] ?? 0);
            $slug = $this->slugger->slug((string)($row['name'] ?? ''), $id);
            return [
                'loc' => $this->absoluteUrl($slug),
                'lastmod' => (string)($row['updated'] ?? $row['created'] ?? ''),
            ];
        }, $rows));
    }

    public function sitemapTerms(array $params): void
    {
        $chunk = max(1, (int)($params['chunk'] ?? 0));
        $rows = $this->sitemapTermChunk($chunk);
        $this->renderSitemapUrlSet(array_map(function (array $row): array {
            $id = (int)($row['id'] ?? 0);
            $slug = $this->slugger->slug((string)($row['name'] ?? ''), $id);
            return [
                'loc' => $this->absoluteUrl('term/' . $slug),
                'lastmod' => (string)($row['lastmod'] ?? ''),
            ];
        }, $rows));
    }

    private function resolvePerPage(array $settings): int
    {
        $value = (int)($settings['front_posts_per_page'] ?? APP_POSTS_PER_PAGE);
        return max(1, min(100, $value));
    }

    private function resolvedSettings(): array
    {
        return array_replace($this->settings->resolved(), $this->resolvedSettings);
    }

    private function findPublishedContent(int $id): ?array
    {
        return $this->findContent($id, false);
    }

    private function findPreviewContent(int $id): ?array
    {
        return $this->findContent($id, true);
    }

    private function findContent(int $id, bool $includeUnpublished): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $builder = $this->query
            ->from('content', 'c')
            ->select(['c.id', 'c.name', 'c.excerpt', 'c.body', 'c.created', 'c.updated', 'c.thumbnail', 'c.author', 'c.type', 'c.comments_enabled'])
            ->selectRaw('u.name AS author_name')
            ->selectRaw('m.path AS thumbnail_path')
            ->selectRaw('m.name AS thumbnail_name')
            ->leftJoin('users', 'u', 'u.id', '=', 'c.author')
            ->leftJoin('media', 'm', 'm.id', '=', 'c.thumbnail')
            ->where('c.id', $id);

        if (!$includeUnpublished) {
            ContentService::publicScope($builder, 'c', $this->now());
        }

        $item = $builder->first();

        if ($item === null) {
            return null;
        }

        $item = $this->withThumbnail($item);
        $item['terms'] = $this->terms->listByContent((int)$item['id']);
        return $item;
    }

    private function canPreview(): bool
    {
        $preview = trim((string)($_GET['preview'] ?? ''));
        return $preview !== '' && $preview !== '0' && $this->auth->isAdmin();
    }

    private function recordView(array $item, bool $contentPreview = false): void
    {
        if ($contentPreview || $this->customizerPreview()) {
            return;
        }

        $this->contentStats->recordView((int)($item['id'] ?? 0), $this->ipAddress());
    }

    private function customizerPreview(): bool
    {
        return trim((string)($_GET['theme_preview'] ?? '')) !== '' && $this->auth->isAdmin();
    }

    private function paginatePublished(int $page, int $perPage, string $search = ''): array
    {
        $search = $this->sanitizeSearch($search);
        return $this->paginatePublishedContent($page, $perPage, null, $search);
    }

    private function paginateTermPublished(int $termId, int $page, int $perPage): array
    {
        return $this->paginatePublishedContent($page, $perPage, static function ($builder) use ($termId): void {
            $builder
                ->innerJoin('content_terms', 'ct', 'ct.content', '=', 'c.id')
                ->where('ct.term', $termId);
        });
    }

    private function paginateAuthorPublished(int $authorId, int $page, int $perPage): array
    {
        return $this->paginatePublishedContent($page, $perPage, static function ($builder) use ($authorId): void {
            $builder->where('c.author', $authorId);
        });
    }

    private function paginatePublishedContent(
        int $page,
        int $perPage,
        ?callable $scope = null,
        string $search = ''
    ): array {
        $page = max(1, $page);
        $perPage = max(1, $perPage);

        $builder = $this->query
            ->from('content', 'c')
            ->select(['c.id', 'c.name', 'c.excerpt', 'c.body', 'c.created', 'c.updated', 'c.thumbnail', 'c.author', 'c.type', 'c.comments_enabled'])
            ->selectRaw('u.name AS author_name')
            ->selectRaw('m.path AS thumbnail_path')
            ->selectRaw('m.name AS thumbnail_name')
            ->leftJoin('users', 'u', 'u.id', '=', 'c.author')
            ->leftJoin('media', 'm', 'm.id', '=', 'c.thumbnail')
            ->search(['c.name', 'c.excerpt', 'c.body'], $search)
            ->orderByRaw('COALESCE(c.updated, c.created) DESC, c.id DESC');

        ContentService::publicScope($builder, 'c', $this->now());

        if ($scope !== null) {
            $scope($builder);
        }

        $pagination = $builder->paginate($page, $perPage);
        $pagination['data'] = $this->withThumbnails((array)($pagination['data'] ?? []));

        return $pagination;
    }

    private function withThumbnails(array $rows): array
    {
        return array_map(fn(array $row): array => $this->withThumbnail($row), $rows);
    }

    private function withThumbnail(array $row): array
    {
        $path = trim((string)($row['thumbnail_path'] ?? ''));
        $row['thumbnail'] = $path;
        return $row;
    }

    private function sanitizeSearch(string $value): string
    {
        return mb_substr(trim($value), 0, 100);
    }

    private function sitemapContentChunkCount(): int
    {
        $builder = $this->query
            ->from('content', 'c')
            ->select('c.id');

        $count = ContentService::publicScope($builder, 'c', $this->now())->count();

        return max(1, (int)ceil($count / self::SITEMAP_CHUNK_SIZE));
    }

    private function sitemapTermChunkCount(): int
    {
        $builder = $this->query
            ->from('terms', 't')
            ->innerJoin('content_terms', 'ct', 'ct.term', '=', 't.id')
            ->innerJoin('content', 'c', 'c.id', '=', 'ct.content');

        $count = ContentService::publicScope($builder, 'c', $this->now())
            ->count($this->query->raw('COUNT(DISTINCT t.id)'));

        return max(1, (int)ceil($count / self::SITEMAP_CHUNK_SIZE));
    }

    private function sitemapContentChunk(int $chunk): array
    {
        $builder = $this->query
            ->from('content', 'c')
            ->select(['c.id', 'c.name', 'c.created', 'c.updated'])
            ->orderBy('c.id', 'ASC')
            ->limit(self::SITEMAP_CHUNK_SIZE, ($chunk - 1) * self::SITEMAP_CHUNK_SIZE);

        return ContentService::publicScope($builder, 'c', $this->now())->get();
    }

    private function sitemapTermChunk(int $chunk): array
    {
        $builder = $this->query
            ->from('terms', 't')
            ->select(['t.id', 't.name'])
            ->selectRaw('MAX(COALESCE(c.updated, c.created)) AS lastmod')
            ->innerJoin('content_terms', 'ct', 'ct.term', '=', 't.id')
            ->innerJoin('content', 'c', 'c.id', '=', 'ct.content')
            ->groupBy(['t.id', 't.name'])
            ->orderBy('t.id', 'ASC')
            ->limit(self::SITEMAP_CHUNK_SIZE, ($chunk - 1) * self::SITEMAP_CHUNK_SIZE);

        return ContentService::publicScope($builder, 'c', $this->now())->get();
    }

    private function renderSitemapUrlSet(array $items): void
    {
        header('Content-Type: application/xml; charset=utf-8');
        echo '<?xml version="1.0" encoding="UTF-8"?>';
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        foreach ($items as $item) {
            $loc = trim((string)($item['loc'] ?? ''));
            if ($loc === '') {
                continue;
            }
            $lastmod = $this->sitemapDate((string)($item['lastmod'] ?? ''));
            echo '<url><loc>' . $this->xml($loc) . '</loc>';
            if ($lastmod !== '') {
                echo '<lastmod>' . $this->xml($lastmod) . '</lastmod>';
            }
            echo '</url>';
        }
        echo '</urlset>';
    }

    private function now(): string
    {
        return date('Y-m-d H:i:s');
    }

    private function ipAddress(): string
    {
        return trim((string)($_SERVER['REMOTE_ADDR'] ?? ''));
    }

    private function absoluteUrl(string $path): string
    {
        return RequestContext::scheme() . '://' . RequestContext::authority() . RequestContext::path($path);
    }

    private function xml(string $value): string
    {
        return esc_xml($value);
    }

    private function sitemapDate(string $value): string
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            return '';
        }
        $timestamp = strtotime($trimmed);
        if ($timestamp === false) {
            return '';
        }
        return gmdate('c', $timestamp);
    }

    private function rssDate(string $value): string
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            return '';
        }
        $timestamp = strtotime($trimmed);
        if ($timestamp === false) {
            return '';
        }
        return gmdate(\DATE_RSS, $timestamp);
    }

    private function plainText(string $value): string
    {
        return trim(preg_replace('/\s+/u', ' ', strip_tags(html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8'))) ?? '');
    }

    private function feedThumbnailUrl(string $path): string
    {
        $trimmed = trim($path);
        if ($trimmed === '') {
            return '';
        }

        return $this->absoluteUrl(Media::bySize($trimmed, 'medium'));
    }
}
