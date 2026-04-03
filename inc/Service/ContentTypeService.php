<?php
declare(strict_types=1);

namespace App\Service;

final class ContentTypeService
{
    private array $types = [];
    private array $slugMap = [];

    public function __construct()
    {
        $this->register('post', 'Příspěvky', 'Příspěvek');
    }

    public function register(string $type, string $pluralLabel, string $singularLabel, string $slug = ''): void
    {
        $key = $this->sanitizeType($type);
        $slugKey = $this->sanitizeSlug($slug === '' ? $key : $slug);

        if ($key === '' || $slugKey === '') {
            return;
        }

        $this->slugMap[$slugKey] = $key;
        $this->types[$key] = [
            'type' => $key,
            'slug' => $slugKey,
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
            'slug' => 'post',
            'label_plural' => 'Příspěvky',
            'label_singular' => 'Příspěvek',
        ];
    }

    public function resolveBySlug(string $slug): ?array
    {
        $slugKey = $this->sanitizeSlug($slug);
        $type = $this->slugMap[$slugKey] ?? null;

        if ($type === null) {
            return $this->resolveForType($slugKey);
        }

        return $this->resolveForType($type);
    }

    public function resolveForType(string $type): ?array
    {
        $key = $this->sanitizeType($type);

        if ($key === '') {
            return null;
        }

        return $this->types[$key] ?? [
            'type' => $key,
            'slug' => $this->sanitizeSlug($key),
            'label_plural' => ucfirst($key),
            'label_singular' => ucfirst($key),
        ];
    }

    private function sanitizeType(string $type): string
    {
        return trim(mb_strtolower($type));
    }

    private function sanitizeSlug(string $slug): string
    {
        $clean = trim(mb_strtolower($slug));
        $clean = preg_replace('/[^a-z0-9-]+/i', '-', $clean) ?? '';

        return trim($clean, '-');
    }
}
