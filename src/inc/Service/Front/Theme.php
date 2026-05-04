<?php
declare(strict_types=1);

namespace App\Service\Front;

use App\Service\Application\Menu;
use App\Service\Application\Comment;
use App\Service\Application\Widget;
use App\Service\Auth\Auth;
use App\Service\Infrastructure\Router\Router;
use App\Service\Support\Date;
use App\Service\Support\Csrf;
use App\Service\Support\I18n;
use App\Service\Support\Media;
use App\Service\Support\RequestContext;
use App\Service\Support\Slugger;
use App\Service\Support\Shortcode;

final class Theme
{
    private static ?self $current = null;
    private string $theme;
    private Slugger $slugger;
    private array $context = [];
    private array $commentCountCache = [];
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

    public function brand(): array
    {
        $display = $this->brandDisplay();
        $logo = in_array($display, ['both', 'logo'], true) ? trim($this->setting('logo')) : '';
        $title = in_array($display, ['both', 'title'], true) ? $this->siteTitle() : '';

        return [
            'logo' => $logo,
            'title' => $title,
        ];
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
        $tags[] = '<link rel="stylesheet" href="' . esc_url($this->themeUrl('assets/css/style.css')) . '">';
        $tags[] = '<link rel="stylesheet" href="' . esc_url($this->url(ASSETS_DIR . 'css/block.css')) . '">';
        if ($this->customizerPreview()) {
            $tags[] = '<link rel="stylesheet" href="' . esc_url($this->url(ASSETS_DIR . 'css/customizer-preview.css')) . '">';
            $tags[] = '<script defer src="' . esc_url($this->url(ASSETS_DIR . 'js/front/theme-customizer-preview.js')) . '"></script>';
        }
        $themeVariables = $this->themeVariablesCss();
        if ($themeVariables !== '') {
            $tags[] = '<style data-theme-variables>' . $themeVariables . '</style>';
        }
        $customCss = $this->customCss();
        if ($customCss !== '') {
            $tags[] = '<style data-theme-custom-css>' . $customCss . '</style>';
        }
        $tags[] = '<script defer src="' . esc_url($this->url(ASSETS_DIR . 'js/front/code-copy.js')) . '"></script>';
        if ($this->commentsEnabled($item)) {
            $tags[] = '<script defer src="' . esc_url($this->url(ASSETS_DIR . 'js/front/comment-actions.js')) . '"></script>';
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
            return '';
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
        if (!$this->menuEnabled()) {
            return '';
        }

        $items = $this->menuItems();
        if ($items === []) {
            return '';
        }

        $class = trim((string)($options['class'] ?? 'site-menu'));
        $itemClass = trim((string)($options['item_class'] ?? 'site-menu-link'));
        $label = trim((string)($options['label'] ?? 'Menu'));
        $showIcons = (bool)($options['show_icons'] ?? true);
        $reserveIconSpace = (bool)($options['reserve_icon_space'] ?? false);
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
            if ($content === '' && $showIcons && $reserveIconSpace) {
                $content = '<span class="menu-icon-placeholder" aria-hidden="true"></span>';
            }
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
        if (!$this->widgetAreaEnabled($area)) {
            return '';
        }

        return $this->widgets->renderArea($area, $this->customizerPreview());
    }

    public function layoutClass(): string
    {
        return 'theme-layout-' . $this->layoutWidth();
    }

    private function brandDisplay(): string
    {
        $display = $this->setting('brand_display', 'both');
        return in_array($display, ['both', 'logo', 'title', 'none'], true) ? $display : 'both';
    }

    private function searchEnabled(): bool
    {
        return $this->setting('enable_search', '1') === '1';
    }

    private function menuEnabled(): bool
    {
        return $this->setting('enable_menu', '1') === '1';
    }

    private function widgetAreaEnabled(string $area): bool
    {
        $area = strtolower(trim($area));
        if ($area === '' || preg_match('/^[a-z0-9_-]{1,100}$/', $area) !== 1) {
            return false;
        }

        $value = strtolower(trim($this->setting('enabled_widget_areas', '*')));
        if ($value === '*') {
            return true;
        }

        foreach (explode(',', $value) as $enabledArea) {
            if (trim($enabledArea) === $area) {
                return true;
            }
        }

        return false;
    }

    private function layoutWidth(): string
    {
        $width = $this->setting('layout_width', 'default');
        return in_array($width, ['narrow', 'default', 'wide', 'full'], true) ? $width : 'default';
    }

    public function customCss(): string
    {
        $css = str_replace("\0", '', trim($this->setting('custom_css')));
        return str_ireplace('</style', '<\/style', $css);
    }

    private function themeVariablesCss(): string
    {
        $variables = [];
        foreach ($this->settings as $key => $value) {
            $key = trim((string)$key);
            if (!str_starts_with($key, 'color_')) {
                continue;
            }

            $color = $this->cssColor((string)$value);
            if ($color !== '') {
                $variables[] = '--' . str_replace('_', '-', substr($key, 6)) . ': ' . $color;
            }
        }

        return $variables !== [] ? ':root{' . implode(';', $variables) . ';}' : '';
    }

    private function cssColor(string $value): string
    {
        $value = strtolower(trim($value));
        if ($value === 'transparent') {
            return $value;
        }

        return preg_match('/^#[0-9a-f]{6}$/', $value) === 1 ? $value : '';
    }

    private function customizerPreview(): bool
    {
        return trim((string)($_GET['theme_preview'] ?? '')) !== '' && $this->auth->isAdmin();
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

        $items = $this->comments->treeForContent((int)$item['id'], $this->pendingCommentIds((int)$item['id']));
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

    public function commentsCount(array $item): int
    {
        $contentId = (int)($item['id'] ?? 0);
        if ($contentId <= 0) {
            return 0;
        }

        if (!array_key_exists($contentId, $this->commentCountCache)) {
            $this->commentCountCache[$contentId] = $this->comments->countForContent($contentId);
        }

        return $this->commentCountCache[$contentId];
    }

    public function commentsForm(array $item, ?int $parentId = null, ?int $replyToId = null): string
    {
        if (!$this->commentsEnabled($item)) {
            return '';
        }

        if (!$this->auth->check() && !$this->commentsAllowAnonymous()) {
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
        $authorFields = !$this->auth->check() ? sprintf(
            '<div class="comment-form-fields"><label><span>%s</span><input type="text" name="author_name" autocomplete="name" required></label><label><span>%s</span><input type="email" name="author_email" autocomplete="email"></label></div>',
            esc_html(t('common.name', 'Name')),
            esc_html(t('common.email', 'Email')),
        ) : '';

        return sprintf(
            '<form class="%s"%s action="%s" method="post">%s<input type="hidden" name="parent" value="%d"><input type="hidden" name="reply_to" value="%d"><input type="hidden" name="return" value="%s"><h3>%s</h3>%s<textarea name="body" rows="4" required></textarea><button type="submit">%s</button></form>',
            $parentId > 0 ? 'comment-form comment-reply-form' : 'comment-form',
            $formId,
            esc_url($this->formAction($action)),
            $this->hiddenRouteField($action) . $this->csrf->field(),
            $parentId,
            $replyToId,
            esc_attr($this->commentReturnPath()),
            esc_html($heading),
            $authorFields,
            esc_html(t('front.comments_submit', 'Send comment')),
        );
    }

    public function contentDate(array $item): string
    {
        $raw = trim((string)($item['created'] ?? ''));
        if ($raw === '') {
            return '';
        }

        $timestamp = $this->timestamp($raw);
        if ($timestamp === null) {
            return '';
        }

        $format = Date::normalizeDateTimeFormat($this->setting('app_datetime_format', APP_DATETIME_FORMAT));
        return date($format, $timestamp);
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

    public function searchForm(string $action = 'search', string $query = '', array $labels = [], bool $respectThemeSetting = true): string
    {
        if ($respectThemeSetting && !$this->searchEnabled()) {
            return '';
        }

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

    private function commentItem(array $item, array $comment, bool $allowReply, int $threadParentId = 0): string
    {
        $author = get_author($comment, t('front.comments_no_author', 'No author'));

        $commentId = (int)$comment['id'];
        $isPending = (bool)($comment['is_pending'] ?? false);
        $threadParentId = $threadParentId > 0 ? $threadParentId : $commentId;
        $comment['author_name'] = $author;
        $html = '<li class="comment-item" id="comment-' . $commentId . '">';
        $html .= '<article class="comment-card' . ($isPending ? ' comment-card-pending' : '') . '">';
        $html .= '<header class="comment-meta"><span class="comment-author">' . get_avatar($comment, 'comment-avatar', 48) . $this->commentAuthorName($comment, $author) . '</span><a href="#comment-' . $commentId . '"><time datetime="' . esc_attr($this->isoDate((string)($comment['created'] ?? ''))) . '">' . esc_html($this->commentDate((string)($comment['created'] ?? ''))) . '</time></a></header>';
        if ($isPending) {
            $html .= '<p class="comment-pending-notice">' . esc_html(t('front.comments_pending', 'Your comment is waiting for approval.')) . '</p>';
        }
        if ((int)($comment['reply_to'] ?? 0) > 0) {
            $replyAuthor = trim((string)($comment['reply_to_author_name'] ?? ''));
            if ($replyAuthor === '') {
                $replyAuthor = '#' . (int)$comment['reply_to'];
            }
            $html .= '<p class="comment-reply-context"><a href="#comment-' . (int)$comment['reply_to'] . '">' . esc_html(sprintf(t('front.comments_replying_to', 'Replying to %s'), $replyAuthor)) . '</a></p>';
        }
        $html .= '<div class="comment-body">' . esc_content($comment['body'] ?? '') . '</div>';
        $replyButton = '';
        $replyForm = '';
        if (!$isPending && $allowReply && ($this->auth->check() || $this->commentsAllowAnonymous())) {
            $replyButton = '<button class="comment-reply" type="button" data-comment-reply data-comment-reply-target="comment-reply-form-' . $commentId . '" aria-controls="comment-reply-form-' . $commentId . '" aria-expanded="false">' . esc_html(t('front.comments_reply_title', 'Reply')) . '</button>';
            $replyForm = $this->commentsForm($item, $threadParentId, $commentId !== $threadParentId ? $commentId : null);
        }

        $adminActions = $this->commentAdminLink($commentId, $threadParentId);
        if ($replyButton !== '' || $adminActions !== '') {
            $html .= '<div class="comment-actions">' . $replyButton . $adminActions . '</div>' . $replyForm;
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

    private function commentAdminLink(int $commentId, int $threadParentId): string
    {
        if (!$this->auth->isAdmin()) {
            return '';
        }
        if ($commentId <= 0) {
            return '';
        }

        $targetId = $threadParentId > 0 ? $threadParentId : $commentId;
        $fragment = $targetId !== $commentId ? '#comments-child-form-' . $commentId : '#comments-form';
        $label = esc_attr(t('front.comments_edit', 'Edit'));

        return sprintf(
            '<a class="comment-admin-action" href="%s" target="_blank" rel="noopener noreferrer" aria-label="%s" title="%s">%s</a>',
            esc_url($this->url('admin/comments/edit?id=' . $targetId) . $fragment),
            $label,
            $label,
            icon('concept'),
        );
    }

    private function commentsAllowAnonymous(): bool
    {
        return $this->setting('comments_allow_anonymous', '0') === '1';
    }

    private function commentAuthorName(array $comment, string $author): string
    {
        $authorId = (int)($comment['author'] ?? 0);
        if ($authorId <= 0) {
            return '<strong>' . esc_html($author) . '</strong>';
        }

        $url = $this->authorUrl([
            'author' => $authorId,
            'author_name' => $author,
        ]);

        return $url !== ''
            ? '<a class="comment-author-link" href="' . esc_url($url) . '"><strong>' . esc_html($author) . '</strong></a>'
            : '<strong>' . esc_html($author) . '</strong>';
    }

    private function pendingCommentIds(int $contentId): array
    {
        if ($contentId <= 0 || session_status() !== PHP_SESSION_ACTIVE) {
            return [];
        }

        return array_values(array_unique(array_filter(
            array_map(static fn(mixed $id): int => (int)$id, (array)($_SESSION['pending_comments'][(string)$contentId] ?? [])),
            static fn(int $id): bool => $id > 0
        )));
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

    private function commentReturnPath(?int $commentId = null): string
    {
        $uri = (string)($_SERVER['REQUEST_URI'] ?? '/');
        $path = strtok($uri, '#');
        $fragment = $commentId !== null && $commentId > 0 ? '#comment-' . $commentId : '#comments';
        return ($path !== false && $path !== '' ? $path : '/') . $fragment;
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

        return $this->router->requestPath($this->url($value));
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

        return $this->siteTitle();
    }

    private function resolveHeadDescription(string $kind, array $item, array $term, string $query = ''): string
    {
        if ($kind === 'content' || $kind === 'home-content') {
            $excerpt = $this->plainText(Shortcode::render((string)($item['excerpt'] ?? '')));
            if ($excerpt !== '') {
                return $excerpt;
            }

            $body = $this->plainText(Shortcode::render((string)($item['body'] ?? '')));
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
        ];
        if ($this->searchEnabled()) {
            $payload['potentialAction'] = [
                '@type' => 'SearchAction',
                'target' => $this->absoluteUrl($this->withQuery($this->url('search'), 'q={search_term_string}')),
                'query-input' => 'required name=search_term_string',
            ];
        }

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
