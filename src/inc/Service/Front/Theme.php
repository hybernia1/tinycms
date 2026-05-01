<?php
declare(strict_types=1);

namespace App\Service\Front;

use App\Service\Application\Menu;
use App\Service\Application\Comment;
use App\Service\Application\Widget;
use App\Service\Auth\Auth;
use App\Service\Infrastructure\Db\Connection;
use App\Service\Infrastructure\Db\Table;
use App\Service\Infrastructure\Router\Router;
use App\Service\Support\Date;
use App\Service\Support\Csrf;
use App\Service\Support\I18n;
use App\Service\Support\Media;
use App\Service\Support\RequestContext;
use App\Service\Support\Slugger;

final class Theme
{
    private static ?self $current = null;
    private string $theme;
    private Slugger $slugger;
    private array $context = [];
    private array $contentPathCache = [];
    private ?\Closure $includeThemeFile = null;

    public function __construct(
        private Router $router,
        private array $settings,
        string $theme,
        private Menu $menu,
        private Widget $widgets,
        private Comment $comments,
        private Auth $auth,
        private Csrf $csrf
    ) {
        $this->theme = trim($theme) !== '' ? trim($theme) : 'default';
        $this->slugger = new Slugger();
    }

    public static function setCurrent(?self $theme): void
    {
        self::$current = $theme;
    }

    public static function current(): ?self
    {
        return self::$current;
    }

    public function setting(string $key, string $default = ''): string
    {
        return (string)($this->settings[$key] ?? $default);
    }

    public function siteTitle(): string
    {
        return $this->setting('sitename', 'TinyCMS');
    }

    public function siteLogo(): string
    {
        if ($this->setting('show_logo', '1') !== '1') {
            return '';
        }

        return $this->setting('logo');
    }

    public function footerText(): string
    {
        return trim($this->setting('footer_text'));
    }

    public function language(): string
    {
        $lang = trim((string)($this->settings['app_lang'] ?? ''));
        return $lang !== '' ? $lang : I18n::htmlLang();
    }

    public function pageTitle(?string $value = null): string
    {
        if ($value !== null && trim($value) !== '') {
            return trim($value);
        }

        return $this->siteTitle();
    }

    public function setContext(array $context): void
    {
        $this->context = $context;
    }

    public function setIncludeThemeFile(callable $includeThemeFile): void
    {
        $this->includeThemeFile = \Closure::fromCallable($includeThemeFile);
    }

    public function include(string $name, array $context = []): void
    {
        if ($this->includeThemeFile === null) {
            return;
        }

        $previous = $this->context;
        $this->context = array_replace($this->context, $context);

        try {
            ($this->includeThemeFile)($name, $this->context);
        } finally {
            $this->context = $previous;
        }
    }

    public function item(): array
    {
        return is_array($this->context['item'] ?? null) ? $this->context['item'] : [];
    }

    public function setItem(array $item): void
    {
        $this->context['item'] = $item;
    }

    public function paginationContext(): array
    {
        return is_array($this->context['pagination'] ?? null) ? $this->context['pagination'] : [];
    }

    public function paginationPath(): string
    {
        return trim((string)($this->context['paginationPath'] ?? ''));
    }

    public function getHead(): string
    {
        return $this->head($this->context);
    }

