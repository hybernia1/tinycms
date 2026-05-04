<?php
declare(strict_types=1);

namespace App\Service\Application;

use App\Service\Infrastructure\Db\Connection;
use App\Service\Infrastructure\Db\Query;
use App\Service\Infrastructure\Db\SchemaRules;
use App\Service\Support\I18n;
use InvalidArgumentException;

final class Term
{
    private Query $query;
    private SchemaRules $schemaRules;

    public function __construct()
    {
        $this->query = new Query(Connection::get());
        $this->schemaRules = new SchemaRules();
    }

    public function paginate(int $page = 1, int $perPage = 10, string $search = '', string $status = 'all'): array
    {
        if ($status === 'unassigned') {
            return $this->paginateUnassigned($page, $perPage, $search);
        }

        return $this->query
            ->from('terms', 't')
            ->select(['t.id', 't.name', 't.created', 't.updated'])
            ->search(['t.name'], $search)
            ->orderBy('t.id', 'DESC')
            ->paginate($page, $perPage);
    }

    public function statusCounts(): array
    {
        $all = $this->query->from('terms', 't')->count();
        $unassigned = $this->query
            ->from('terms', 't')
            ->whereNotExists('content_terms', 'ct', 'ct.term', '=', 't.id')
            ->count();

        return [
            'all' => $all,
            'unassigned' => $unassigned,
        ];
    }

    public function find(int $id): ?array
    {
        $rows = $this->query->select('terms', ['id', 'name', 'created', 'updated'], ['id' => $id]);
        return $rows[0] ?? null;
    }

    public function save(array $input, ?int $id = null): array
    {
        $name = $this->schemaRules->truncate(
            'terms',
            'name',
            trim((string)($input['name'] ?? '')),
            255
        );
        $errors = [];

        if ($name === '') {
            $errors['name'] = I18n::t('validation.name_required');
        }

        if ($name !== '' && $this->existsByName($name, $id)) {
            $errors['name'] = I18n::t('terms.name_exists');
        }

        $lengthErrors = $this->schemaRules->validate('terms', [
            'name' => $name,
        ], [
            'name' => 'name',
        ]);

        foreach ($lengthErrors as $field => $message) {
            if (!isset($errors[$field])) {
                $errors[$field] = $message;
            }
        }

        if ($errors !== []) {
            return ['success' => false, 'errors' => $errors];
        }

        $payload = [
            'name' => $name,
        ];

        try {
            if ($id === null) {
                $newId = $this->query->insert('terms', $payload);
                return ['success' => $newId > 0, 'id' => $newId, 'errors' => []];
            }

            $updated = $this->query->update('terms', $payload, ['id' => $id]);
            return ['success' => $updated >= 0, 'id' => $id, 'errors' => []];
        } catch (InvalidArgumentException $e) {
            return ['success' => false, 'errors' => ['_global' => $e->getMessage()]];
        }
    }

    public function delete(int $id): bool
    {
        return $this->query->delete('terms', ['id' => $id]) > 0;
    }

    public function search(string $query, int $limit = 15): array
    {
        $needle = trim($query);
        if ($limit <= 0) {
            return [];
        }

        if ($needle === '') {
            $rows = $this->query
                ->from('terms')
                ->select(['id', 'name'])
                ->orderBy('name', 'ASC')
                ->limit(min($limit, 50))
                ->get();
            return array_map(static fn(array $item): array => [
                'id' => (int)($item['id'] ?? 0),
                'name' => (string)($item['name'] ?? ''),
            ], $rows);
        }

        return array_map(static fn(array $item): array => [
            'id' => (int)($item['id'] ?? 0),
            'name' => (string)($item['name'] ?? ''),
        ], $this->query
            ->from('terms')
            ->select(['id', 'name'])
            ->search(['name'], $needle)
            ->orderBy('name', 'ASC')
            ->limit(min($limit, 50))
            ->get());
    }


