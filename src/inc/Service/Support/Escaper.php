<?php
declare(strict_types=1);

namespace App\Service\Support;

final class Escaper
{
    public function html(mixed $value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }

    public function attr(mixed $value): string
    {
        return $this->html($value);
    }

    public function url(string $value): string
    {
        $clean = trim($value);
        if ($clean === '') {
            return '';
        }

        $scheme = strtolower((string)parse_url($clean, PHP_URL_SCHEME));
        if ($scheme !== '' && !in_array($scheme, ['http', 'https', 'mailto', 'tel'], true)) {
            return '';
        }

        return $this->html($clean);
    }

    public function js(mixed $value): string
    {
        $encoded = json_encode((string)$value, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
        if (!is_string($encoded) || strlen($encoded) < 2) {
            return '';
        }

        return substr($encoded, 1, -1);
    }

    public function xml(mixed $value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }
}
