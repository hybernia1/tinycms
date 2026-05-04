<?php
declare(strict_types=1);

use App\Service\Application\ThemeDefinition;
use App\Service\Front\Theme;
use App\Service\Support\Avatar;
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
    if (trim((string)($_GET['theme_preview'] ?? '')) !== '') {
        return inline_icon($icon, $class !== '' ? $class : 'icon');
    }

    $href = esc_url(icon_sprite() . '#icon-' . $icon);
    return '<svg class="' . esc_attr($class !== '' ? $class : 'icon') . '" aria-hidden="true" focusable="false"><use href="' . $href . '"></use></svg>';
}

function inline_icon(string $name, string $classes = 'icon'): string
{
    static $symbols = null;

    if ($symbols === null) {
        $symbols = [];
        $file = BASE_DIR . '/' . ASSETS_DIR . 'svg/icons.svg';
        $svg = is_file($file) ? (string)file_get_contents($file) : '';
        preg_match_all('/<symbol\b([^>]*)\bid="icon-([^"]+)"([^>]*)>(.*?)<\/symbol>/s', $svg, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $attrs = $match[1] . ' ' . $match[3];
            preg_match('/\bviewBox="([^"]+)"/', $attrs, $viewBox);
            $symbols[$match[2]] = [
                'viewBox' => $viewBox[1] ?? '0 0 16 16',
                'body' => trim($match[4]),
            ];
        }
    }

    $symbol = $symbols[$name] ?? null;
    if ($symbol === null || $symbol['body'] === '') {
        return '';
    }

    $class = trim($classes) !== '' ? trim($classes) : 'icon';
    return '<svg class="' . esc_attr($class) . '" viewBox="' . esc_attr($symbol['viewBox']) . '" aria-hidden="true" focusable="false">' . $symbol['body'] . '</svg>';
}