    public function head(array $context = []): string
    {
        $kind = trim((string)($context['kind'] ?? 'home'));
        $item = is_array($context['item'] ?? null) ? $context['item'] : [];
        $term = is_array($context['term'] ?? null) ? $context['term'] : [];
        $query = trim((string)($context['query'] ?? ''));
        $title = $this->resolveHeadTitle($kind, $item, $term, isset($context['pageTitle']) ? (string)$context['pageTitle'] : null, $query);
        $description = $this->resolveHeadDescription($kind, $item, $term, $query);
        $ogType = $this->resolveOgType($kind, $item);
        $url = $this->currentRequestUrl();
        $image = trim((string)($item['thumbnail'] ?? '')) !== '' ? $this->absoluteUrl($this->mediaUrl((string)$item['thumbnail'], 'webp')) : '';
        $author = trim((string)($item['author_name'] ?? ''));
        $tags = [
            '<meta charset="utf-8">',
            '<meta name="viewport" content="width=device-width, initial-scale=1">',
            '<title>' . esc_html($title) . '</title>',
            '<meta name="description" content="' . esc_attr($description) . '">',
            '<link rel="canonical" href="' . esc_url($url) . '">',
            '<meta property="og:title" content="' . esc_attr($title) . '">',
            '<meta property="og:description" content="' . esc_attr($description) . '">',
            '<meta property="og:type" content="' . esc_attr($ogType) . '">',
            '<meta property="og:url" content="' . esc_url($url) . '">',
            '<meta property="og:site_name" content="' . esc_attr($this->siteTitle()) . '">',
            '<meta name="twitter:card" content="' . esc_attr($image !== '' ? 'summary_large_image' : 'summary') . '">',
            '<meta name="twitter:title" content="' . esc_attr($title) . '">',
            '<meta name="twitter:description" content="' . esc_attr($description) . '">',
        ];

        $contentType = trim((string)($item['type'] ?? ''));
        if ($contentType !== '') {
            $tags[] = '<meta name="content:type" content="' . esc_attr($contentType) . '">';
        }
        if ($author !== '') {
            $tags[] = '<meta name="author" content="' . esc_attr($author) . '">';
        }
        if ($image !== '') {
            $tags[] = '<meta property="og:image" content="' . esc_url($image) . '">';
            $tags[] = '<meta name="twitter:image" content="' . esc_url($image) . '">';
        }
        if (($kind === 'content' || $kind === 'home-content') && $this->isArticleType((string)($item['type'] ?? ''))) {
            $published = $this->isoDate((string)($item['created'] ?? ''));
            $updated = $this->isoDate((string)($item['updated'] ?? ''));
            if ($published !== '') {
                $tags[] = '<meta property="article:published_time" content="' . esc_attr($published) . '">';
            }
            if ($updated !== '') {
                $tags[] = '<meta property="article:modified_time" content="' . esc_attr($updated) . '">';
            }
            if ($author !== '') {
                $tags[] = '<meta property="article:author" content="' . esc_attr($author) . '">';
            }
        }
        if ($kind === 'search') {
            $tags[] = '<meta name="robots" content="noindex,follow">';
        }
        $favicon = trim($this->setting('favicon'));
        if ($favicon !== '') {
            $tags[] = '<link rel="icon" href="' . esc_url($this->url($favicon)) . '">';
        }
        $tags[] = '<link rel="stylesheet" href="' . esc_url($this->url(ASSETS_DIR . 'css/block.css')) . '">';
        $tags[] = '<link rel="stylesheet" href="' . esc_url($this->themeUrl('assets/css/style.css')) . '">';
        if ($this->commentsEnabled($item)) {
            $tags[] = '<script defer src="' . esc_url($this->url(ASSETS_DIR . 'js/front/comment-reply.js')) . '"></script>';
        }

        $jsonLd = $this->jsonLd($kind, $item, $term, $title, $description, $url, $image, $author, $query);
        if ($jsonLd !== '') {
            $tags[] = '<script type="application/ld+json">' . $jsonLd . '</script>';
        }

        return implode(PHP_EOL, $tags);
    }

    public function url(string $path = ''): string
    {
        return $this->router->url($path);
    }

    public function themeUrl(string $path = ''): string
    {
        $themeDir = trim((string)(defined('THEMES_DIR') ? THEMES_DIR : 'themes/'), '/');
        return $this->url(trim($themeDir . '/' . $this->theme . '/' . ltrim($path, '/'), '/'));
    }

    public function mediaUrl(string $path = '', string $size = 'origin'): string
    {
        return $this->url(Media::bySize($path, $size));
    }

    public function contentUrl(array $item): string
    {
        $id = (int)($item['id'] ?? 0);
        if ($id <= 0) {
            return $this->url('');
        }

        return $this->url($this->slugger->slug((string)($item['name'] ?? ''), $id));
    }

    public function termUrl(array $term): string
    {
        $id = (int)($term['id'] ?? 0);
        if ($id <= 0) {
            return $this->url('term');
        }

        return $this->url('term/' . $this->slugger->slug((string)($term['name'] ?? ''), $id));
    }

