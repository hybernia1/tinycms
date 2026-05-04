<?php
declare(strict_types=1);

namespace App\Service\Application;

use App\Service\Infrastructure\Db\Connection;
use App\Service\Infrastructure\Db\Query;
use App\Service\Infrastructure\Db\SchemaRules;
use App\Service\Infrastructure\Db\Table;

final class ContentStats
{
    private \PDO $pdo;
    private Query $query;
    private SchemaRules $schemaRules;

    public function __construct()
    {
        $this->pdo = Connection::get();
        $this->query = new Query($this->pdo);
        $this->schemaRules = new SchemaRules();
    }

    public function recordView(int $contentId, string $ipAddress): void
    {
        $ipAddress = $this->schemaRules->truncate('content_stats', 'ip_address', trim($ipAddress), 45);
        if ($contentId <= 0 || $ipAddress === '') {
            return;
        }

        try {
            $this->upsert($contentId, $ipAddress);
        } catch (\PDOException) {
            return;
        }
    }

    public function viewsCount(int $contentId): int
    {
        if ($contentId <= 0) {
            return 0;
        }

        try {
            return $this->fetchViewsCount($contentId);
        } catch (\PDOException) {
            return 0;
        }
    }

    public function lastVisit(int $contentId): string
    {
        if ($contentId <= 0) {
            return '';
        }

        try {
            return $this->fetchLastVisit($contentId);
        } catch (\PDOException) {
            return '';
        }
    }

    private function upsert(int $contentId, string $ipAddress): void
    {
        $contentStatsTable = Table::name('content_stats');
        $now = date('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare(implode("\n", [
            "INSERT INTO $contentStatsTable (content, ip_address, visits, last_visit)",
            'VALUES (:content, :ip_address, 1, :last_visit)',
            'ON DUPLICATE KEY UPDATE visits = visits + 1, last_visit = :last_visit_update',
        ]));
        $stmt->execute([
            'content' => $contentId,
            'ip_address' => $ipAddress,
            'last_visit' => $now,
            'last_visit_update' => $now,
        ]);
    }

    private function fetchViewsCount(int $contentId): int
    {
        return $this->query
            ->from('content_stats')
            ->where('content', $contentId)
            ->count();
    }

    private function fetchLastVisit(int $contentId): string
    {
        $value = $this->query
            ->from('content_stats')
            ->where('content', $contentId)
            ->value($this->query->raw('MAX(last_visit)'));

        return trim((string)($value ?: ''));
    }
}