    public function listByContent(int $contentId): array
    {
        if ($contentId <= 0) {
            return [];
        }

        return array_map(static fn(array $row): array => [
            'id' => (int)($row['id'] ?? 0),
            'name' => trim((string)($row['name'] ?? '')),
        ], $this->query
            ->from('terms', 't')
            ->distinct()
            ->select(['t.id', 't.name'])
            ->innerJoin('content_terms', 'ct', 'ct.term', '=', 't.id')
            ->where('ct.content', $contentId)
            ->orderBy('t.name', 'ASC')
            ->get());
    }

    public function namesByContent(int $contentId): array
    {
        if ($contentId <= 0) {
            return [];
        }

        $rows = $this->query
            ->from('terms', 't')
            ->distinct()
            ->select('t.name')
            ->innerJoin('content_terms', 'ct', 'ct.term', '=', 't.id')
            ->where('ct.content', $contentId)
            ->orderBy('t.name', 'ASC')
            ->get();

        return array_values(array_filter(array_map(static fn(array $row): string => trim((string)($row['name'] ?? '')), $rows)));
    }

    public function syncContentTerms(int $contentId, string $rawTerms): void
    {
        if ($contentId <= 0) {
            return;
        }

        $names = $this->normalizeTerms($rawTerms);
        if ($names === []) {
            $this->query->delete('content_terms', ['content' => $contentId]);
            return;
        }

        try {
            $this->query->transaction(function () use ($contentId, $names): void {
                $termIds = [];

                foreach ($names as $name) {
                    $id = (int)($this->query
                        ->from('terms')
                        ->where('name', $name)
                        ->value('id') ?: 0);
                    if ($id <= 0) {
                        $this->query->insertIgnore('terms', ['name' => $name]);
                        $id = (int)($this->query
                            ->from('terms')
                            ->where('name', $name)
                            ->value('id') ?: 0);
                    }
                    if ($id > 0) {
                        $termIds[] = $id;
                    }
                }

                $termIds = array_values(array_unique($termIds));
                if ($termIds === []) {
                    $this->query->delete('content_terms', ['content' => $contentId]);
                    return;
                }

                $this->query->delete('content_terms', ['content' => $contentId]);

                foreach ($termIds as $termId) {
                    $this->query->insertIgnore('content_terms', ['content' => $contentId, 'term' => $termId]);
                }
            });
        } catch (\Throwable) {
        }
    }

    public function contentUsages(int $termId): array
    {
        if ($termId <= 0) {
            return [];
        }

        return $this->query
            ->from('content', 'c')
            ->select(['c.id', 'c.name', 'c.created'])
            ->innerJoin('content_terms', 'ct', 'ct.content', '=', 'c.id')
            ->where('ct.term', $termId)
            ->orderByRaw('COALESCE(c.updated, c.created) DESC')
            ->get();
    }

    private function normalizeTerms(string $rawTerms): array
    {
        $parts = preg_split('/[\n,]+/', $rawTerms) ?: [];
        $terms = [];

        foreach ($parts as $part) {
            $value = trim((string)$part);
            if ($value === '') {
                continue;
            }
            $value = $this->schemaRules->truncate(
                'terms',
                'name',
                $value,
                255
            );
            $key = mb_strtolower($value);
            $terms[$key] = $value;
        }

        return array_values($terms);
    }

    private function existsByName(string $name, ?int $excludeId = null): bool
    {
        $foundId = (int)($this->query
            ->from('terms')
            ->where('name', $name)
            ->value('id') ?: 0);

        if ($foundId <= 0) {
            return false;
        }

        return $excludeId === null || $foundId !== $excludeId;
    }

    private function paginateUnassigned(int $page, int $perPage, string $search): array
    {
        $page = max(1, $page);
        $perPage = max(1, $perPage);

        return $this->query
            ->from('terms', 't')
            ->select(['t.id', 't.name', 't.created', 't.updated'])
            ->whereNotExists('content_terms', 'ct', 'ct.term', '=', 't.id')
            ->search(['t.name'], $search)
            ->orderBy('t.id', 'DESC')
            ->paginate($page, $perPage);
    }
}
