<?php
declare(strict_types=1);

namespace App\Service\Support;

use App\Service\Auth\Auth;
use App\Service\Application\Content;
use App\Service\Infrastructure\Router\Router;

final class Shortcode
{
    private static ?Router $router = null;
    private static ?Auth $auth = null;
    private static ?Content $content = null;
    private static array $settings = [];
    private static array $contentCache = [];

    public static function configure(Router $router, Auth $auth, array $settings): void
    {
        self::$router = $router;
        self::$auth = $auth;
        self::$content = null;
        self::$settings = $settings;
        self::$contentCache = [];
    }

    public static function render(string $value, array &$trustedBlocks = []): string
    {
        if (!str_contains($value, '[')) {
            return $value;
        }

        $codeBlocks = [];
        $value = self::extractCodeBlocks($value, $codeBlocks);
        $value = self::extractSearchForms($value, $trustedBlocks);
        $value = self::renderUserBlocks($value, $trustedBlocks);
        $value = preg_replace_callback('/\[(year|date|login|logout)\]|\[content=(\d+)\]/i', static function (array $match): string {
            $name = strtolower((string)($match[1] ?? ''));
            if ($name === 'year') {
                return date('Y');
            }
            if ($name === 'date') {
                return self::currentDateTime();
            }
            if ($name === 'login') {
                return self::link('auth/login', I18n::t('auth.login'));
            }
            if ($name === 'logout') {
                return self::link('admin/logout', I18n::t('admin.logout'));
            }

            return self::contentLink((int)($match[2] ?? 0));
        }, $value) ?? $value;

        return strtr($value, $codeBlocks);
    }

    private static function extractCodeBlocks(string $value, array &$blocks): string
    {
        $value = preg_replace_callback('/<p[^>]*>\s*\[code(?:=([a-z0-9_+.#-]{1,30}))?\]([\s\S]*?)\[\/code\]\s*<\/p>/i', static function (array $match) use (&$blocks): string {
            return self::storeCodeBlock($blocks, (string)($match[2] ?? ''), (string)($match[1] ?? ''));
        }, $value) ?? $value;

        return preg_replace_callback('/\[code(?:=([a-z0-9_+.#-]{1,30}))?\]([\s\S]*?)\[\/code\]/i', static function (array $match) use (&$blocks): string {
            return self::storeCodeBlock($blocks, (string)($match[2] ?? ''), (string)($match[1] ?? ''));
        }, $value) ?? $value;
    }

    private static function storeCodeBlock(array &$blocks, string $code, string $language): string
    {
        $placeholder = '%%TINYCMS_CODE_BLOCK_' . count($blocks) . '%%';
        $blocks[$placeholder] = self::codeBlock($code, $language);
        return $placeholder;
    }

    private static function extractSearchForms(string $value, array &$blocks): string
    {
        return preg_replace_callback('/<p>\s*\[search\]\s*<\/p>|\[search\]/i', static function () use (&$blocks): string {
            $placeholder = self::placeholder('TRUSTED_BLOCK');
            $blocks[$placeholder] = \function_exists('get_search_form') ? \get_search_form('search', null, false) : '';
            return $placeholder;
        }, $value) ?? $value;
    }

    private static function codeBlock(string $code, string $language): string
    {
        $code = self::codeText($code);
        $language = self::codeLanguage($language);
        $attrs = ' class="code-block"';
        if ($language !== '') {
            $attrs .= ' data-language="' . Escape::attr($language) . '"';
        }

        return '<pre' . $attrs . '><code>' . Escape::html($code) . '</code></pre>';
    }

    private static function codeText(string $code): string
    {
        $code = preg_replace('/<br\s*\/?>/i', "\n", $code) ?? $code;
        $code = preg_replace('/<\/p>\s*<p[^>]*>/i', "\n", $code) ?? $code;
        $code = preg_replace('/<\/div>\s*<div[^>]*>/i', "\n", $code) ?? $code;
        $code = preg_replace('/<\/?(p|div)[^>]*>/i', '', $code) ?? $code;

        return html_entity_decode(trim($code), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    private static function codeLanguage(string $language): string
    {
        $clean = strtolower(trim($language));
        return preg_match('/^[a-z0-9][a-z0-9_+.#-]{0,29}$/', $clean) === 1 ? $clean : '';
    }

    private static function placeholder(string $prefix): string
    {
        try {
            $token = bin2hex(random_bytes(8));
        } catch (\Throwable) {
            $token = str_replace('.', '', uniqid('', true));
        }

        return '%%TINYCMS_' . $prefix . '_' . $token . '%%';
    }

    private static function renderUserBlocks(string $value, array &$trustedBlocks): string
    {
        return preg_replace_callback('/\[user\](.*?)\[\/user\]/is', static function (array $match) use (&$trustedBlocks): string {
            return self::isUser() ? self::render((string)($match[1] ?? ''), $trustedBlocks) : '';
        }, $value) ?? $value;
    }

    private static function currentDateTime(): string
    {
        $default = defined('APP_DATETIME_FORMAT') ? APP_DATETIME_FORMAT : 'd.m.Y H:i';
        $format = Date::normalizeDateTimeFormat((string)(self::$settings['app_datetime_format'] ?? $default));
        return date($format);
    }

    private static function contentLink(int $id): string
    {
        $item = self::content($id);
        if ($item === null) {
            return '';
        }

        $name = trim((string)($item['name'] ?? ''));
        if ($name === '') {
            $name = '#' . $id;
        }

        $slug = (new Slugger())->slug($name, $id);
        return self::link($slug, $name);
    }

    private static function content(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        if (array_key_exists($id, self::$contentCache)) {
            return self::$contentCache[$id];
        }

        if (self::$router === null) {
            return self::$contentCache[$id] = null;
        }

        $content = self::$content ??= new Content();
        $item = $content->findPublishedSummary($id);

        return self::$contentCache[$id] = is_array($item) ? $item : null;
    }

    private static function link(string $path, string $label): string
    {
        $label = trim($label);
        if ($label === '') {
            return '';
        }

        $url = self::$router?->url($path) ?? RequestContext::path($path);
        return '<a href="' . Escape::url($url) . '">' . Escape::html($label) . '</a>';
    }

    private static function isUser(): bool
    {
        return self::$auth?->check() ?? false;
    }
}
