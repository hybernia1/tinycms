<?php
declare(strict_types=1);

use App\Service\Front\Theme;
use App\Service\Support\Escape;
use App\Service\Support\I18n;
use App\Service\Support\RequestContext;

function t(string $key, ?string $fallback = null): string
{
    return I18n::t($key, $fallback);
}

function icon(string $name, string $classes = 'icon'): string
{
    $icon = trim($name);
    if (preg_match('/^[a-z0-9_-]+$/i', $icon) !== 1) {
        return '';
    }

    $class = trim($classes);
    $href = esc_url(icon_sprite() . '#icon-' . $icon);
    return '<svg class="' . esc_attr($class !== '' ? $class : 'icon') . '" aria-hidden="true" focusable="false"><use href="' . $href . '"></use></svg>';
}

function icon_sprite(): string
{
    return RequestContext::path(ASSETS_DIR . 'svg/icons.svg');
}

function site_title(): string
{
    return Theme::current()?->siteTitle() ?? 'TinyCMS';
}

function site_url(string $path = ''): string
{
    return Theme::current()?->url($path) ?? RequestContext::path($path);
}

function site_language(): string
{
    return Theme::current()?->language() ?? I18n::htmlLang();
}

function site_logo_url(): string
{
    $logo = trim((string)(Theme::current()?->siteLogo() ?? ''));
    return $logo !== '' ? site_url($logo) : '';
}

function site_logo(string $class = 'site-logo'): string
{
    $url = site_logo_url();
    if ($url === '') {
        return '';
    }

    $class = trim($class);
    return '<img src="' . esc_url($url) . '" alt="' . esc_attr(site_title()) . '"' . ($class !== '' ? ' class="' . esc_attr($class) . '"' : '') . '>';
}

function theme_url(string $path = ''): string
{
    return Theme::current()?->themeUrl($path) ?? site_url($path);
}

function get_head(): string
{
    return Theme::current()?->getHead() ?? '';
}

function get_menu(array $options = []): string
{
    return Theme::current()?->menu($options) ?? '';
}

function get_search_form(string $action = 'search', ?string $query = null): string
{
    $query ??= (string)($_GET['q'] ?? '');
    return Theme::current()?->searchForm($action, $query, [
        'placeholder' => t('front.search_placeholder'),
        'button' => t('front.search_button'),
    ]) ?? '';
}

function get_title(array $item): string
{
    return Theme::current()?->contentTitle($item) ?? trim((string)($item['name'] ?? ''));
}

function get_excerpt(array $item, int $limit = 0): string
{
    return Theme::current()?->contentExcerpt($item, $limit) ?? trim(strip_tags((string)($item['excerpt'] ?? '')));
}

function get_content(array $item): string
{
    return Theme::current()?->contentBody($item) ?? esc_content($item['body'] ?? '');
}

function get_permalink(array $item): string
{
    return Theme::current()?->contentUrl($item) ?? '';
}

function get_thumbnail(array $item, array $options = []): string
{
    return Theme::current()?->contentThumbnail($item, $options) ?? '';
}

function get_thumbnail_url(array $item, string $size = 'webp'): string
{
    return Theme::current()?->contentThumbnailUrl($item, $size) ?? '';
}

function get_author(array $item, string $fallback = ''): string
{
    return Theme::current()?->contentAuthor($item, $fallback) ?? trim($fallback);
}

function get_author_url(array $item): string
{
    return Theme::current()?->authorUrl($item) ?? '';
}

function get_date(array $item, string $fallback = ''): string
{
    return Theme::current()?->contentDate($item, $fallback) ?? trim($fallback);
}

function get_terms(array $item): array
{
    return Theme::current()?->contentTerms($item) ?? [];
}

function get_term_url(array $term): string
{
    return Theme::current()?->termUrl($term) ?? '';
}

function get_content_meta(array $item): string
{
    return Theme::current()?->contentMeta($item) ?? '';
}

function get_term_links(array $item, string $class = 'term-list'): string
{
    return Theme::current()?->termLinks($item, $class) ?? '';
}

function get_pagination(array $pagination, string $basePath = ''): string
{
    return Theme::current()?->pagination($pagination, $basePath) ?? '';
}

function esc_html(mixed $value): string
{
    return Escape::html($value);
}

function esc_attr(mixed $value): string
{
    return Escape::attr($value);
}

function esc_url(mixed $value): string
{
    return Escape::url($value);
}

function esc_js(mixed $value): string
{
    return Escape::js($value);
}

function esc_json(mixed $value): string
{
    return Escape::json($value);
}

function esc_content(mixed $value): string
{
    return Escape::content($value);
}

function esc_xml(mixed $value): string
{
    return Escape::xml($value);
}
