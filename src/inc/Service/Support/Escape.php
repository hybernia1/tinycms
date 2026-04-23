<?php
declare(strict_types=1);

namespace App\Service\Support;

final class Escape
{
    public static function html(mixed $value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public static function attr(mixed $value): string
    {
        return self::html($value);
    }

    public static function url(mixed $value): string
    {
        $url = trim((string)$value);
        if ($url === '') {
            return '';
        }

        $scheme = strtolower((string)(parse_url($url, PHP_URL_SCHEME) ?? ''));
        if ($scheme !== '' && !in_array($scheme, ['http', 'https', 'mailto', 'tel'], true)) {
            return '';
        }

        if (str_starts_with($url, '//')) {
            return '';
        }

        return self::attr($url);
    }

    public static function js(mixed $value): string
    {
        return trim(self::json((string)$value), '"');
    }

    public static function json(mixed $value): string
    {
        return (string)json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    }

    public static function content(mixed $value): string
    {
        $html = trim((string)$value);
        if ($html === '') {
            return '';
        }
        if (!class_exists(\DOMDocument::class)) {
            return self::html(strip_tags($html));
        }

        $previous = libxml_use_internal_errors(true);
        $document = new \DOMDocument('1.0', 'UTF-8');
        $document->loadHTML(
            '<?xml encoding="UTF-8"><div id="tinycms-content-root">' . str_replace("\0", '', $html) . '</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $document->getElementById('tinycms-content-root');
        if (!$root instanceof \DOMElement) {
            return '';
        }

        self::cleanChildren($root);

        $output = '';
        foreach ($root->childNodes as $child) {
            $output .= $document->saveHTML($child);
        }

        return trim($output);
    }

    public static function xml(mixed $value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_XML1 | ENT_SUBSTITUTE, 'UTF-8');
    }

    private static function cleanChildren(\DOMNode $node): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child instanceof \DOMElement) {
                self::cleanElement($child);
                continue;
            }

