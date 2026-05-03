<?php
declare(strict_types=1);

namespace App\Service\Support;

final class Avatar
{
    private const PALETTE = [
        ['#f6c177', '#51352a', '#fff0d6', '#9ccfd8', '#eb6f92'],
        ['#d9b38c', '#4a3429', '#fff4e8', '#c4a7e7', '#286983'],
        ['#a7b0b5', '#293241', '#f7f7f2', '#f6c177', '#ea9a97'],
        ['#f2cdcd', '#40313a', '#fff8f2', '#89b4fa', '#a6e3a1'],
        ['#e4c59e', '#3f2a1f', '#f8e8ca', '#b8c0ff', '#ffafcc'],
        ['#c9a27e', '#2f241e', '#fff1df', '#e9c46a', '#2a9d8f'],
    ];

    public static function url(string $seed, int $size = 64): string
    {
        $cleanSeed = trim($seed) !== '' ? trim($seed) : 'user';
        $size = max(24, min(256, $size));

        return RequestContext::path('avatar/' . rawurlencode($cleanSeed) . '.svg?size=' . $size);
    }

    public static function dataUrl(string $seed, int $size = 64): string
    {
        return 'data:image/svg+xml;base64,' . base64_encode((new self())->svg($seed, $size));
    }

    public static function userSeed(array $user): string
    {
        foreach (['name', 'email'] as $field) {
            $value = trim((string)($user[$field] ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        $id = (int)($user['id'] ?? ($user['ID'] ?? 0));
        return $id > 0 ? 'user-' . $id : 'user';
    }

    public function svg(string $seed, int $size = 128): string
    {
        $seed = trim($seed) !== '' ? trim($seed) : 'user';
        $size = max(24, min(512, $size));
        $hash = hash('sha256', mb_strtolower($seed));
        $colors = self::PALETTE[$this->byte($hash, 0) % count(self::PALETTE)];
        $fur = $this->tint($colors[0], 10 - ($this->byte($hash, 1) % 21));
        $dark = $colors[1];
        $muzzle = $colors[2];
        $background = $colors[3];
        $accent = $colors[4];
        $detail = $this->detail($hash, $accent, $background);
        $ears = $this->ears($hash, $fur, $muzzle, $dark);
        $markings = $this->markings($hash, $dark, $accent);
        $eyes = $this->eyes($hash, $dark, $accent);
        $nose = $this->nose($hash, $accent, $dark);
        $whiskers = $this->whiskers($hash, $dark);
        $title = Escape::xml($seed);

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="$size" height="$size" viewBox="0 0 128 128" role="img" aria-label="$title">
<title>$title</title>
<rect width="128" height="128" rx="28" fill="$background"/>
$detail
<g>
$ears
<circle cx="64" cy="68" r="39" fill="$fur"/>
$markings
<ellipse cx="50" cy="78" rx="14" ry="12" fill="$muzzle"/>
<ellipse cx="78" cy="78" rx="14" ry="12" fill="$muzzle"/>
<ellipse cx="64" cy="85" rx="18" ry="13" fill="$muzzle"/>
$eyes
$nose
$whiskers
</g>
</svg>
SVG;
    }

    private function ears(string $hash, string $fur, string $inner, string $dark): string
    {
        return match ($this->byte($hash, 2) % 4) {
            0 => '<path d="M32 45 22 13l29 18zM96 45l10-32-29 18z" fill="' . $fur . '"/><path d="M34 36 28 22l13 9zM94 36l6-14-13 9z" fill="' . $inner . '" opacity=".82"/>',
            1 => '<path d="M34 47 19 20l34 9zM94 47l15-27-34 9z" fill="' . $fur . '"/><path d="M35 38 28 27l14 4zM93 38l7-11-14 4z" fill="' . $inner . '" opacity=".82"/>',
            2 => '<path d="M33 46 26 14l27 19zM95 46l7-32-27 19z" fill="' . $fur . '"/><path d="M35 36 32 23l11 9zM93 36l3-13-11 9z" fill="' . $dark . '" opacity=".2"/>',
            default => '<path d="M31 49 18 16l34 18zM97 49l13-33-34 18z" fill="' . $fur . '"/><path d="M35 38 28 25l14 9zM93 38l7-13-14 9z" fill="' . $inner . '" opacity=".82"/>',
        };
    }

    private function markings(string $hash, string $dark, string $accent): string
    {
        return match ($this->byte($hash, 3) % 6) {
            0 => '<path d="M48 31c2 11 4 18 9 28M64 28v29M80 31c-2 11-4 18-9 28" stroke="' . $dark . '" stroke-width="5" stroke-linecap="round" opacity=".35"/>',
            1 => '<path d="M28 62c12-2 20-7 27-16M100 62c-12-2-20-7-27-16" stroke="' . $dark . '" stroke-width="6" stroke-linecap="round" opacity=".28"/>',
            2 => '<path d="M43 35c9-7 28-8 41 0-10 4-17 8-21 18-4-10-10-14-20-18z" fill="' . $accent . '" opacity=".38"/>',
            3 => '<circle cx="42" cy="50" r="10" fill="' . $dark . '" opacity=".22"/><circle cx="86" cy="50" r="10" fill="' . $dark . '" opacity=".22"/>',
            4 => '<path d="M57 30h14l-7 25z" fill="' . $dark . '" opacity=".28"/><path d="M33 64c7-6 13-9 22-10M95 64c-7-6-13-9-22-10" stroke="' . $dark . '" stroke-width="4" stroke-linecap="round" opacity=".25"/>',
            default => '<path d="M38 38c9 4 16 10 20 19M90 38c-9 4-16 10-20 19" stroke="' . $accent . '" stroke-width="6" stroke-linecap="round" opacity=".36"/>',
        };
    }

    private function eyes(string $hash, string $dark, string $accent): string
    {
        return match ($this->byte($hash, 4) % 5) {
            0 => '<ellipse cx="48" cy="64" rx="7" ry="9" fill="' . $accent . '"/><ellipse cx="80" cy="64" rx="7" ry="9" fill="' . $accent . '"/><path d="M48 58v12M80 58v12" stroke="' . $dark . '" stroke-width="3" stroke-linecap="round"/>',
            1 => '<circle cx="48" cy="64" r="7" fill="' . $dark . '"/><circle cx="80" cy="64" r="7" fill="' . $dark . '"/><circle cx="45" cy="61" r="2" fill="#fff"/><circle cx="77" cy="61" r="2" fill="#fff"/>',
            2 => '<path d="M41 62c5-6 11-6 16 0M71 62c5-6 11-6 16 0" fill="none" stroke="' . $dark . '" stroke-width="4" stroke-linecap="round"/>',
            3 => '<ellipse cx="48" cy="64" rx="8" ry="5" fill="' . $dark . '"/><ellipse cx="80" cy="64" rx="8" ry="5" fill="' . $dark . '"/>',
            default => '<ellipse cx="48" cy="64" rx="6" ry="8" fill="' . $dark . '"/><ellipse cx="80" cy="64" rx="6" ry="8" fill="' . $dark . '"/><path d="M46 61h4M78 61h4" stroke="#fff" stroke-width="2" stroke-linecap="round"/>',
        };
    }

    private function nose(string $hash, string $accent, string $dark): string
    {
        $mouth = match ($this->byte($hash, 5) % 4) {
            0 => '<path d="M64 82c-4 6-9 8-15 7M64 82c4 6 9 8 15 7" fill="none" stroke="' . $dark . '" stroke-width="3" stroke-linecap="round"/>',
            1 => '<path d="M64 82v8" stroke="' . $dark . '" stroke-width="3" stroke-linecap="round"/><path d="M54 91c5 5 15 5 20 0" fill="none" stroke="' . $dark . '" stroke-width="3" stroke-linecap="round"/>',
            2 => '<path d="M56 90c5 7 11 7 16 0" fill="none" stroke="' . $dark . '" stroke-width="3" stroke-linecap="round"/>',
            default => '<path d="M64 82v7" stroke="' . $dark . '" stroke-width="3" stroke-linecap="round"/><circle cx="56" cy="91" r="3" fill="' . $dark . '" opacity=".85"/><circle cx="72" cy="91" r="3" fill="' . $dark . '" opacity=".85"/>',
        };

        return '<path d="M57 77c3-4 11-4 14 0l-7 7z" fill="' . $accent . '"/>' . $mouth;
    }

    private function whiskers(string $hash, string $dark): string
    {
        $wide = $this->byte($hash, 6) % 2 === 0;
        $leftEnd = $wide ? 21 : 27;
        $rightEnd = $wide ? 107 : 101;

        return '<path d="M56 80H' . $leftEnd . 'M56 86 24 91M72 80h' . (128 - $rightEnd) . 'M72 86l32 5" stroke="' . $dark . '" stroke-width="3" stroke-linecap="round" opacity=".55"/>'
            . '<path d="M55 74 25 67M73 74l30-7" stroke="' . $dark . '" stroke-width="3" stroke-linecap="round" opacity=".45"/>';
    }

    private function detail(string $hash, string $accent, string $background): string
    {
        return match ($this->byte($hash, 7) % 4) {
            0 => '<path d="M18 92h34" stroke="' . $accent . '" stroke-width="8" stroke-linecap="round" opacity=".18"/><path d="M78 28h31" stroke="' . $accent . '" stroke-width="8" stroke-linecap="round" opacity=".22"/>',
            1 => '<path d="M0 92c28-22 61-20 128 0v36H0z" fill="' . $accent . '" opacity=".16"/>',
            2 => '<circle cx="104" cy="27" r="9" fill="' . $background . '" opacity=".9"/><circle cx="21" cy="101" r="8" fill="' . $accent . '" opacity=".22"/>',
            default => '<path d="M16 26l18-10 18 10-18 10zM78 106l18-10 18 10-18 10z" fill="' . $accent . '" opacity=".16"/>',
        };
    }

    private function byte(string $hash, int $index): int
    {
        return hexdec(substr($hash, ($index * 2) % strlen($hash), 2));
    }

    private function tint(string $hex, int $amount): string
    {
        $hex = ltrim($hex, '#');
        $rgb = [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ];
        $rgb = array_map(static fn(int $value): int => max(0, min(255, $value + $amount)), $rgb);

        return sprintf('#%02x%02x%02x', $rgb[0], $rgb[1], $rgb[2]);
    }
}
