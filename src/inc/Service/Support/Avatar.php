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
        $ears = $this->ears($hash, $fur, $muzzle, $dark);
        $head = $this->head($hash, $fur, $dark);
        $breed = $this->breedTraits($hash, $dark, $accent, $muzzle);
        $markings = $this->markings($hash, $dark, $accent);
        $eyes = $this->eyes($hash, $dark, $accent);
        $nose = $this->nose($hash, $accent, $dark);
        $whiskers = $this->whiskers($hash, $dark);
        $title = Escape::xml($seed);

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="$size" height="$size" viewBox="0 0 128 128" role="img" aria-label="$title">
<title>$title</title>
<rect width="128" height="128" rx="28" fill="$background"/>
<g>
<ellipse cx="64" cy="90" rx="44" ry="24" fill="$accent" opacity=".12"/>
$ears
$head
$breed
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
            0 => '<ellipse cx="48" cy="64" rx="8" ry="10" fill="#f2cf66"/><ellipse cx="80" cy="64" rx="8" ry="10" fill="#f2cf66"/><ellipse cx="48" cy="64" rx="2" ry="8" fill="' . $dark . '"/><ellipse cx="80" cy="64" rx="2" ry="8" fill="' . $dark . '"/><circle cx="46" cy="61" r="1.8" fill="#fff"/><circle cx="78" cy="61" r="1.8" fill="#fff"/>',
            1 => '<ellipse cx="48" cy="64" rx="8" ry="8" fill="#82cfff"/><ellipse cx="80" cy="64" rx="8" ry="8" fill="#82cfff"/><circle cx="48" cy="64" r="4" fill="' . $dark . '"/><circle cx="80" cy="64" r="4" fill="' . $dark . '"/><circle cx="46" cy="62" r="1.5" fill="#fff"/><circle cx="78" cy="62" r="1.5" fill="#fff"/>',
            2 => '<path d="M40 64c5-7 11-7 16 0M72 64c5-7 11-7 16 0" fill="none" stroke="' . $dark . '" stroke-width="4" stroke-linecap="round"/><path d="M42 62c3-3 9-3 12 0M74 62c3-3 9-3 12 0" fill="none" stroke="' . $accent . '" stroke-width="2" stroke-linecap="round" opacity=".7"/>',
            3 => '<ellipse cx="48" cy="64" rx="9" ry="6" fill="#9de27f"/><ellipse cx="80" cy="64" rx="9" ry="6" fill="#9de27f"/><path d="M42 64h12M74 64h12" stroke="' . $dark . '" stroke-width="3" stroke-linecap="round"/><circle cx="45" cy="62" r="1.5" fill="#fff"/><circle cx="77" cy="62" r="1.5" fill="#fff"/>',
            default => '<ellipse cx="48" cy="64" rx="8" ry="9" fill="' . $accent . '" opacity=".8"/><ellipse cx="80" cy="64" rx="8" ry="9" fill="' . $accent . '" opacity=".8"/><ellipse cx="48" cy="64" rx="3" ry="6" fill="' . $dark . '"/><ellipse cx="80" cy="64" rx="3" ry="6" fill="' . $dark . '"/><circle cx="46" cy="61" r="1.5" fill="#fff"/><circle cx="78" cy="61" r="1.5" fill="#fff"/>',
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

    private function breedTraits(string $hash, string $dark, string $accent, string $muzzle): string
    {
        return match ($this->byte($hash, 8) % 5) {
            0 => '<ellipse cx="64" cy="56" rx="24" ry="11" fill="' . $dark . '" opacity=".08"/><path d="M45 52c4-4 10-5 14-4M69 48c5-1 10 0 14 4" stroke="' . $dark . '" stroke-width="2.4" stroke-linecap="round" opacity=".35"/>',
            1 => '<path d="M35 69c9-8 49-8 58 0" fill="none" stroke="' . $accent . '" stroke-width="5" stroke-linecap="round" opacity=".22"/><ellipse cx="64" cy="93" rx="12" ry="5" fill="' . $muzzle . '" opacity=".7"/>',
            2 => '<path d="M40 57c5-4 12-5 17-2M71 55c5-3 12-2 17 2" stroke="' . $dark . '" stroke-width="3" stroke-linecap="round" opacity=".4"/><circle cx="64" cy="52" r="3" fill="' . $accent . '" opacity=".35"/>',
            3 => '<ellipse cx="45" cy="72" rx="7" ry="5" fill="' . $muzzle . '" opacity=".55"/><ellipse cx="83" cy="72" rx="7" ry="5" fill="' . $muzzle . '" opacity=".55"/><path d="M52 98c4 2 20 2 24 0" stroke="' . $dark . '" stroke-width="2.2" stroke-linecap="round" opacity=".35"/>',
            default => '<path d="M43 50c6-6 36-6 42 0" fill="none" stroke="' . $accent . '" stroke-width="3" stroke-linecap="round" opacity=".3"/><path d="M48 58c4-3 8-4 12-3M68 55c4-1 8 0 12 3" stroke="' . $dark . '" stroke-width="2" stroke-linecap="round" opacity=".34"/>',
        };
    }

    private function head(string $hash, string $fur, string $dark): string
    {
        return match ($this->byte($hash, 7) % 4) {
            0 => '<circle cx="64" cy="68" r="39" fill="' . $fur . '"/><ellipse cx="64" cy="46" rx="24" ry="10" fill="' . $dark . '" opacity=".12"/>',
            1 => '<path d="M25 71c0-24 17-42 39-42s39 18 39 42c0 20-17 35-39 35S25 91 25 71z" fill="' . $fur . '"/><ellipse cx="64" cy="47" rx="22" ry="9" fill="' . $dark . '" opacity=".1"/>',
            2 => '<path d="M24 66c0-23 18-39 40-39s40 16 40 39c0 24-18 40-40 40S24 90 24 66z" fill="' . $fur . '"/><path d="M36 54c9-7 47-7 56 0" stroke="' . $dark . '" stroke-width="4" stroke-linecap="round" opacity=".18"/>',
            default => '<path d="M26 68c0-22 16-40 38-40s38 18 38 40-16 38-38 38-38-16-38-38z" fill="' . $fur . '"/><ellipse cx="64" cy="44" rx="20" ry="8" fill="' . $dark . '" opacity=".14"/>',
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
