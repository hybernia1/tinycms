<?php
declare(strict_types=1);

namespace App\Controller\Front;

use App\Service\Auth\Auth;
use App\Service\Front\Services;
use App\Service\Infrastructure\Db\Connection;
use App\Service\Infrastructure\Db\Table;
use App\Service\Support\RequestContext;
use App\Service\Support\Slugger;
use App\View\FrontView;

final class Front
{
    private const SITEMAP_CHUNK_SIZE = 5000;
    private \PDO $pdo;
    private Slugger $slugger;

    public function __construct(private FrontView $view, private Services $services, private Auth $auth)
    {
        $this->pdo = Connection::get();
        $this->slugger = new Slugger();
    }

    public function home(): void
    {
        $settings = $this->services->settings->resolved();
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

    public function contentLegacy(callable $redirect, array $params): void
    {
        $id = (int)($params['id'] ?? 0);
        $preview = $this->canPreview();
        $item = $preview ? $this->findPreviewContent($id) : $this->findPublishedContent($id);

        if ($item === null) {
            $this->notFound();
            return;
        }

        if ($preview) {
            $this->view->singleContent($item);
            return;
        }

        $redirect($this->slugger->slug((string)($item['name'] ?? ''), (int)($item['id'] ?? 0)), true);
    }

    public function search(): void
    {
        $settings = $this->services->settings->resolved();
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
        $term = $termId > 0 ? $this->services->term->find($termId) : null;

        if ($term === null) {
            $this->notFound();
            return;
        }

        $canonicalSlug = $this->slugger->slug((string)($term['name'] ?? ''), (int)($term['id'] ?? 0));
        if ($slug !== $canonicalSlug) {
            $redirect('term/' . $canonicalSlug, true);
        }

        $settings = $this->services->settings->resolved();
        $perPage = $this->resolvePerPage($settings);
        $page = max(1, (int)($_GET['page'] ?? 1));
        $pagination = $this->paginateTermPublished($termId, $page, $perPage);

        $this->view->termArchive($term, $pagination);
    }

    public function authorArchive(callable $redirect, array $params): void
    {
        $slug = trim((string)($params['slug'] ?? ''));
        $authorId = $this->slugger->extractId($slug);
        $author = $authorId > 0 ? $this->services->user->find($authorId) : null;

        if ($author === null) {
            $this->notFound();
            return;
        }

        $canonicalSlug = $this->slugger->slug((string)($author['name'] ?? ''), (int)($author['ID'] ?? 0));
        if ($slug !== $canonicalSlug) {
            $redirect('author/' . $canonicalSlug, true);
        }

        $settings = $this->services->settings->resolved();
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
        $item['terms'] = $this->services->term->listByContent((int)$item['id']);
        return $item;
    }

    private function canPreview(): bool
    {
        $preview = trim((string)($_GET['preview'] ?? ''));
        return $preview !== '' && $preview !== '0' && $this->auth->isAdmin();
    }

    private function paginatePublished(int $page, int $perPage, string $search = ''): array
    {
        $contentTable = Table::name('content');
        $usersTable = Table::name('users');
        $mediaTable = Table::name('media');
        $search = $this->sanitizeSearch($search);
        $page = max(1, $page);
        $perPage = max(1, $perPage);
        $offset = ($page - 1) * $perPage;

        $where = 'WHERE c.status = :status AND c.created <= :now';
        if ($search !== '') {
            $where .= ' AND (c.name LIKE :search OR c.excerpt LIKE :search OR c.body LIKE :search)';
        }

        $countStmt = $this->pdo->prepare(implode("\n", [
            'SELECT COUNT(*)',
            "FROM $contentTable c",
            $where,
        ]));
        $countStmt->bindValue(':status', 'published');
        $countStmt->bindValue(':now', $this->now());
        if ($search !== '') {
            $countStmt->bindValue(':search', '%' . $search . '%');
        }
        $countStmt->execute();
        $total = (int)($countStmt->fetchColumn() ?: 0);

        $stmt = $this->pdo->prepare(implode("\n", [
            'SELECT c.id, c.name, c.excerpt, c.body, c.created, c.updated, c.thumbnail, c.author, c.type,',
            "u.name AS author_name, m.path AS thumbnail_path, m.name AS thumbnail_name",
            "FROM $contentTable c",
            "LEFT JOIN $usersTable u ON u.id = c.author",
            "LEFT JOIN $mediaTable m ON m.id = c.thumbnail",
            $where,
            'ORDER BY COALESCE(c.updated, c.created) DESC, c.id DESC',
            'LIMIT :limit OFFSET :offset',
        ]));
        $stmt->bindValue(':status', 'published');
        $stmt->bindValue(':now', $this->now());
        if ($search !== '') {
            $stmt->bindValue(':search', '%' . $search . '%');
        }
        $stmt->bindValue(':limit', $perPage, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();

        return $this->paginationPayload($this->withThumbnails($stmt->fetchAll(\PDO::FETCH_ASSOC)), $page, $perPage, $total);
    }

    private function paginateTermPublished(int $termId, int $page, int $perPage): array
    {
        $contentTable = Table::name('content');
        $usersTable = Table::name('users');
        $contentTermsTable = Table::name('content_terms');
        $mediaTable = Table::name('media');
        $page = max(1, $page);
        $perPage = max(1, $perPage);
        $offset = ($page - 1) * $perPage;

        $countStmt = $this->pdo->prepare(implode("\n", [
            'SELECT COUNT(*)',
            "FROM $contentTable c",
            "INNER JOIN $contentTermsTable ct ON ct.content = c.id",
            'WHERE c.status = :status AND ct.term = :term AND c.created <= :now',
        ]));
        $countStmt->execute(['status' => 'published', 'term' => $termId, 'now' => $this->now()]);
        $total = (int)($countStmt->fetchColumn() ?: 0);

        $stmt = $this->pdo->prepare(implode("\n", [
            'SELECT c.id, c.name, c.excerpt, c.body, c.created, c.updated, c.thumbnail, c.author, c.type,',
            "u.name AS author_name, m.path AS thumbnail_path, m.name AS thumbnail_name",
            "FROM $contentTable c",
            "INNER JOIN $contentTermsTable ct ON ct.content = c.id",
            "LEFT JOIN $usersTable u ON u.id = c.author",
            "LEFT JOIN $mediaTable m ON m.id = c.thumbnail",
            'WHERE c.status = :status AND ct.term = :term AND c.created <= :now',
            'ORDER BY COALESCE(c.updated, c.created) DESC, c.id DESC',
            'LIMIT :limit OFFSET :offset',
        ]));
        $stmt->bindValue(':status', 'published');
        $stmt->bindValue(':term', $termId, \PDO::PARAM_INT);
        $stmt->bindValue(':now', $this->now());
        $stmt->bindValue(':limit', $perPage, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();

        return $this->paginationPayload($this->withThumbnails($stmt->fetchAll(\PDO::FETCH_ASSOC)), $page, $perPage, $total);
    }

    private function paginateAuthorPublished(int $authorId, int $page, int $perPage): array
    {
        $contentTable = Table::name('content');
        $usersTable = Table::name('users');
        $mediaTable = Table::name('media');
        $page = max(1, $page);
        $perPage = max(1, $perPage);
        $offset = ($page - 1) * $perPage;

        $countStmt = $this->pdo->prepare(implode("\n", [
            'SELECT COUNT(*)',
            "FROM $contentTable c",
            'WHERE c.status = :status AND c.author = :author AND c.created <= :now',
        ]));
        $countStmt->execute(['status' => 'published', 'author' => $authorId, 'now' => $this->now()]);
        $total = (int)($countStmt->fetchColumn() ?: 0);

        $stmt = $this->pdo->prepare(implode("\n", [
            'SELECT c.id, c.name, c.excerpt, c.body, c.created, c.updated, c.thumbnail, c.author, c.type,',
            "u.name AS author_name, m.path AS thumbnail_path, m.name AS thumbnail_name",
            "FROM $contentTable c",
            "LEFT JOIN $usersTable u ON u.id = c.author",
            "LEFT JOIN $mediaTable m ON m.id = c.thumbnail",
            'WHERE c.status = :status AND c.author = :author AND c.created <= :now',
            'ORDER BY COALESCE(c.updated, c.created) DESC, c.id DESC',
            'LIMIT :limit OFFSET :offset',
        ]));
        $stmt->bindValue(':status', 'published');
        $stmt->bindValue(':author', $authorId, \PDO::PARAM_INT);
        $stmt->bindValue(':now', $this->now());
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
            'page' => min($page, $totalPages),
            'per_page' => $perPage,
            'total' => $total,
            'total_pages' => $totalPages,
        ];
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
        $scriptName = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));
        $baseDir = trim(dirname($scriptName), '/.');
        $prefix = $baseDir === '' ? '' : '/' . $baseDir;
        $cleanPath = ltrim($path, '/');
        return RequestContext::scheme() . '://' . RequestContext::authority() . $prefix . '/' . $cleanPath;
    }

    private function xml(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
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
}