function widget_title(string $title, string $icon = ''): string
{
    $title = trim($title);
    if ($title === '') {
        return '';
    }

    $iconHtml = trim($icon) !== '' ? icon($icon, 'widget-title-icon icon') : '';
    return '<h2 class="widget-title">' . $iconHtml . '<span>' . esc_html($title) . '</span></h2>';
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

function get_site_brand(string $class = 'site-title'): string
{
    $brand = Theme::current()?->brand() ?? [];
    $logo = trim((string)($brand['logo'] ?? ''));
    $title = (string)($brand['title'] ?? '');

    if ($logo === '' && $title === '') {
        return '';
    }

    $class = trim($class);
    $logoHtml = $logo !== '' ? '<img src="' . esc_url(site_url($logo)) . '" alt="' . esc_attr(site_title()) . '" class="site-logo">' : '';
    $titleHtml = $title !== '' ? '<span>' . esc_html($title) . '</span>' : '';

    return '<a href="' . esc_url(site_url()) . '"' . ($class !== '' ? ' class="' . esc_attr($class) . '"' : '') . '>' . $logoHtml . $titleHtml . '</a>';
}

function site_layout_class(): string
{
    return Theme::current()?->layoutClass() ?? 'theme-layout-default';
}

function get_avatar_url(?array $source = null, int $size = 64, bool $inline = false): string
{
    $seed = Avatar::userSeed(author_user($source));
    return $inline ? Avatar::dataUrl($seed, $size) : Avatar::url($seed, $size);
}

function get_avatar(?array $source = null, string $class = 'avatar', int $size = 64, bool $inline = false): string
{
    $class = trim($class);
    $src = get_avatar_url($source, $size, $inline);
    $src = $inline ? esc_attr($src) : esc_url($src);

    return '<img src="' . $src . '" alt=""' . ($class !== '' ? ' class="' . esc_attr($class) . '"' : '') . '>';
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

function get_widget_area(string $area): string
{
    return Theme::current()?->widgetArea($area) ?? '';
}

function theme_option(string $key, string $default = ''): string
{
    return Theme::current()?->setting($key, $default) ?? $default;
}

function theme_option_enabled(string $key, bool $default = false): bool
{
    return theme_option($key, $default ? '1' : '0') === '1';
}

function register_theme(array $manifest): void
{
    ThemeDefinition::current()?->registerTheme($manifest);
}

function register_theme_option(string $key, array $field): void
{
    ThemeDefinition::current()?->registerOption($key, $field);
}

function register_theme_section(string $key, string $label = '', array $fields = []): void
{
    ThemeDefinition::current()?->registerCustomizerSection($key, $label, $fields);
}

function register_widget_area(string $area, string $label = ''): void
{
    ThemeDefinition::current()?->registerWidgetArea($area, $label);
}

function get_footer(): string
{
    return esc_content(Theme::current()?->footerText() ?? '');
}

function get_search_form(string $action = 'search', ?string $query = null, bool $respectThemeSetting = true): string
{
    $query ??= (string)($_GET['q'] ?? '');
    return Theme::current()?->searchForm($action, $query, [
        'placeholder' => t('front.search_placeholder'),
        'button' => t('front.search_button'),
    ], $respectThemeSetting) ?? '';
}

function get_content_title(?array $source = null): string
{
    $item = content_item($source);
    return Theme::current()?->contentTitle($item) ?? trim((string)($item['name'] ?? ''));
}

function get_content_excerpt(?array $source = null, int $limit = 0): string
{
    $item = content_item($source);
    return Theme::current()?->contentExcerpt($item, $limit) ?? trim(strip_tags((string)($item['excerpt'] ?? '')));
}

function get_content_body(?array $source = null): string
{
    $item = content_item($source);
    return Theme::current()?->contentBody($item) ?? esc_content($item['body'] ?? '');
}

function content_comments_enabled(?array $source = null): bool
{
    $item = content_item($source);
    return Theme::current()?->commentsEnabled($item) ?? false;
}

function get_content_comments(?array $source = null): string
{
    $item = content_item($source);
    return Theme::current()?->commentsList($item) ?? '';
}

function get_content_comments_count(?array $source = null): int
{
    $item = content_item($source);
    return Theme::current()?->commentsCount($item) ?? 0;
}

function get_content_views_count(?array $source = null): int
{
    $item = content_item($source);
    return Theme::current()?->viewsCount($item) ?? 0;
}

function get_content_last_visit(?array $source = null): string
{
    $item = content_item($source);
    return Theme::current()?->lastVisit($item) ?? '';
}

function get_content_comments_form(?array $source = null, ?int $parentId = null, ?int $replyToId = null): string
{
    $item = content_item($source);
    return Theme::current()?->commentsForm($item, $parentId, $replyToId) ?? '';
}

function get_content_url(?array $source = null): string
{
    $item = content_item($source);
    return Theme::current()?->contentUrl($item) ?? '';
}

function get_content_thumbnail(?array $source = null, array $options = []): string
{
    $item = content_item($source);
    return Theme::current()?->contentThumbnail($item, $options) ?? '';
}

function get_author(?array $source = null, string $fallback = ''): string
{
    $user = author_user($source, $fallback);
    $name = trim((string)($user['name'] ?? ''));

    return $name !== '' ? $name : trim($fallback);
}

function get_author_url(?array $source = null): string
{
    $user = author_user($source);
    if ((int)($user['id'] ?? 0) <= 0) {
        return '';
    }

    return Theme::current()?->authorUrl([
        'author' => (int)$user['id'],
        'author_name' => (string)($user['name'] ?? ''),
    ]) ?? '';
}

function get_content_date(?array $source = null): string
{
    $item = content_item($source);
    return Theme::current()?->contentDate($item) ?? '';
}

function get_content_terms(?array $source = null): array
{
    $item = content_item($source);
    return array_values(array_filter((array)($item['terms'] ?? []), static function (mixed $term): bool {
        return is_array($term) && (int)($term['id'] ?? 0) > 0 && trim((string)($term['name'] ?? '')) !== '';
    }));
}

function get_term_url(array $term): string
{
    $id = (int)($term['id'] ?? 0);
    if ($id <= 0) {
        return '';
    }

    return Theme::current()?->termUrl($term) ?? '';
}

function get_pagination(array|string|null $pagination = null, string $basePath = ''): string
{
    if (is_string($pagination)) {
        $basePath = $pagination;
        $pagination = null;
    }

    $pagination ??= Theme::current()?->paginationContext() ?? [];
    $basePath = $basePath !== '' ? $basePath : (Theme::current()?->paginationPath() ?? '');

    return Theme::current()?->pagination($pagination, $basePath) ?? '';
}

function include_partial(string $name, array $context = []): void
{
    Theme::current()?->include($name, $context);
}

function content_item(?array $source = null): array
{
    return $source ?? current_content_item();
}

function author_user(?array $source = null, string $fallback = ''): array
{
    $source = content_item($source);
    $hasAuthorFields = array_key_exists('author', $source)
        || array_key_exists('author_name', $source)
        || array_key_exists('author_email', $source);
    $name = trim((string)($hasAuthorFields ? ($source['author_name'] ?? $fallback) : ($source['name'] ?? $fallback)));
    $email = trim((string)($hasAuthorFields ? ($source['author_email'] ?? '') : ($source['email'] ?? '')));
    $id = (int)($hasAuthorFields ? ($source['author'] ?? 0) : ($source['id'] ?? $source['ID'] ?? 0));

    return [
        'id' => $id,
        'name' => $name,
        'email' => $email,
    ];
}

function current_content_item(): array
{
    return Theme::current()?->item() ?? [];
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
