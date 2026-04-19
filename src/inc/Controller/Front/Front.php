<?php
declare(strict_types=1);

namespace App\Controller\Front;

use App\Service\Front\Services;
use App\Service\Infrastructure\Db\Connection;
use App\Service\Infrastructure\Db\Table;
use App\Service\Support\Slugger;
use App\View\FrontView;

final class Front
{
    private \PDO $pdo;
    private Slugger $slugger;

    public function __construct(private FrontView $view, private Services $services)
    {
        $this->pdo = Connection::get();
        $this->slugger = new Slugger();
    }

    public function home(): void
    {
        $settings = $this->services->settings->resolved();
        $mode = (string)($settings['front_home_mode'] ?? 'latest');
        $perPage = $this->resolvePerPage($settings);

        if ($mode === 'content') {
            $contentId = (int)($settings['front_home_content'] ?? 0);
            $item = $contentId > 0 ? $this->findPublishedContent($contentId) : null;
            if ($item !== null) {
                $this->view->homeContent($item);
                return;
            }
        }

        $page = max(1, (int)($_GET['page'] ?? 1));
        $pagination = $this->paginatePublished($page, $perPage);
        $this->view->homeLoop($pagination);
    }

    public function content(callable $redirect, array $params): void
    {
        $slug = trim((string)($params['slug'] ?? ''));
        $id = $this->slugger->extractId($slug);
        $item = $this->findPublishedContent($id);

        if ($item === null) {
            http_response_code(404);
            echo '404';
            return;
        }

        $canonicalSlug = $this->slugger->slug((string)($item['name'] ?? ''), (int)($item['id'] ?? 0));
        if ($slug !== $canonicalSlug) {
            $redirect($canonicalSlug, true);
        }

        $this->view->singleContent($item);
    }

    public function contentLegacy(callable $redirect, array $params): void
    {
        $id = (int)($params['id'] ?? 0);
        $item = $this->findPublishedContent($id);

        if ($item === null) {
            http_response_code(404);
            echo '404';
            return;
        }

        $redirect($this->slugger->slug((string)($item['name'] ?? ''), (int)($item['id'] ?? 0)), true);
    }

    public function termArchive(callable $redirect, array $params): void
    {
        $slug = trim((string)($params['slug'] ?? ''));
        $termId = $this->slugger->extractId($slug);
        $term = $termId > 0 ? $this->services->term->find($termId) : null;

        if ($term === null) {
            http_response_code(404);
            echo '404';
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

    private function resolvePerPage(array $settings): int
    {
        $value = (int)($settings['front_posts_per_page'] ?? APP_POSTS_PER_PAGE);
        return max(1, min(100, $value));
    }

    private function findPublishedContent(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $contentTable = Table::name('content');
        $usersTable = Table::name('users');
        $mediaTable = Table::name('media');
        $stmt = $this->pdo->prepare(implode("\n", [
            'SELECT c.id, c.name, c.excerpt, c.body, c.created, c.updated, c.thumbnail, c.author, c.type,',
            "u.name AS author_name, m.path AS thumbnail_path, m.name AS thumbnail_name",
            "FROM $contentTable c",
            "LEFT JOIN $usersTable u ON u.id = c.author",
            "LEFT JOIN $mediaTable m ON m.id = c.thumbnail",
            'WHERE c.id = :id AND c.status = :status',
            'LIMIT 1',
        ]));
        $stmt->execute(['id' => $id, 'status' => 'published']);
        $item = $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;

        if ($item === null) {
            return null;
        }

        $item = $this->withThumbnail($item);
        $item['terms'] = $this->services->term->listByContent((int)$item['id']);
        return $item;
    }

    private function paginatePublished(int $page, int $perPage): array
    {
        $contentTable = Table::name('content');
        $usersTable = Table::name('users');
        $mediaTable = Table::name('media');
        $page = max(1, $page);
        $perPage = max(1, $perPage);
        $offset = ($page - 1) * $perPage;

        $countStmt = $this->pdo->prepare("SELECT COUNT(*) FROM $contentTable WHERE status = :status");
        $countStmt->execute(['status' => 'published']);
        $total = (int)($countStmt->fetchColumn() ?: 0);

        $stmt = $this->pdo->prepare(implode("\n", [
            'SELECT c.id, c.name, c.excerpt, c.body, c.created, c.updated, c.thumbnail, c.author, c.type,',
            "u.name AS author_name, m.path AS thumbnail_path, m.name AS thumbnail_name",
            "FROM $contentTable c",
            "LEFT JOIN $usersTable u ON u.id = c.author",
            "LEFT JOIN $mediaTable m ON m.id = c.thumbnail",
            'WHERE c.status = :status',
            'ORDER BY COALESCE(c.updated, c.created) DESC, c.id DESC',
            'LIMIT :limit OFFSET :offset',
        ]));
        $stmt->bindValue(':status', 'published');
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
            'WHERE c.status = :status AND ct.term = :term',
        ]));
        $countStmt->execute(['status' => 'published', 'term' => $termId]);
        $total = (int)($countStmt->fetchColumn() ?: 0);

        $stmt = $this->pdo->prepare(implode("\n", [
            'SELECT c.id, c.name, c.excerpt, c.body, c.created, c.updated, c.thumbnail, c.author, c.type,',
            "u.name AS author_name, m.path AS thumbnail_path, m.name AS thumbnail_name",
            "FROM $contentTable c",
            "INNER JOIN $contentTermsTable ct ON ct.content = c.id",
            "LEFT JOIN $usersTable u ON u.id = c.author",
            "LEFT JOIN $mediaTable m ON m.id = c.thumbnail",
            'WHERE c.status = :status AND ct.term = :term',
            'ORDER BY COALESCE(c.updated, c.created) DESC, c.id DESC',
            'LIMIT :limit OFFSET :offset',
        ]));
        $stmt->bindValue(':status', 'published');
        $stmt->bindValue(':term', $termId, \PDO::PARAM_INT);
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
}
