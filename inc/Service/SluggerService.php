<?php
declare(strict_types=1);

namespace App\Service;

final class SluggerService
{
    public function slug(string $name, int $id): string
    {
        $base = $this->normalize($name);

        if ($base === '') {
            return (string)$id;
        }

        return $base . '-' . $id;
    }

    private function normalize(string $value): string
    {
        $clean = trim(mb_strtolower($value));

        if ($clean === '') {
            return '';
        }

        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $clean);
        $ascii = $ascii === false ? $clean : $ascii;
        $ascii = preg_replace('/[^a-z0-9]+/i', '-', $ascii) ?? '';

        return trim($ascii, '-');
    }
}