    public function authorUrl(array $item): string
    {
        $id = (int)($item['author'] ?? 0);
        if ($id <= 0) {
            return '';
        }

        $name = trim((string)($item['author_name'] ?? ''));
        return $this->url('author/' . $this->slugger->slug($name !== '' ? $name : 'author', $id));
    }

    public function contentTitle(array $item): string
    {
        return trim((string)($item['name'] ?? ''));
    }

    public function menuItems(): array
    {
        return array_map(function (array $item): array {
            $item['href'] = $this->menuItemUrl((string)($item['url'] ?? ''));
            return $item;
        }, $this->menu->items());
    }

    public function menu(array $options = []): string
    {
        $items = $this->menuItems();
        if ($items === []) {
            return '';
        }

        $class = trim((string)($options['class'] ?? 'site-menu'));
        $itemClass = trim((string)($options['item_class'] ?? 'site-menu-link'));
        $label = trim((string)($options['label'] ?? 'Menu'));
        $showIcons = (bool)($options['show_icons'] ?? true);
        $currentPath = $this->router->requestPath((string)($_SERVER['REQUEST_URI'] ?? '/'));
        $links = [];

        foreach ($items as $item) {
            $target = (string)($item['link_target'] ?? '_self') === '_blank' ? '_blank' : '_self';
            $rel = $target === '_blank' ? ' rel="noopener noreferrer"' : '';
            $targetAttr = $target === '_blank' ? ' target="_blank"' : '';
            $labelText = (string)($item['label'] ?? '');
            $iconName = $this->menuIconName((string)($item['icon'] ?? ''));
            $hasLabel = trim($labelText) !== '';
            $ariaLabel = $showIcons && $iconName !== '' && !$hasLabel ? ' aria-label="' . esc_attr($iconName) . '"' : '';
            $content = $showIcons && $iconName !== '' ? icon($iconName) : '';
            if ($hasLabel) {
                $content .= esc_html($labelText);
            }
            $itemPath = $this->menuItemCurrentPath((string)($item['url'] ?? ''));
            $classes = trim($itemClass . ($itemPath !== null && $itemPath === $currentPath ? ' is-current' : ''));
            $links[] = sprintf(
                '<a class="%s" href="%s"%s%s%s>%s</a>',
                esc_attr($classes),
                esc_url((string)($item['href'] ?? '')),
                $targetAttr,
                $rel,
                $ariaLabel,
                $content,
            );
        }

        return sprintf(
            '<nav class="%s" aria-label="%s">%s</nav>',
            esc_attr($class),
            esc_attr($label !== '' ? $label : 'Menu'),
            implode('', $links),
        );
    }

    public function widgetArea(string $area): string
    {
        if (!$this->widgetsEnabled()) {
            return '';
        }

        return $this->widgets->renderArea($area);
    }

    public function widgetsEnabled(): bool
    {
        return $this->setting('enable_widgets', '1') === '1';
    }

    public function contentThumbnail(array $item, array $options = []): string
    {
        $thumbnail = trim((string)($item['thumbnail'] ?? ''));
        if ($thumbnail === '') {
            return '';
        }

        $size = trim((string)($options['size'] ?? 'webp'));
        $sizes = trim((string)($options['sizes'] ?? '(max-width: 1024px) 100vw, 1024px'));
        $loading = trim((string)($options['loading'] ?? 'lazy'));
        $class = trim((string)($options['class'] ?? 'content-cover'));
        $wrapped = (bool)($options['wrap'] ?? true);
        $name = trim((string)($item['thumbnail_name'] ?? ''));
        if ($name === '') {
            $name = $this->contentTitle($item);
        }

        $img = sprintf(
            '<img%s src="%s" srcset="%s" sizes="%s" alt="%s" loading="%s" decoding="async">',
            $this->classAttr($class, $wrapped),
            esc_url($this->mediaUrl($thumbnail, $size)),
            esc_attr($this->mediaSrcSet($thumbnail)),
            esc_attr($sizes),
            esc_attr($name),
            esc_attr($loading),
        );

        if (!$wrapped) {
            return $img;
        }

        return '<figure class="' . esc_attr($class !== '' ? $class : 'content-cover') . '">' . $img . '</figure>';
    }

