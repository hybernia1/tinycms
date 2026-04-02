<?php
declare(strict_types=1);

namespace App\Service;

final class ContentTypeService
{
    private array $types = [];

    public function __construct()
    {
        $this->register('post', 'Příspěvky', 'Příspěvek');
        $this->register('page', 'Stránky', 'Stránka');
    }

    public function register(string $type, string $pluralLabel, string $singularLabel): void
    {
        $key = $this->sanitizeType($type);

        if ($key === '') {
            return;
        }

        $this->types[$key] = [
            'type' => $key,
            'label_plural' => trim($pluralLabel) !== '' ? trim($pluralLabel) : ucfirst($key),
            'label_singular' => trim($singularLabel) !== '' ? trim($singularLabel) : ucfirst($key),
        ];
    }

    public function all(): array
    {
        return array_values($this->types);
    }

    public function find(string $type): ?array
    {
        $key = $this->sanitizeType($type);
        return $this->types[$key] ?? null;
    }

    public function resolve(string $type = ''): array
    {
        $resolved = $this->find($type);

        if ($resolved !== null) {
            return $resolved;
        }

        $first = reset($this->types);
        return is_array($first) ? $first : [
            'type' => 'post',
            'label_plural' => 'Příspěvky',
            'label_singular' => 'Příspěvek',
        ];
    }

    private function sanitizeType(string $type): string
    {
        return trim(mb_strtolower($type));
    }
}
