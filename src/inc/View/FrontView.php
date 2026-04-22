<?php
declare(strict_types=1);

namespace App\View;

use App\Service\Application\Menu;
use App\Service\Front\AdminBar;
use App\Service\Infrastructure\Router\Router;

final class FrontView
{
    private string $rootPath;
    private Router $router;
    private array $settings;
    private string $theme;

    public function __construct(string $rootPath, Router $router, array $settings, private AdminBar $adminBar, private Menu $menu)
    {
        $this->rootPath = rtrim($rootPath, '/');
        $this->router = $router;
        $this->settings = $settings;
        $this->theme = themes_resolve_name($this->rootPath, (string)($settings['front_theme'] ?? 'default'));
    }

    public function homeLoop(array $pagination): void
    {
        $this->render('index', [
            'kind' => 'home',
            'mode' => 'loop',
            'pagination' => $pagination,
        ]);
    }

    public function homeContent(array $item): void
    {
        $this->render('index', [
            'kind' => 'home-content',
            'mode' => 'content',
            'item' => $item,
        ]);
    }

    public function singleContent(array $item): void
    {
        $this->render('content', [
            'kind' => 'content',
            'item' => $item,
        ]);
    }

    public function termArchive(array $term, array $pagination, string $archivePath): void
    {
        $this->render('archive', [
            'kind' => 'archive',
            'term' => $term,
            'pagination' => $pagination,
            'archiveLabel' => themes_translate($this->rootPath, $this->theme, $this->settings, 'front.archive_for'),
            'archivePath' => trim($archivePath, '/'),
        ]);
    }

    public function authorArchive(array $author, array $pagination, string $archivePath): void
    {
        $this->render('archive', [
            'kind' => 'archive',
            'term' => $author,
            'user' => $author,
            'pagination' => $pagination,
            'archiveLabel' => themes_translate($this->rootPath, $this->theme, $this->settings, 'front.archive_for_author'),
            'archivePath' => trim($archivePath, '/'),
        ]);
    }

    public function searchResults(array $pagination, string $query): void
    {
        $title = themes_translate($this->rootPath, $this->theme, $this->settings, 'front.search_results');
        if (trim($query) !== '') {
            $title .= ': ' . $query;
        }

        $this->render('search', [
            'kind' => 'search',
            'pagination' => $pagination,
            'query' => $query,
            'pageTitle' => $title,
        ]);
    }

    public function account(array $user): void
    {
        $this->render('account', [
            'kind' => 'account',
            'user' => $user,
            'pageTitle' => themes_translate($this->rootPath, $this->theme, $this->settings, 'front.account_title'),
        ]);
    }


    public function notFound(): void
    {
        http_response_code(404);
        $this->render('404', [
            'kind' => '404',
            'pageTitle' => themes_translate($this->rootPath, $this->theme, $this->settings, 'front.not_found_title'),
        ]);
    }

    private function render(string $template, array $data): void
    {
        $layoutFile = themes_resolve_file($this->rootPath, $this->theme, 'layout.php');
        $templateFile = themes_resolve_file($this->rootPath, $this->theme, $template . '.php');
        $themeCtx = themes_init($this->router, $this->settings, $this->theme, $this->menu);
        $url = fn(string $path = ''): string => themes_url($themeCtx, $path);
        $themeUrl = fn(string $path = ''): string => themes_theme_url($themeCtx, $path);
        $e = static fn(mixed $value): string => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
        $setting = fn(string $key, string $default = ''): string => themes_setting($themeCtx, $key, $default);
        $t = fn(string $key, ?string $fallback = null): string => themes_translate($this->rootPath, $this->theme, $this->settings, $key, $fallback);
        $mediaUrl = fn(string $path = '', string $size = 'origin'): string => themes_media_url($themeCtx, $path, $size);
        $mediaSrcSet = fn(string $path): string => themes_media_srcset($themeCtx, $path);
        $contentThumbnail = fn(array $item, array $options = []): string => themes_content_thumbnail($themeCtx, $item, $options);
        $contentAuthor = fn(array $item, string $fallback = ''): string => themes_content_author($item, $fallback);
        $contentDate = fn(array $item, string $fallback = ''): string => themes_content_date($themeCtx, $item, $fallback);
        $contentUrl = fn(array $item): string => themes_content_url($themeCtx, $item);
        $termUrl = fn(array $term): string => themes_term_url($themeCtx, $term);
        $authorUrl = fn(array $item): string => themes_author_url($themeCtx, $item);
        $searchForm = fn(string $action = 'search', string $query = ''): string => themes_search_form($themeCtx, $action, $query, [
            'placeholder' => themes_translate($this->rootPath, $this->theme, $this->settings, 'front.search_placeholder'),
            'button' => themes_translate($this->rootPath, $this->theme, $this->settings, 'front.search_button'),
        ]);
        $menuItems = fn(): array => themes_menu_items($themeCtx);
        $menu = fn(array $options = []): string => themes_menu($themeCtx, $options);
        $icon = static function (string $name, string $classes = 'icon') use ($e, $url): string {
            $sprite = $e($url(ASSETS_DIR . 'svg/icons.svg#icon-' . trim($name)));
            $class = trim($classes);
            return '<svg class="' . $e($class !== '' ? $class : 'icon') . '" aria-hidden="true"><use href="' . $sprite . '"></use></svg>';
        };
        $lang = themes_resolved_language($this->settings);
        $siteTitle = fn(): string => themes_site_title($themeCtx);
        $siteLogo = fn(): string => themes_site_logo($themeCtx);
        $renderPagination = fn(array $paginationData, string $basePath = '', array $labels = []): string => themes_pagination($themeCtx, $paginationData, $basePath, $labels);
        $includePartial = function (string $name, array $context = []) use ($e, $url, $themeUrl, $setting, $t, $lang, $mediaUrl, $mediaSrcSet, $contentThumbnail, $contentAuthor, $contentDate, $contentUrl, $termUrl, $authorUrl, $searchForm, $menuItems, $menu, $icon, $siteTitle, $siteLogo, $renderPagination): void {
            $file = themes_resolve_file($this->rootPath, $this->theme, 'partials/' . $name . '.php');
            extract($context, EXTR_SKIP);
            require $file;
        };

        $pageTitle = themes_page_title($themeCtx, isset($data['pageTitle']) ? (string)$data['pageTitle'] : null);
        $head = themes_head($themeCtx, $data);
        extract($data, EXTR_SKIP);

        ob_start();
        require $templateFile;
        $content = (string)ob_get_clean();

        ob_start();
        require $layoutFile;
        $output = (string)ob_get_clean();

        echo $this->adminBar->inject($output, $data);
    }
}