    public function contentExcerpt(array $item, int $limit = 0): string
    {
        $excerpt = $this->plainText((string)($item['excerpt'] ?? ''), $limit);
        if ($excerpt !== '') {
            return $excerpt;
        }

        return $limit > 0 ? $this->plainText((string)($item['body'] ?? ''), $limit) : '';
    }

    public function contentBody(array $item): string
    {
        return esc_content($item['body'] ?? '');
    }

    public function commentsEnabled(array $item): bool
    {
        return (int)($item['id'] ?? 0) > 0 && (int)($item['comments_enabled'] ?? 0) === 1;
    }

    public function commentsList(array $item): string
    {
        if (!$this->commentsEnabled($item)) {
            return '';
        }

        $items = $this->comments->treeForContent((int)$item['id']);
        $html = '<div class="comments-list"><h2>' . esc_html(t('front.comments_title', 'Comments')) . '</h2>';

        if ($items === []) {
            return $html . '<p class="text-muted">' . esc_html(t('front.comments_empty', 'No comments yet.')) . '</p></div>';
        }

        $html .= '<ol class="comment-thread">';
        foreach ($items as $comment) {
            $html .= $this->commentItem($item, $comment, true);
        }

        return $html . '</ol></div>';
    }

    public function commentsForm(array $item, ?int $parentId = null, ?int $replyToId = null): string
    {
        if (!$this->commentsEnabled($item)) {
            return '';
        }

        if (!$this->auth->check()) {
            return '<p class="comments-login">' . sprintf(
                esc_html(t('front.comments_login_required', 'Please %ssign in%s to comment.')),
                '<a href="' . esc_url($this->url('auth/login')) . '">',
                '</a>'
            ) . '</p>';
        }

        $parentId = max(0, (int)($parentId ?? 0));
        $replyToId = max(0, (int)($replyToId ?? 0));
        $action = $this->url('comments/' . (int)$item['id'] . '/add');
        $heading = $parentId > 0 ? t('front.comments_reply_title', 'Reply') : t('front.comments_form_title', 'Add comment');
        $formTargetId = $replyToId > 0 ? $replyToId : $parentId;
        $formId = $formTargetId > 0 ? ' id="comment-reply-form-' . $formTargetId . '" data-comment-reply-form' : '';

        return sprintf(
            '<form class="%s"%s action="%s" method="post">%s<input type="hidden" name="parent" value="%d"><input type="hidden" name="reply_to" value="%d"><input type="hidden" name="return" value="%s"><h3>%s</h3><textarea name="body" rows="4" required></textarea><button type="submit">%s</button></form>',
            $parentId > 0 ? 'comment-form comment-reply-form' : 'comment-form',
            $formId,
            esc_url($this->formAction($action)),
            $this->hiddenRouteField($action) . $this->csrf->field(),
            $parentId,
            $replyToId,
            esc_attr($this->commentReturnPath()),
            esc_html($heading),
            esc_html(t('front.comments_submit', 'Send comment')),
        );
    }

    public function contentAuthor(array $item, string $fallback = ''): string
    {
        $author = trim((string)($item['author_name'] ?? ''));
        if ($author !== '') {
            return $author;
        }

        return trim($fallback);
    }

    public function contentDate(array $item, string $fallback = ''): string
    {
        $raw = trim((string)($item['created'] ?? ''));
        if ($raw === '') {
            return trim($fallback);
        }

        $timestamp = $this->timestamp($raw);
        if ($timestamp === null) {
            return trim($fallback);
        }

        $format = Date::normalizeDateTimeFormat($this->setting('app_datetime_format', APP_DATETIME_FORMAT));
        return date($format, $timestamp);
    }

    public function termLinks(array $item, string $class = 'term-list'): string
    {
        $terms = $this->contentTerms($item);
        if ($terms === []) {
            return '';
        }

        $items = [];
        foreach ($terms as $term) {
            $name = trim((string)($term['name'] ?? ''));
            if ($name === '') {
                continue;
            }

            $items[] = '<li><a href="' . esc_url($this->termUrl($term)) . '">' . esc_html($name) . '</a></li>';
        }

        return $items !== [] ? '<ul class="' . esc_attr($class) . '">' . implode('', $items) . '</ul>' : '';
    }

