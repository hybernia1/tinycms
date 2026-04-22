<?php
declare(strict_types=1);

use App\Service\Application\Menu;
use App\Service\Infrastructure\Router\Router;
use App\Service\Support\DateTimeFormatter;
use App\Service\Support\Media;
use App\Service\Support\RequestContext;
use App\Service\Support\Slugger;

function themes_init(Router $router, array $settings, string $theme, Menu $menu): array
{
    return ['router' => $router, 'settings' => $settings, 'theme' => trim($theme) !== '' ? trim($theme) : 'default', 'menu' => $menu, 'slugger' => new Slugger()];
}

function themes_setting(array $ctx, string $key, string $default = ''): string { return (string)($ctx['settings'][$key] ?? $default); }
function themes_site_title(array $ctx): string { return themes_setting($ctx, 'sitename', 'TinyCMS'); }
function themes_site_logo(array $ctx): string { return themes_setting($ctx, 'logo'); }
function themes_page_title(array $ctx, ?string $value = null): string { return $value !== null && trim($value) !== '' ? trim($value) : themes_site_title($ctx); }
function themes_url(array $ctx, string $path = ''): string { return $ctx['router']->url($path); }
function themes_theme_url(array $ctx, string $path = ''): string { $themeDir = trim((string)(defined('THEMES_DIR') ? THEMES_DIR : 'themes/'), '/'); return themes_url($ctx, trim($themeDir . '/' . $ctx['theme'] . '/' . ltrim($path, '/'), '/')); }
function themes_media_url(array $ctx, string $path = '', string $size = 'origin'): string { return themes_url($ctx, Media::bySize($path, $size)); }
function themes_content_url(array $ctx, array $item): string { $id = (int)($item['id'] ?? 0); return $id <= 0 ? themes_url($ctx, '') : themes_url($ctx, $ctx['slugger']->slug((string)($item['name'] ?? ''), $id)); }
function themes_term_url(array $ctx, array $term): string { $id = (int)($term['id'] ?? 0); return $id <= 0 ? themes_url($ctx, 'term') : themes_url($ctx, 'term/' . $ctx['slugger']->slug((string)($term['name'] ?? ''), $id)); }
function themes_author_url(array $ctx, array $item): string { $id = (int)($item['author'] ?? 0); if ($id <= 0) { return ''; } $name = trim((string)($item['author_name'] ?? '')); return themes_url($ctx, 'author/' . $ctx['slugger']->slug($name !== '' ? $name : 'author', $id)); }

function themes_menu_items(array $ctx): array
{
    return array_map(static function (array $item) use ($ctx): array {
        $item['href'] = themes_menu_item_url($ctx, (string)($item['url'] ?? ''));
        return $item;
    }, $ctx['menu']->items());
}

function themes_menu(array $ctx, array $options = []): string
{
    $items = themes_menu_items($ctx);
    if ($items === []) {
        return '';
    }

    $class = trim((string)($options['class'] ?? 'site-menu'));
    $itemClass = trim((string)($options['item_class'] ?? 'site-menu-link'));
    $label = trim((string)($options['label'] ?? 'Menu'));
    $links = [];

    foreach ($items as $item) {
        $target = (string)($item['link_target'] ?? '_self') === '_blank' ? '_blank' : '_self';
        $rel = $target === '_blank' ? ' rel="noopener noreferrer"' : '';
        $targetAttr = $target === '_blank' ? ' target="_blank"' : '';
        $labelText = (string)($item['label'] ?? '');
        $iconName = themes_menu_icon_name((string)($item['icon'] ?? ''));
        $hasLabel = trim($labelText) !== '';
        $ariaLabel = $iconName !== '' && !$hasLabel ? ' aria-label="' . themes_esc($iconName) . '"' : '';
        $content = $iconName !== '' ? themes_icon($ctx, $iconName) : '';
        if ($hasLabel) {
            $content .= themes_esc($labelText);
        }
        $links[] = sprintf('<a class="%s" href="%s"%s%s%s>%s</a>', themes_esc($itemClass), themes_esc((string)($item['href'] ?? '')), $targetAttr, $rel, $ariaLabel, $content);
    }

    return sprintf('<nav class="%s" aria-label="%s">%s</nav>', themes_esc($class), themes_esc($label !== '' ? $label : 'Menu'), implode('', $links));
}

function themes_media_srcset(array $ctx, string $path): string
{
    $trimmed = trim($path);
    if ($trimmed === '') {
        return '';
    }

    $sources = [];
    foreach (Media::variants() as $variant) {
        $name = trim((string)($variant['name'] ?? ''));
        $width = (int)($variant['width'] ?? 0);
        if ($name === '' || $width <= 0) {
            continue;
        }
        $sources[] = themes_media_url($ctx, $trimmed, $name) . ' ' . $width . 'w';
    }

    $sources[] = themes_media_url($ctx, $trimmed, 'webp') . ' 1024w';
    return implode(', ', $sources);
}

