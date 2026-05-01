<?php
declare(strict_types=1);

use App\Service\Application\Widget;
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

function site_logo(string $class = 'site-logo'): string
{
    $logo = trim((string)(Theme::current()?->siteLogo() ?? ''));
    $url = $logo !== '' ? site_url($logo) : '';
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

function get_widget_area(string $area): string
{
    return Theme::current()?->widgetArea($area) ?? '';
}

function widgets_enabled(): bool
{
    return Theme::current()?->widgetsEnabled() ?? false;
}

function register_widget_area(string $area, string $label = ''): void
{
    Widget::current()?->registerArea($area, $label);
}

function get_footer(): string
{
    return esc_content(Theme::current()?->footerText() ?? '');
}

function get_search_form(string $action = 'search', ?string $query = null): string
{
    $query ??= (string)($_GET['q'] ?? '');
    return Theme::current()?->searchForm($action, $query, [
        'placeholder' => t('front.search_placeholder'),
        'button' => t('front.search_button'),
    ]) ?? '';
}

function get_title(): string
{
    $item = current_content_item();
    return Theme::current()?->contentTitle($item) ?? trim((string)($item['name'] ?? ''));
}

function get_excerpt(int $limit = 0): string
{
    $item = current_content_item();
    return Theme::current()?->contentExcerpt($item, $limit) ?? trim(strip_tags((string)($item['excerpt'] ?? '')));
}

function get_content(): string
{
    $item = current_content_item();
    return Theme::current()?->contentBody($item) ?? esc_content($item['body'] ?? '');
}

function comments_enabled(): bool
{
    $item = current_content_item();
    return Theme::current()?->commentsEnabled($item) ?? false;
}

function get_comments_list(): string
{
    $item = current_content_item();
    return Theme::current()?->commentsList($item) ?? '';
}

function get_comments_form(?int $parentId = null, ?int $replyToId = null): string
{
    $item = current_content_item();
    return Theme::current()?->commentsForm($item, $parentId, $replyToId) ?? '';
}

function get_permalink(): string
{
    $item = current_content_item();
    return Theme::current()?->contentUrl($item) ?? '';
}

function get_thumbnail(array $options = []): string
{
    $item = current_content_item();
    return Theme::current()?->contentThumbnail($item, $options) ?? '';
}

function get_author(string $fallback = ''): string
{
    $item = current_content_item();
    return Theme::current()?->contentAuthor($item, $fallback) ?? trim($fallback);
}

function get_author_url(): string
{
    $item = current_content_item();
    return Theme::current()?->authorUrl($item) ?? '';
}

function get_date(string $fallback = ''): string
{
    $item = current_content_item();
    return Theme::current()?->contentDate($item, $fallback) ?? trim($fallback);
}

function get_term_links(string $class = 'term-list'): string
{
    $item = current_content_item();
    return Theme::current()?->termLinks($item, $class) ?? '';
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

function current_content_item(): array
{
    return Theme::current()?->item() ?? [];
}

function use_content_item(array $item): void
{
    Theme::current()?->setItem($item);
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