    public function pagination(array $pagination, string $basePath = ''): string
    {
        $totalPages = (int)($pagination['total_pages'] ?? 1);
        $page = (int)($pagination['page'] ?? 1);
        if ($totalPages <= 1) {
            return '';
        }

        $current = max(1, min($page, $totalPages));
        $items = [];
        $link = function (int $target, string $label, string $class = '', string $aria = '') use ($basePath): string {
            $classAttr = $class !== '' ? ' class="' . esc_attr($class) . '"' : '';
            $ariaAttr = $aria !== '' ? ' aria-label="' . esc_attr($aria) . '"' : '';

            return '<a' . $classAttr . ' href="' . esc_url($this->paginationUrl($basePath, $target)) . '"' . $ariaAttr . '>' . $label . '</a>';
        };
        $disabled = static function (string $label, string $class = ''): string {
            $classAttr = trim('pagination-disabled ' . $class);

            return '<span class="' . esc_attr($classAttr) . '">' . $label . '</span>';
        };

        $items[] = $current > 1 ? $link(1, '&laquo;', '', t('front.first_page', 'First page')) : $disabled('&laquo;');
        $items[] = $current > 1 ? $link($current - 1, '&lsaquo;', '', t('front.prev')) : $disabled('&lsaquo;');

        $pages = [1, $current - 1, $current, $current + 1, $totalPages];
        $pages = array_values(array_unique(array_filter($pages, static fn(int $item): bool => $item >= 1 && $item <= $totalPages)));
        sort($pages);
        $last = 0;

        foreach ($pages as $item) {
            if ($last > 0 && $item > $last + 1) {
                $items[] = '<span class="pagination-gap">...</span>';
            }

            $items[] = $item === $current
                ? '<span class="is-current" aria-current="page">' . $item . '</span>'
                : $link($item, (string)$item, '', t('front.page', 'Page') . ' ' . $item);
            $last = $item;
        }

        $items[] = $current < $totalPages ? $link($current + 1, '&rsaquo;', '', t('front.next')) : $disabled('&rsaquo;');
        $items[] = $current < $totalPages ? $link($totalPages, '&raquo;', '', t('front.last_page', 'Last page')) : $disabled('&raquo;');

        return '<nav class="pagination" aria-label="Pagination">' . implode('', $items) . '</nav>';
    }

    public function searchForm(string $action = 'search', string $query = '', array $labels = []): string
    {
        $placeholder = trim((string)($labels['placeholder'] ?? 'Search content'));
        $button = trim((string)($labels['button'] ?? 'Search'));
        $formAction = $this->url(trim($action, '/'));
        $hiddenRoute = $this->hiddenRouteField($formAction);
        $queryValue = trim($query);
        $value = esc_attr($queryValue);
        $state = $queryValue !== '' ? ' is-open' : '';

        return sprintf(
            '<form class="search-form search-form-expand%s" action="%s" method="get">%s<input type="search" name="q" value="%s" placeholder="%s" aria-label="%s"><button type="submit" aria-label="%s">%s</button></form>',
            $state,
            esc_url($this->formAction($formAction)),
            $hiddenRoute,
            $value,
            esc_attr($placeholder),
            esc_attr($button),
            esc_attr($placeholder),
            icon('search'),
        );
    }

    private function formAction(string $url): string
    {
        return strtok($url, '?') ?: $url;
    }

    private function hiddenRouteField(string $url): string
    {
        parse_str((string)(parse_url($url, PHP_URL_QUERY) ?? ''), $query);
        $route = trim((string)($query['route'] ?? ''));

        return $route === '' ? '' : '<input type="hidden" name="route" value="' . esc_attr($route) . '">';
    }

    private function mediaSrcSet(string $path): string
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

