<?php
declare(strict_types=1);

namespace App\Controller\Front;

use App\Service\Auth\Auth;
use App\Service\Application\Settings;
use App\Service\Application\Term;
use App\Service\Application\User;
use App\Service\Infrastructure\Db\Connection;
use App\Service\Infrastructure\Db\Table;
use App\Service\Support\Media;
use App\Service\Support\RequestContext;
use App\Service\Support\Slugger;
use App\View\FrontView;

final class Front
{
    private const SITEMAP_CHUNK_SIZE = 5000;
    private \PDO $pdo;
    private Slugger $slugger;

    public function __construct(
        private FrontView $view,
        private Settings $settings,
        private Term $terms,
        private User $users,
        private Auth $auth
    ) {
        $this->pdo = Connection::get();
        $this->slugger = new Slugger();
    }

    public function home(): void
    {
        $settings = $this->settings->resolved();
        $perPage = $this->resolvePerPage($settings);

        $contentId = (int)($settings['front_home_content'] ?? 0);
        $item = $contentId > 0 ? $this->findPublishedContent($contentId) : null;
        if ($item !== null) {
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
            $excerpt = $this->plainText((string)($item['excerpt'] ?? ''));
            if ($excerpt === '') {
                $excerpt = $this->plainText((string)($item['body'] ?? ''));
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

        $contentTable = Table::name('content');
        $usersTable = Table::name('users');
        $mediaTable = Table::name('media');
        if (!$includeUnpublished) {
            $stmt = $this->pdo->prepare(implode("\n", [
                'SELECT c.id, c.name, c.excerpt, c.body, c.created, c.updated, c.thumbnail, c.author, c.type,',
                "u.name AS author_name, m.path AS thumbnail_path, m.name AS thumbnail_name",
                "FROM $contentTable c",
                "LEFT JOIN $usersTable u ON u.id = c.author",
                "LEFT JOIN $mediaTable m ON m.id = c.thumbnail",
                'WHERE c.id = :id AND c.status = :status AND c.created <= :now',
                'LIMIT 1',
            ]));
            $stmt->execute(['id' => $id, 'status' => 'published', 'now' => $this->now()]);
        } else {
            $stmt = $this->pdo->prepare(implode("\n", [
                'SELECT c.id, c.name, c.excerpt, c.body, c.created, c.updated, c.thumbnail, c.author, c.type,',
                "u.name AS author_name, m.path AS thumbnail_path, m.name AS thumbnail_name",
                "FROM $contentTable c",
                "LEFT JOIN $usersTable u ON u.id = c.author",
                "LEFT JOIN $mediaTable m ON m.id = c.thumbnail",
                'WHERE c.id = :id',
                'LIMIT 1',
            ]));
            $stmt->execute(['id' => $id]);
        }
        $item = $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;

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

    private function paginatePublished(int $page, int $perPage, string $search = ''): array
    {
        $search = $this->sanitizeSearch($search);
        return $this->paginatePublishedContent(
            $page,
            $perPage,
            [],
            $search !== '' ? ['(c.name LIKE :search OR c.excerpt LIKE :search OR c.body LIKE :search)'] : [],
            $search !== '' ? ['search' => '%' . $search . '%'] : []
        );
    }

    private function paginateTermPublished(int $termId, int $page, int $perPage): array
    {
        $contentTermsTable = Table::name('content_terms');
        return $this->paginatePublishedContent(
            $page,
            $perPage,
            ["INNER JOIN $contentTermsTable ct ON ct.content = c.id"],
            ['ct.term = :term'],
            ['term' => $termId],
            ['term']
        );
    }

    private function paginateAuthorPublished(int $authorId, int $page, int $perPage): array
    {
        return $this->paginatePublishedContent(
            $page,
            $perPage,
            [],
            ['c.author = :author'],
            ['author' => $authorId],
            ['author']
        );
    }

    private function paginatePublishedContent(
        int $page,
        int $perPage,
        array $joins = [],
        array $conditions = [],
        array $params = [],
        array $intParams = []
    ): array {
        $contentTable = Table::name('content');
        $usersTable = Table::name('users');
        $mediaTable = Table::name('media');
        $page = max(1, $page);
        $perPage = max(1, $perPage);
        $params = array_merge(['status' => 'published', 'now' => $this->now()], $params);
        $where = 'WHERE ' . implode(' AND ', array_merge(['c.status = :status', 'c.created <= :now'], $conditions));

        $countStmt = $this->pdo->prepare(implode("\n", array_merge([
            'SELECT COUNT(*)',
            "FROM $contentTable c",
        ], $joins, [$where])));
        $this->bindParams($countStmt, $params, $intParams);
        $countStmt->execute();
        $total = (int)($countStmt->fetchColumn() ?: 0);
        $page = min($page, max(1, (int)ceil($total / $perPage)));
        $offset = ($page - 1) * $perPage;

        $stmt = $this->pdo->prepare(implode("\n", array_merge([
            'SELECT c.id, c.name, c.excerpt, c.body, c.created, c.updated, c.thumbnail, c.author, c.type,',
            "u.name AS author_name, m.path AS thumbnail_path, m.name AS thumbnail_name",
            "FROM $contentTable c",
        ], $joins, [
            "LEFT JOIN $usersTable u ON u.id = c.author",
            "LEFT JOIN $mediaTable m ON m.id = c.thumbnail",
            $where,
            'ORDER BY COALESCE(c.updated, c.created) DESC, c.id DESC',
            'LIMIT :limit OFFSET :offset',
        ])));
        $this->bindParams($stmt, $params, $intParams);
        $stmt->bindValue(':limit', $perPage, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();

        return $this->paginationPayload($this->withThumbnails($stmt->fetchAll(\PDO::FETCH_ASSOC)), $page, $perPage, $total);
    }

    private function paginationPayload(array $rows, int $page, int $perPage, int $total): array
    {
        $totalPages = max(1, (int)ceil($total / $perPage));

        return [
            'data' => $rows,
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'total_pages' => $totalPages,
        ];
    }

    private function bindParams(\PDOStatement $stmt, array $params, array $intParams = []): void
    {
        foreach ($params as $key => $value) {
            $type = in_array($key, $intParams, true) ? \PDO::PARAM_INT : \PDO::PARAM_STR;
            $stmt->bindValue(':' . $key, $value, $type);
        }
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
        $contentTable = Table::name('content');
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM $contentTable c WHERE c.status = :status AND c.created <= :now");
        $stmt->execute(['status' => 'published', 'now' => $this->now()]);
        return max(1, (int)ceil(((int)($stmt->fetchColumn() ?: 0)) / self::SITEMAP_CHUNK_SIZE));
    }

    private function sitemapTermChunkCount(): int
    {
        $termsTable = Table::name('terms');
        $contentTable = Table::name('content');
        $contentTermsTable = Table::name('content_terms');
        $stmt = $this->pdo->prepare(implode("\n", [
            'SELECT COUNT(DISTINCT t.id)',
            "FROM $termsTable t",
            "INNER JOIN $contentTermsTable ct ON ct.term = t.id",
            "INNER JOIN $contentTable c ON c.id = ct.content",
            'WHERE c.status = :status AND c.created <= :now',
        ]));
        $stmt->execute(['status' => 'published', 'now' => $this->now()]);
        return max(1, (int)ceil(((int)($stmt->fetchColumn() ?: 0)) / self::SITEMAP_CHUNK_SIZE));
    }

    private function sitemapContentChunk(int $chunk): array
    {
        $contentTable = Table::name('content');
        $stmt = $this->pdo->prepare(implode("\n", [
            'SELECT c.id, c.name, c.created, c.updated',
            "FROM $contentTable c",
            'WHERE c.status = :status AND c.created <= :now',
            'ORDER BY c.id ASC',
            'LIMIT :limit OFFSET :offset',
        ]));
        $stmt->bindValue(':status', 'published');
        $stmt->bindValue(':now', $this->now());
        $stmt->bindValue(':limit', self::SITEMAP_CHUNK_SIZE, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', ($chunk - 1) * self::SITEMAP_CHUNK_SIZE, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }

    private function sitemapTermChunk(int $chunk): array
    {
        $termsTable = Table::name('terms');
        $contentTable = Table::name('content');
        $contentTermsTable = Table::name('content_terms');
        $stmt = $this->pdo->prepare(implode("\n", [
            'SELECT t.id, t.name, MAX(COALESCE(c.updated, c.created)) AS lastmod',
            "FROM $termsTable t",
            "INNER JOIN $contentTermsTable ct ON ct.term = t.id",
            "INNER JOIN $contentTable c ON c.id = ct.content",
            'WHERE c.status = :status AND c.created <= :now',
            'GROUP BY t.id, t.name',
            'ORDER BY t.id ASC',
            'LIMIT :limit OFFSET :offset',
        ]));
        $stmt->bindValue(':status', 'published');
        $stmt->bindValue(':now', $this->now());
        $stmt->bindValue(':limit', self::SITEMAP_CHUNK_SIZE, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', ($chunk - 1) * self::SITEMAP_CHUNK_SIZE, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
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
