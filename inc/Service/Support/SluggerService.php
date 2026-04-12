<?php
declare(strict_types=1);

namespace App\Service\Support;

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

    public function extractId(string $slug): int
    {
        $clean = trim($slug);

        if ($clean !== '' && ctype_digit($clean)) {
            return (int)$clean;
        }

        if (preg_match('/-(\d+)$/', $clean, $matches) === 1) {
            return (int)$matches[1];
        }

        return 0;
    }

    private function normalize(string $value): string
    {
        $clean = trim($value);

        if ($clean === '') {
            return '';
        }

        $ascii = $this->toAscii($clean);
        $ascii = mb_strtolower($ascii);
        $ascii = preg_replace('/[^a-z0-9]+/i', '-', $ascii) ?? '';

        return trim($ascii, '-');
    }

    private function toAscii(string $value): string
    {
        if (class_exists(\Transliterator::class)) {
            $transliterator = \Transliterator::create('Any-Latin; Latin-ASCII');
            if ($transliterator instanceof \Transliterator) {
                $converted = $transliterator->transliterate($value);
                if (is_string($converted) && $converted !== '') {
                    return $converted;
                }
            }
        }

        $clean = strtr(mb_strtolower($value), [
            'á' => 'a',
            'ä' => 'a',
            'č' => 'c',
            'ď' => 'd',
            'é' => 'e',
            'ě' => 'e',
            'í' => 'i',
            'ľ' => 'l',
            'ĺ' => 'l',
            'ň' => 'n',
            'ó' => 'o',
            'ô' => 'o',
            'ö' => 'o',
            'ř' => 'r',
            'š' => 's',
            'ť' => 't',
            'ú' => 'u',
            'ů' => 'u',
            'ü' => 'u',
            'ý' => 'y',
            'ž' => 'z',
        ]);

        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $clean);
        return $ascii === false ? $clean : $ascii;
    }
}