function themes_content_thumbnail(array $ctx, array $item, array $options = []): string
{
    $thumbnail = trim((string)($item['thumbnail'] ?? ''));
    if ($thumbnail === '') {
        return '';
    }

    $size = trim((string)($options['size'] ?? 'webp'));
    $sizes = trim((string)($options['sizes'] ?? '(max-width: 1024px) 100vw, 1024px'));
    $loading = trim((string)($options['loading'] ?? 'lazy'));
    $class = trim((string)($options['class'] ?? 'content-cover'));
    $name = trim((string)($item['thumbnail_name'] ?? ''));
    if ($name === '') {
        $name = trim((string)($item['name'] ?? ''));
    }

    return sprintf('<figure class="%s"><img src="%s" srcset="%s" sizes="%s" alt="%s" loading="%s" decoding="async"></figure>', themes_esc($class), themes_esc(themes_media_url($ctx, $thumbnail, $size)), themes_esc(themes_media_srcset($ctx, $thumbnail)), themes_esc($sizes), themes_esc($name), themes_esc($loading));
}

function themes_content_author(array $item, string $fallback = ''): string { $author = trim((string)($item['author_name'] ?? '')); return $author !== '' ? $author : trim($fallback); }
function themes_content_date(array $ctx, array $item, string $fallback = ''): string { $raw = trim((string)($item['created'] ?? '')); if ($raw === '') { return trim($fallback); } $timestamp = themes_timestamp($raw); if ($timestamp === null) { return trim($fallback); } $format = DateTimeFormatter::normalizeDateTimeFormat(themes_setting($ctx, 'app_datetime_format', DateTimeFormatter::defaultDateTimeFormat())); return date($format, $timestamp); }
function themes_pagination(array $ctx, array $pagination, string $basePath = '', array $labels = []): string { $totalPages = (int)($pagination['total_pages'] ?? 1); $page = (int)($pagination['page'] ?? 1); if ($totalPages <= 1) { return ''; } $current = max(1, min($page, $totalPages)); $prevLabel = trim((string)($labels['prev'] ?? 'Previous')); $nextLabel = trim((string)($labels['next'] ?? 'Next')); $items = []; if ($current > 1) { $items[] = sprintf('<a href="%s">%s</a>', themes_esc(themes_pagination_url($ctx, $basePath, $current - 1)), themes_esc($prevLabel !== '' ? $prevLabel : 'Previous')); } $items[] = sprintf('<span>%d / %d</span>', $current, $totalPages); if ($current < $totalPages) { $items[] = sprintf('<a href="%s">%s</a>', themes_esc(themes_pagination_url($ctx, $basePath, $current + 1)), themes_esc($nextLabel !== '' ? $nextLabel : 'Next')); } return '<nav class="pagination" aria-label="Pagination">' . implode('', $items) . '</nav>'; }
function themes_search_form(array $ctx, string $action = 'search', string $query = '', array $labels = []): string { $placeholder = trim((string)($labels['placeholder'] ?? 'Search content')); $button = trim((string)($labels['button'] ?? 'Search')); $formAction = themes_url($ctx, trim($action, '/')); $hiddenRoute = themes_hidden_route_field($formAction); $queryValue = trim($query); $state = $queryValue !== '' ? ' is-open' : ''; return sprintf('<form class="search-form search-form-expand%s" action="%s" method="get">%s<input type="search" name="q" value="%s" placeholder="%s" aria-label="%s"><button type="submit" aria-label="%s">%s</button></form>', $state, themes_esc(themes_form_action($formAction)), $hiddenRoute, themes_esc($queryValue), themes_esc($placeholder), themes_esc($button), themes_esc($placeholder), themes_icon($ctx, 'search')); }