            if (!$child instanceof \DOMText || trim($child->textContent) === '') {
                $node->removeChild($child);
            }
        }
    }

    private static function cleanElement(\DOMElement $node): void
    {
        self::cleanChildren($node);

        $tag = strtolower($node->tagName);
        if ($tag === 'b' || $tag === 'i') {
            $node = self::rename($node, $tag === 'b' ? 'strong' : 'em');
            $tag = strtolower($node->tagName);
        }

        if (in_array($tag, ['script', 'style', 'object', 'embed', 'form', 'input', 'button', 'textarea', 'select', 'option'], true)) {
            $node->parentNode?->removeChild($node);
            return;
        }

        if (!in_array($tag, ['p', 'br', 'strong', 'em', 'a', 'ul', 'ol', 'li', 'blockquote', 'hr', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'div', 'img', 'iframe', 'table', 'thead', 'tbody', 'tfoot', 'tr', 'th', 'td', 'caption', 'colgroup', 'col'], true)) {
            self::unwrap($node);
            return;
        }

        $attributes = self::attributes($node);
        foreach (array_keys($attributes) as $name) {
            $node->removeAttribute($name);
        }

        match ($tag) {
            'a' => self::cleanLink($node, $attributes),
            'img' => self::cleanImage($node, $attributes),
            'iframe' => self::cleanIframe($node, $attributes),
            'th', 'td' => self::cleanTableCell($node, $attributes),
            'div' => self::cleanDiv($node, $attributes),
            default => null,
        };
    }

    private static function attributes(\DOMElement $node): array
    {
        $attributes = [];
        foreach ($node->attributes as $attribute) {
            $attributes[strtolower($attribute->name)] = $attribute->value;
        }

        return $attributes;
    }

    private static function cleanLink(\DOMElement $node, array $attributes): void
    {
        $href = self::safeHref((string)($attributes['href'] ?? ''));
        if ($href === '') {
            self::unwrap($node);
            return;
        }

        $node->setAttribute('href', $href);
        if (($attributes['target'] ?? '') === '_blank') {
            $node->setAttribute('target', '_blank');
            $rel = ['noopener', 'noreferrer'];
            if (preg_match('/\bnofollow\b/i', (string)($attributes['rel'] ?? '')) === 1) {
                $rel[] = 'nofollow';
            }
            $node->setAttribute('rel', implode(' ', $rel));
        } elseif (preg_match('/\bnofollow\b/i', (string)($attributes['rel'] ?? '')) === 1) {
            $node->setAttribute('rel', 'nofollow');
        }
    }

    private static function cleanImage(\DOMElement $node, array $attributes): void
    {
        $src = self::safeUrl((string)($attributes['src'] ?? ''), true);
        if ($src === '') {
            $node->parentNode?->removeChild($node);
            return;
        }

        $node->setAttribute('src', $src);
        $node->setAttribute('alt', trim((string)($attributes['alt'] ?? '')));
        if (preg_match('/^\d+$/', (string)($attributes['data-media-id'] ?? '')) === 1) {
            $node->setAttribute('data-media-id', (string)$attributes['data-media-id']);
        }

        $style = self::safeWidthStyle((string)($attributes['style'] ?? ''));
        if ($style !== '') {
            $node->setAttribute('style', $style);
        }
    }

    private static function cleanIframe(\DOMElement $node, array $attributes): void
    {
        $src = self::safeYoutubeEmbedUrl((string)($attributes['src'] ?? ''));
        if ($src === '') {
            $node->parentNode?->removeChild($node);
            return;
        }

        $node->setAttribute('src', $src);
        $node->setAttribute('loading', 'lazy');
        $node->setAttribute('allowfullscreen', '');
        $node->setAttribute('referrerpolicy', 'strict-origin-when-cross-origin');
        $node->setAttribute('allow', 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share');
    }

    private static function cleanDiv(\DOMElement $node, array $attributes): void
    {
        $class = ' ' . (string)($attributes['class'] ?? '') . ' ';
        if (str_contains($class, ' block-list ')) {
            self::unwrap($node);
            return;
        }

        if (str_contains($class, ' block-image ')) {
            $align = str_contains($class, ' align-left ') ? 'left' : (str_contains($class, ' align-right ') ? 'right' : 'center');
            $node->setAttribute('class', 'block block-image align-' . $align);
            $style = self::safeWidthStyle((string)($attributes['style'] ?? ''));
            if ($style !== '') {
                $node->setAttribute('style', $style);
            }
            return;
        }

        if (str_contains($class, ' block-embed-youtube ')) {
            $align = str_contains($class, ' align-left ') ? 'left' : (str_contains($class, ' align-right ') ? 'right' : 'center');
            $node->setAttribute('class', 'block block-embed block-embed-youtube align-' . $align);
            $style = self::safeWidthStyle((string)($attributes['style'] ?? ''));
            if ($style !== '') {
                $node->setAttribute('style', $style);
            }
            return;
        }

        if (str_contains($class, ' embed-frame ')) {
            $node->setAttribute('class', 'embed-frame');
            return;
        }

        self::unwrap($node);
    }

    private static function cleanTableCell(\DOMElement $node, array $attributes): void
    {
        $colspan = trim((string)($attributes['colspan'] ?? ''));
        if (preg_match('/^\d+$/', $colspan) === 1 && (int)$colspan > 1) {
            $node->setAttribute('colspan', $colspan);
        }

        $rowspan = trim((string)($attributes['rowspan'] ?? ''));
        if (preg_match('/^\d+$/', $rowspan) === 1 && (int)$rowspan > 1) {
            $node->setAttribute('rowspan', $rowspan);
        }
    }

    private static function safeHref(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }

        return self::safeUrl($url, true);
    }

    private static function safeUrl(string $url, bool $allowRelative): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }

        $scheme = strtolower((string)(parse_url($url, PHP_URL_SCHEME) ?? ''));
        if ($scheme !== '' && !in_array($scheme, ['http', 'https', 'mailto', 'tel'], true)) {
            return '';
        }

        if (preg_match('~^(https?://|mailto:|tel:|#)~i', $url) === 1) {
            return $url;
        }

        return $allowRelative && preg_match('#^/(?!/)#', $url) === 1 ? $url : '';
    }

    private static function safeYoutubeEmbedUrl(string $url): string
    {
        $parts = parse_url(trim($url));
        $host = strtolower((string)($parts['host'] ?? ''));
        $host = preg_replace('/^(www|m)\./', '', $host) ?? $host;
        $path = (string)($parts['path'] ?? '');

        if (!in_array($host, ['youtube.com', 'youtube-nocookie.com'], true) || preg_match('#^/embed/([a-zA-Z0-9_-]{11})$#', $path, $match) !== 1) {
            return '';
        }

        return 'https://www.youtube.com/embed/' . $match[1];
    }

    private static function safeWidthStyle(string $style): string
    {
        if (preg_match('/(?:^|;)\s*width\s*:\s*(\d+(?:\.\d+)?)%\s*(?:;|$)/i', $style, $match) !== 1) {
            return '';
        }

        $width = max(10, min(100, (float)$match[1]));
        $value = rtrim(rtrim(number_format($width, 2, '.', ''), '0'), '.');
        return 'width: ' . $value . '%;';
    }

    private static function rename(\DOMElement $node, string $tag): \DOMElement
    {
        $replacement = $node->ownerDocument->createElement($tag);
        while ($node->firstChild) {
            $replacement->appendChild($node->firstChild);
        }
        $node->parentNode?->replaceChild($replacement, $node);

        return $replacement;
    }

    private static function unwrap(\DOMElement $node): void
    {
        $parent = $node->parentNode;
        if (!$parent instanceof \DOMNode) {
            return;
        }

        while ($node->firstChild) {
            $parent->insertBefore($node->firstChild, $node);
        }
        $parent->removeChild($node);
    }
}
