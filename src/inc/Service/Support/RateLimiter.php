<?php
declare(strict_types=1);

namespace App\Service\Support;

final class RateLimiter
{
    private string $directory;

    public function __construct(?string $directory = null)
    {
        $baseDirectory = $directory ?? sys_get_temp_dir() . '/tinycms-rate-limit';
        $this->directory = rtrim($baseDirectory, '/');

        if (!is_dir($this->directory) && !@mkdir($this->directory, 0775, true) && !is_dir($this->directory)) {
            $this->directory = sys_get_temp_dir();
        }
    }

    public function hit(string $key, int $limit, int $windowSeconds): array
    {
        $now = time();
        $path = $this->pathFor($key);
        $hits = $this->load($path);
        $hits = array_values(array_filter($hits, static fn(int $stamp): bool => $stamp > ($now - $windowSeconds)));

        if (count($hits) >= $limit) {
            $oldest = min($hits);
            return [
                'allowed' => false,
                'retry_after' => max(1, $windowSeconds - ($now - $oldest)),
            ];
        }

        $hits[] = $now;
        $this->store($path, $hits);

        return [
            'allowed' => true,
            'retry_after' => 0,
        ];
    }

    private function pathFor(string $key): string
    {
        return $this->directory . '/' . sha1($key) . '.json';
    }

    private function load(string $path): array
    {
        if (!is_file($path)) {
            return [];
        }

        $raw = file_get_contents($path);
        if ($raw === false || $raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return [];
        }

        return array_values(array_filter(
            array_map(static fn(mixed $value): int => (int)$value, $decoded),
            static fn(int $stamp): bool => $stamp > 0
        ));
    }

    private function store(string $path, array $hits): void
    {
        $encoded = json_encode(array_values($hits), JSON_UNESCAPED_UNICODE);
        if ($encoded === false) {
            return;
        }

        @file_put_contents($path, $encoded, LOCK_EX);
    }
}