function themes_head(array $ctx, array $context = []): string
{
    $kind = trim((string)($context['kind'] ?? 'home'));
    $item = is_array($context['item'] ?? null) ? $context['item'] : [];
    $term = is_array($context['term'] ?? null) ? $context['term'] : [];
    $query = trim((string)($context['query'] ?? ''));
    $title = themes_resolve_head_title($ctx, $kind, $item, $term, isset($context['pageTitle']) ? (string)$context['pageTitle'] : null);
    $description = themes_resolve_head_description($ctx, $kind, $item, $term, $query);
    $ogType = themes_resolve_og_type($kind, $item);
    $url = themes_current_request_url($ctx);
    $image = trim((string)($item['thumbnail'] ?? '')) !== '' ? themes_absolute_url($ctx, themes_media_url($ctx, (string)$item['thumbnail'], 'webp')) : '';
    $author = trim((string)($item['author_name'] ?? ''));
    $tags = ['<meta charset="utf-8">', '<meta name="viewport" content="width=device-width, initial-scale=1">', '<title>' . themes_esc($title) . '</title>', '<meta name="description" content="' . themes_esc($description) . '">', '<link rel="canonical" href="' . themes_esc($url) . '">', '<meta property="og:title" content="' . themes_esc($title) . '">', '<meta property="og:description" content="' . themes_esc($description) . '">', '<meta property="og:type" content="' . themes_esc($ogType) . '">', '<meta property="og:url" content="' . themes_esc($url) . '">', '<meta property="og:site_name" content="' . themes_esc(themes_site_title($ctx)) . '">', '<meta name="twitter:card" content="' . themes_esc($image !== '' ? 'summary_large_image' : 'summary') . '">', '<meta name="twitter:title" content="' . themes_esc($title) . '">', '<meta name="twitter:description" content="' . themes_esc($description) . '">'];

    $contentType = trim((string)($item['type'] ?? ''));
    if ($contentType !== '') { $tags[] = '<meta name="content:type" content="' . themes_esc($contentType) . '">'; }
    if ($author !== '') { $tags[] = '<meta name="author" content="' . themes_esc($author) . '">'; }
    if ($image !== '') { $tags[] = '<meta property="og:image" content="' . themes_esc($image) . '">'; $tags[] = '<meta name="twitter:image" content="' . themes_esc($image) . '">'; }
    $favicon = trim(themes_setting($ctx, 'favicon'));
    if ($favicon !== '') { $tags[] = '<link rel="icon" href="' . themes_esc(themes_url($ctx, $favicon)) . '">'; }
    $jsonLd = themes_json_ld($ctx, $kind, $item, $term, $title, $description, $url, $image, $author, $query);
    if ($jsonLd !== '') { $tags[] = '<script type="application/ld+json">' . $jsonLd . '</script>'; }

    return implode(PHP_EOL, $tags);
}