            $sources[] = $this->mediaUrl($trimmed, $name) . ' ' . $width . 'w';
        }

        $sources[] = $this->mediaUrl($trimmed, 'webp') . ' 1024w';
        return implode(', ', $sources);
    }

    private function contentTerms(array $item): array
    {
        return array_values(array_filter((array)($item['terms'] ?? []), static fn(mixed $term): bool => is_array($term)));
    }

    private function commentItem(array $item, array $comment, bool $allowReply, int $threadParentId = 0): string
    {
        $author = trim((string)($comment['author_name'] ?? ''));
        if ($author === '') {
            $author = t('front.comments_no_author', 'No author');
        }

        $commentId = (int)$comment['id'];
        $threadParentId = $threadParentId > 0 ? $threadParentId : $commentId;
        $html = '<li class="comment-item" id="comment-' . $commentId . '">';
        $html .= '<article class="comment-card">';
        $html .= '<header class="comment-meta"><strong>' . esc_html($author) . '</strong><a href="#comment-' . $commentId . '"><time datetime="' . esc_attr($this->isoDate((string)($comment['created'] ?? ''))) . '">' . esc_html($this->commentDate((string)($comment['created'] ?? ''))) . '</time></a></header>';
        if ((int)($comment['reply_to'] ?? 0) > 0) {
            $replyAuthor = trim((string)($comment['reply_to_author_name'] ?? ''));
            if ($replyAuthor === '') {
                $replyAuthor = '#' . (int)$comment['reply_to'];
            }
            $html .= '<p class="comment-reply-context"><a href="#comment-' . (int)$comment['reply_to'] . '">' . esc_html(sprintf(t('front.comments_replying_to', 'Replying to %s'), $replyAuthor)) . '</a></p>';
        }
        $html .= '<div class="comment-body">' . esc_content($comment['body'] ?? '') . '</div>';
        if ($allowReply && $this->auth->check()) {
            $html .= '<button class="comment-reply" type="button" data-comment-reply data-comment-reply-target="comment-reply-form-' . $commentId . '" aria-controls="comment-reply-form-' . $commentId . '" aria-expanded="false">' . esc_html(t('front.comments_reply_title', 'Reply')) . '</button>';
            $html .= $this->commentsForm($item, $threadParentId, $commentId !== $threadParentId ? $commentId : null);
        }
        $html .= '</article>';

        $children = array_values(array_filter((array)($comment['children'] ?? []), static fn(mixed $child): bool => is_array($child)));
        if ($children !== []) {
            $html .= '<ol class="comment-children">';
            foreach ($children as $child) {
                $html .= $this->commentItem($item, $child, true, $commentId);
            }
            $html .= '</ol>';
        }

        return $html . '</li>';
    }

    private function commentDate(string $value): string
    {
        $timestamp = $this->timestamp($value);
        if ($timestamp === null) {
            return '';
        }

        $format = Date::normalizeDateTimeFormat($this->setting('app_datetime_format', APP_DATETIME_FORMAT));
        return date($format, $timestamp);
    }

    private function commentReturnPath(): string
    {
        $uri = (string)($_SERVER['REQUEST_URI'] ?? '/');
        $path = strtok($uri, '#');
        return ($path !== false && $path !== '' ? $path : '/') . '#comments';
    }

    private function classAttr(string $class, bool $wrapped): string
    {
        return !$wrapped && $class !== '' ? ' class="' . esc_attr($class) . '"' : '';
    }

    private function menuIconName(string $name): string
    {
        $icon = trim(str_starts_with($name, 'icon-') ? substr($name, 5) : $name);
        return preg_match('/^[a-z0-9_-]+$/i', $icon) === 1 ? $icon : '';
    }

    private function menuItemUrl(string $url): string
    {
        $value = trim($url);
        if ($value === '') {
            return $this->url('');
        }

        if (preg_match('#^(https?:)?//#i', $value) === 1 || preg_match('#^(mailto|tel):#i', $value) === 1 || str_starts_with($value, '#')) {
            return $value;
        }

        $contentPath = $this->contentPathFromMenuUrl($value);
        if ($contentPath !== null) {
            return $this->url($contentPath);
        }

        return $this->url($value);
    }

    private function menuItemCurrentPath(string $url): ?string
    {
        $value = trim($url);
        if ($value === '') {
            return '';
        }

        if (str_starts_with($value, '#') || preg_match('#^(https?:)?//#i', $value) === 1 || preg_match('#^(mailto|tel):#i', $value) === 1) {
            return null;
        }

        return $this->router->requestPath($this->url($this->contentPathFromMenuUrl($value) ?? $value));
    }

    private function contentPathFromMenuUrl(string $url): ?string
    {
        $path = trim((string)(parse_url(trim($url), PHP_URL_PATH) ?? ''), '/');
        if ($path === '' || !ctype_digit($path)) {
            return null;
        }

        $id = (int)$path;
        if ($id <= 0) {
            return null;
        }

        if (array_key_exists($id, $this->contentPathCache)) {
            return $this->contentPathCache[$id];
        }

        $contentTable = Table::name('content');
        $stmt = Connection::get()->prepare("SELECT id, name FROM $contentTable WHERE id = :id AND status = :status AND created <= :now LIMIT 1");
        $stmt->execute(['id' => $id, 'status' => 'published', 'now' => date('Y-m-d H:i:s')]);
        $item = $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
        $this->contentPathCache[$id] = is_array($item) ? $this->slugger->slug((string)($item['name'] ?? ''), $id) : null;

        return $this->contentPathCache[$id];
    }

    private function paginationUrl(string $basePath, int $page): string
    {
        $base = trim($basePath, '/');
        $separator = str_contains($base, '?') ? '&' : '?';
        $suffix = $page > 1 ? $separator . 'page=' . $page : '';
        return $this->url($base . $suffix);
    }

    private function resolveHeadTitle(string $kind, array $item, array $term, ?string $customTitle, string $query = ''): string
    {
        $custom = trim((string)$customTitle);
        if ($custom !== '') {
            return $custom;
        }

        if (($kind === 'content' || $kind === 'home-content') && trim((string)($item['name'] ?? '')) !== '') {
            return trim((string)$item['name']) . ' | ' . $this->siteTitle();
        }

        if ($kind === 'archive' && trim((string)($term['name'] ?? '')) !== '') {
            return trim((string)$term['name']) . ' | ' . $this->siteTitle();
        }

        if ($kind === 'search') {
            $title = t('front.search_results');
            $query = trim($query);
            return ($query !== '' ? $title . ': ' . $query : $title) . ' | ' . $this->siteTitle();
        }

        return $this->pageTitle(null);
    }

    private function resolveHeadDescription(string $kind, array $item, array $term, string $query = ''): string
    {
        if ($kind === 'content' || $kind === 'home-content') {
            $excerpt = $this->plainText((string)($item['excerpt'] ?? ''));
            if ($excerpt !== '') {
                return $excerpt;
            }

            $body = $this->plainText((string)($item['body'] ?? ''));
            if ($body !== '') {
                return $body;
            }
        }

        if ($kind === 'archive' && trim((string)($term['name'] ?? '')) !== '') {
            return trim((string)$term['name']);
        }
        if ($kind === 'search' && trim($query) !== '') {
            return 'Search results for: ' . trim($query);
        }

        $meta = $this->plainText($this->setting('meta_description'));
        return $meta !== '' ? $meta : $this->siteTitle();
    }

    private function resolveOgType(string $kind, array $item): string
    {
        if ($kind !== 'content' && $kind !== 'home-content') {
            return 'website';
        }

        $type = trim((string)($item['type'] ?? ''));
        return in_array($type, ['article', 'news_article', 'blog_posting'], true) ? 'article' : 'website';
    }

    private function jsonLd(string $kind, array $item, array $term, string $title, string $description, string $url, string $image, string $author, string $query = ''): string
    {
        if ($kind === 'content' || $kind === 'home-content') {
            $payload = [
                '@context' => 'https://schema.org',
                '@type' => $this->schemaType((string)($item['type'] ?? '')),
                'headline' => $title,
                'description' => $description,
                'mainEntityOfPage' => $url,
                'url' => $url,
            ];
            if ($image !== '') {
                $payload['image'] = $image;
            }
            $published = $this->isoDate((string)($item['created'] ?? ''));
            if ($published !== '') {
                $payload['datePublished'] = $published;
            }
            $updated = $this->isoDate((string)($item['updated'] ?? ''));
            if ($updated !== '') {
                $payload['dateModified'] = $updated;
            }
            if ($author !== '') {
                $payload['author'] = ['@type' => 'Person', 'name' => $author];
            }
            $terms = array_values(array_filter(array_map(static fn(array $entry): string => trim((string)($entry['name'] ?? '')), (array)($item['terms'] ?? []))));
            if ($terms !== []) {
                $payload['keywords'] = implode(', ', $terms);
            }

            return $this->jsonEncode($payload);
        }
        if ($kind === 'archive') {
            $payload = [
                '@context' => 'https://schema.org',
                '@type' => 'CollectionPage',
                'name' => $title,
                'description' => $description,
                'url' => $url,
                'isPartOf' => $this->absoluteUrl($this->url('')),
            ];
            if (trim((string)($term['name'] ?? '')) !== '') {
                $payload['about'] = ['@type' => 'Thing', 'name' => trim((string)$term['name'])];
            }

            return $this->jsonEncode($payload);
        }
        if ($kind === 'search') {
            return $this->jsonEncode([
                '@context' => 'https://schema.org',
                '@type' => 'SearchResultsPage',
                'name' => $title,
                'description' => $description,
                'url' => $url,
                'isPartOf' => $this->absoluteUrl($this->url('')),
                'about' => trim($query) !== '' ? trim($query) : null,
            ]);
        }

        $payload = [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => $this->siteTitle(),
            'url' => $this->absoluteUrl($this->url('')),
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => $this->absoluteUrl($this->withQuery($this->url('search'), 'q={search_term_string}')),
                'query-input' => 'required name=search_term_string',
            ],
        ];
        return $this->jsonEncode($payload);
    }

    private function plainText(string $value, int $limit = 160): string
    {
        $clean = trim(html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $clean = preg_replace('/\s+/', ' ', $clean) ?? '';
        return $limit > 0 ? mb_substr($clean, 0, $limit) : $clean;
    }

    private function timestamp(string $value): ?int
    {
        $clean = trim($value);
        if ($clean === '') {
            return null;
        }

        $formats = ['Y-m-d H:i:s', 'Y-m-d H:i', 'Y-m-d\\TH:i:s', 'Y-m-d\\TH:i'];
        foreach ($formats as $format) {
            $date = \DateTimeImmutable::createFromFormat($format, $clean);
            if ($date instanceof \DateTimeImmutable && $date->format($format) === $clean) {
                return $date->getTimestamp();
            }
        }

        $timestamp = strtotime($clean);
        return $timestamp === false ? null : $timestamp;
    }

    private function isoDate(string $value): string
    {
        $timestamp = $this->timestamp($value);
        if ($timestamp === null) {
            return '';
        }

        return gmdate('c', $timestamp);
    }

    private function schemaType(string $type): string
    {
        $normalized = trim($type);
        return match ($normalized) {
            'page' => 'WebPage',
            'about_page' => 'AboutPage',
            'news_article' => 'NewsArticle',
            'blog_posting' => 'BlogPosting',
            'faq_page' => 'FAQPage',
            default => 'Article',
        };
    }

    private function isArticleType(string $type): bool
    {
        return in_array(trim($type), ['article', 'news_article', 'blog_posting'], true);
    }

    private function absoluteUrl(string $path): string
    {
        if (preg_match('#^https?://#i', $path) === 1) {
            return $path;
        }

        if (!RequestContext::hasAuthority()) {
            return $path;
        }

        return RequestContext::scheme() . '://' . RequestContext::authority() . '/' . ltrim($path, '/');
    }

    private function currentRequestUrl(): string
    {
        $uri = (string)($_SERVER['REQUEST_URI'] ?? '/');
        $path = $this->url($this->router->requestPath($uri));
        $query = $this->publicQuery($uri);
        if ($query !== '') {
            $path = $this->withQuery($path, $query);
        }

        return $this->absoluteUrl($path);
    }

    private function publicQuery(string $uri): string
    {
        parse_str((string)(parse_url($uri, PHP_URL_QUERY) ?? ''), $query);
        unset($query['route']);
        return http_build_query($query);
    }

    private function withQuery(string $url, string $query): string
    {
        $clean = ltrim($query, '?&');
        return $clean === '' ? $url : $url . (str_contains($url, '?') ? '&' : '?') . $clean;
    }

    private function jsonEncode(array $payload): string
    {
        $payload = array_filter($payload, static fn(mixed $value): bool => $value !== null && $value !== '');
        return esc_json($payload);
    }
}
