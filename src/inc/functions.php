<?php
declare(strict_types=1);

use App\Service\Front\Theme;
use App\Service\Front\Widgets;
use App\Service\Support\Hooks;
use App\Service\Support\Escape;
use App\Service\Support\I18n;
use App\Service\Support\RequestContext;

function add_action(string $hook, callable $callback, int $priority = 10, int $acceptedArgs = 99): void
{
    Hooks::add($hook, $callback, $priority, $acceptedArgs);
}

function do_action(string $hook, mixed ...$args): void
{
    Hooks::action($hook, ...$args);
}

function add_filter(string $hook, callable $callback, int $priority = 10, int $acceptedArgs = 99): void
{
    Hooks::add($hook, $callback, $priority, $acceptedArgs);
}

function apply_filters(string $hook, mixed $value, mixed ...$args): mixed
{
    return Hooks::filter($hook, $value, ...$args);
}

function register_sidebar(string $id, array $options = []): void
{
    Widgets::registerSidebar($id, $options);
}

function register_widget(string $id, callable $callback, array $options = []): void
{
    Widgets::define($id, $callback, $options);
}

function add_widget(string $sidebar, callable $callback, int $priority = 10, int $acceptedArgs = 0): void
{
    Widgets::add($sidebar, $callback, $priority, $acceptedArgs);
}

function add_widget_instance(string $sidebar, string $widget, array $settings = [], int $priority = 10): void
{
    Widgets::addDefined($sidebar, $widget, $settings, $priority);
}

function has_widgets(string $sidebar): bool
{
    return Widgets::has($sidebar);
}

function get_widgets(string $sidebar): string
{
    return (string)apply_filters('sidebar_html', Widgets::render($sidebar), $sidebar);
}

function widget_box(string $title, string $content, string $class = ''): string
{
    $clean = trim($content);
    if ($clean === '') {
        return '';
    }

    $classes = trim('widget ' . $class);
    return '<section class="' . esc_attr($classes) . '"><h2 class="widget-title">' . esc_html($title) . '</h2>' . $clean . '</section>';
}

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
    return (string)apply_filters('site_title', Theme::current()?->siteTitle() ?? 'TinyCMS');
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
    return (string)apply_filters('head', Theme::current()?->getHead() ?? '');
}

function get_menu(array $options = []): string
{
    return (string)apply_filters('menu_html', Theme::current()?->menu($options) ?? '', $options);
}

function get_footer(): string
{
    return esc_content(apply_filters('footer_text', Theme::current()?->footerText() ?? ''));
}

function get_search_form(string $action = 'search', ?string $query = null): string
{
    $query ??= (string)($_GET['q'] ?? '');
    $form = Theme::current()?->searchForm($action, $query, [
        'placeholder' => t('front.search_placeholder'),
        'button' => t('front.search_button'),
    ]) ?? '';

    return (string)apply_filters('search_form', $form, $action, $query);
}

function get_title(array $item): string
{
    return (string)apply_filters('content_title', Theme::current()?->contentTitle($item) ?? trim((string)($item['name'] ?? '')), $item);
}

function get_excerpt(array $item, int $limit = 0): string
{
    return (string)apply_filters('content_excerpt', Theme::current()?->contentExcerpt($item, $limit) ?? trim(strip_tags((string)($item['excerpt'] ?? ''))), $item, $limit);
}

function get_content(array $item): string
{
    return esc_content(apply_filters('content_body', Theme::current()?->contentBody($item) ?? ($item['body'] ?? ''), $item));
}

function get_permalink(array $item): string
{
    return (string)apply_filters('content_url', Theme::current()?->contentUrl($item) ?? '', $item);
}

function get_thumbnail(array $item, array $options = []): string
{
    return (string)apply_filters('content_thumbnail', Theme::current()?->contentThumbnail($item, $options) ?? '', $item, $options);
}

function get_thumbnail_url(array $item, string $size = 'webp'): string
{
    return (string)apply_filters('content_thumbnail_url', Theme::current()?->contentThumbnailUrl($item, $size) ?? '', $item, $size);
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