function themes_form_action(string $url): string { return strtok($url, '?') ?: $url; }
function themes_hidden_route_field(string $url): string { parse_str((string)(parse_url($url, PHP_URL_QUERY) ?? ''), $query); $route = trim((string)($query['route'] ?? '')); return $route === '' ? '' : '<input type="hidden" name="route" value="' . themes_esc($route) . '">'; }
function themes_icon(array $ctx, string $name): string { $sprite = themes_esc(themes_url($ctx, ASSETS_DIR . 'svg/icons.svg#icon-' . trim($name))); return '<svg class="icon" aria-hidden="true"><use href="' . $sprite . '"></use></svg>'; }
function themes_menu_icon_name(string $name): string { $icon = trim(str_starts_with($name, 'icon-') ? substr($name, 5) : $name); return preg_match('/^[a-z0-9_-]+$/i', $icon) === 1 ? $icon : ''; }
function themes_menu_item_url(array $ctx, string $url): string { $value = trim($url); if ($value === '') { return themes_url($ctx, ''); } if (preg_match('#^(https?:)?//#i', $value) === 1 || preg_match('#^(mailto|tel):#i', $value) === 1 || str_starts_with($value, '#')) { return $value; } return themes_url($ctx, $value); }
function themes_pagination_url(array $ctx, string $basePath, int $page): string { $base = trim($basePath, '/'); $separator = str_contains($base, '?') ? '&' : '?'; $suffix = $page > 1 ? $separator . 'page=' . $page : ''; return themes_url($ctx, $base . $suffix); }
function themes_resolve_head_title(array $ctx, string $kind, array $item, array $term, ?string $customTitle): string { $custom = trim((string)$customTitle); if ($custom !== '') { return $custom; } if (($kind === 'content' || $kind === 'home-content') && trim((string)($item['name'] ?? '')) !== '') { return trim((string)$item['name']) . ' | ' . themes_site_title($ctx); } if ($kind === 'archive' && trim((string)($term['name'] ?? '')) !== '') { return trim((string)$term['name']) . ' | ' . themes_site_title($ctx); } return themes_page_title($ctx, null); }
function themes_resolve_head_description(array $ctx, string $kind, array $item, array $term, string $query = ''): string { if ($kind === 'content' || $kind === 'home-content') { $excerpt = themes_plain_text((string)($item['excerpt'] ?? '')); if ($excerpt !== '') { return $excerpt; } $body = themes_plain_text((string)($item['body'] ?? '')); if ($body !== '') { return $body; } } if ($kind === 'archive' && trim((string)($term['name'] ?? '')) !== '') { return trim((string)$term['name']); } if ($kind === 'search' && trim($query) !== '') { return 'Search results for: ' . trim($query); } $meta = themes_plain_text(themes_setting($ctx, 'meta_description')); return $meta !== '' ? $meta : themes_site_title($ctx); }
function themes_resolve_og_type(string $kind, array $item): string { if ($kind !== 'content' && $kind !== 'home-content') { return 'website'; } return in_array(trim((string)($item['type'] ?? '')), ['article', 'news_article', 'blog_posting'], true) ? 'article' : 'website'; }
function themes_json_ld(array $ctx, string $kind, array $item, array $term, string $title, string $description, string $url, string $image, string $author, string $query = ''): string { if ($kind === 'archive') { return themes_json_encode(['@context'=>'https://schema.org','@type'=>'CollectionPage','name'=>$title,'description'=>$description,'url'=>$url,'isPartOf'=>themes_absolute_url($ctx,themes_url($ctx,'')),'about'=>trim((string)($term['name']??'')) !== '' ? ['@type'=>'Thing','name'=>trim((string)$term['name'])] : null]); } if ($kind === 'search') { return themes_json_encode(['@context'=>'https://schema.org','@type'=>'SearchResultsPage','name'=>$title,'description'=>$description,'url'=>$url,'isPartOf'=>themes_absolute_url($ctx,themes_url($ctx,'')),'about'=>trim($query) !== '' ? trim($query) : null]); } if ($kind === 'content' || $kind === 'home-content') { return themes_json_encode(['@context'=>'https://schema.org','@type'=>themes_schema_type((string)($item['type'] ?? '')),'headline'=>$title,'description'=>$description,'mainEntityOfPage'=>$url,'url'=>$url,'image'=>$image !== '' ? $image : null,'datePublished'=>themes_iso_date((string)($item['created'] ?? '')) ?: null,'dateModified'=>themes_iso_date((string)($item['updated'] ?? '')) ?: null,'author'=>$author !== '' ? ['@type'=>'Person','name'=>$author] : null]); } return themes_json_encode(['@context'=>'https://schema.org','@type'=>'WebSite','name'=>themes_site_title($ctx),'url'=>themes_absolute_url($ctx, themes_url($ctx,'')),'potentialAction'=>['@type'=>'SearchAction','target'=>themes_absolute_url($ctx,themes_with_query(themes_url($ctx,'search'),'q={search_term_string}')),'query-input'=>'required name=search_term_string']]); }
function themes_plain_text(string $value, int $limit = 160): string { $clean = trim(html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8')); $clean = preg_replace('/\s+/', ' ', $clean) ?? ''; return $limit > 0 ? mb_substr($clean, 0, $limit) : $clean; }
function themes_timestamp(string $value): ?int { $clean = trim($value); if ($clean === '') { return null; } foreach (['Y-m-d H:i:s','Y-m-d H:i','Y-m-d\\TH:i:s','Y-m-d\\TH:i'] as $format) { $date = DateTimeImmutable::createFromFormat($format, $clean); if ($date instanceof DateTimeImmutable && $date->format($format) === $clean) { return $date->getTimestamp(); } } $timestamp = strtotime($clean); return $timestamp === false ? null : $timestamp; }
function themes_iso_date(string $value): string { $timestamp = themes_timestamp($value); return $timestamp === null ? '' : gmdate('c', $timestamp); }
function themes_schema_type(string $type): string { return match (trim($type)) { 'page' => 'WebPage', 'about_page' => 'AboutPage', 'news_article' => 'NewsArticle', 'blog_posting' => 'BlogPosting', 'faq_page' => 'FAQPage', default => 'Article', }; }
function themes_absolute_url(array $ctx, string $path): string { if (preg_match('#^https?://#i', $path) === 1) { return $path; } if (!RequestContext::hasAuthority()) { return $path; } return RequestContext::scheme() . '://' . RequestContext::authority() . '/' . ltrim($path, '/'); }
function themes_current_request_url(array $ctx): string { $uri = (string)($_SERVER['REQUEST_URI'] ?? '/'); $path = themes_url($ctx, $ctx['router']->requestPath($uri)); $query = themes_public_query($uri); if ($query !== '') { $path = themes_with_query($path, $query); } return themes_absolute_url($ctx, $path); }
function themes_public_query(string $uri): string { parse_str((string)(parse_url($uri, PHP_URL_QUERY) ?? ''), $query); unset($query['route']); return http_build_query($query); }
function themes_with_query(string $url, string $query): string { $clean = ltrim($query, '?&'); return $clean === '' ? $url : $url . (str_contains($url, '?') ? '&' : '?') . $clean; }
function themes_json_encode(array $payload): string { $payload = array_filter($payload, static fn(mixed $value): bool => $value !== null && $value !== ''); return (string)json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG); }
function themes_esc(string $value): string { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }
