<?php
declare(strict_types=1);

namespace App\Service\Application;

use App\Service\Infrastructure\Db\Connection;
use App\Service\Infrastructure\Db\SchemaRules;
use App\Service\Infrastructure\Db\Table;

final class ContentStats
{
    private \PDO $pdo;
    private SchemaRules $schemaRules;

    public function __construct()
    {
        $this->pdo = Connection::get();
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
            "INSERT INTO $contentStatsTable (content, ip_address, last_visit)",
            'VALUES (:content, :ip_address, :last_visit)',
            'ON DUPLICATE KEY UPDATE last_visit = :last_visit_update',
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
        $contentStatsTable = Table::name('content_stats');
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM $contentStatsTable WHERE content = :content");
        $stmt->execute(['content' => $contentId]);

        return (int)($stmt->fetchColumn() ?: 0);
    }

    private function fetchLastVisit(int $contentId): string
    {
        $contentStatsTable = Table::name('content_stats');
        $stmt = $this->pdo->prepare("SELECT MAX(last_visit) FROM $contentStatsTable WHERE content = :content");
        $stmt->execute(['content' => $contentId]);

        return trim((string)($stmt->fetchColumn() ?: ''));
    }
}
