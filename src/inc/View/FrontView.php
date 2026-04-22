<?php
declare(strict_types=1);

namespace App\View;

use App\Service\Application\Menu;
use App\Service\Front\AdminBar;
use App\Service\Front\Theme;
use App\Service\Infrastructure\Router\Router;
use App\Service\Support\I18n;

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
        $this->theme = $this->resolveTheme((string)($settings['front_theme'] ?? 'default'));
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
            'archiveLabel' => $this->themeText('front.archive_for'),
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
            'archiveLabel' => $this->themeText('front.archive_for_author'),
            'archivePath' => trim($archivePath, '/'),
        ]);
    }

    public function searchResults(array $pagination, string $query): void
    {
        $title = $this->themeText('front.search_results');
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
            'pageTitle' => $this->themeText('front.account_title'),
        ]);
    }


    public function notFound(): void
    {
        http_response_code(404);
        $this->render('404', [
            'kind' => '404',
            'pageTitle' => $this->themeText('front.not_found_title'),
        ]);
    }

    private function render(string $template, array $data): void
    {
        $layoutFile = $this->resolveThemeFile('layout.php');
        $templateFile = $this->resolveThemeFile($template . '.php');
        $theme = new Theme($this->router, $this->settings, $this->theme, $this->menu);
        $url = fn(string $path = ''): string => $theme->url($path);
        $themeUrl = fn(string $path = ''): string => $theme->themeUrl($path);
        $setting = fn(string $key, string $default = ''): string => $theme->setting($key, $default);
        $mediaUrl = fn(string $path = '', string $size = 'origin'): string => $theme->mediaUrl($path, $size);
        $mediaSrcSet = fn(string $path): string => $theme->mediaSrcSet($path);
        $contentThumbnail = fn(array $item, array $options = []): string => $theme->contentThumbnail($item, $options);
        $contentAuthor = fn(array $item, string $fallback = ''): string => $theme->contentAuthor($item, $fallback);
        $contentDate = fn(array $item, string $fallback = ''): string => $theme->contentDate($item, $fallback);
        $contentUrl = fn(array $item): string => $theme->contentUrl($item);
        $termUrl = fn(array $term): string => $theme->termUrl($term);
        $authorUrl = fn(array $item): string => $theme->authorUrl($item);
        $searchForm = fn(string $action = 'search', string $query = ''): string => $theme->searchForm($action, $query, [
            'placeholder' => t('front.search_placeholder'),
            'button' => t('front.search_button'),
        ]);
        $menuItems = fn(): array => $theme->menuItems();
        $menu = fn(array $options = []): string => $theme->menu($options);
        $lang = $this->resolvedLanguage();
        $includePartial = function (string $name, array $context = []) use ($url, $themeUrl, $setting, $lang, $mediaUrl, $mediaSrcSet, $contentThumbnail, $contentAuthor, $contentDate, $contentUrl, $termUrl, $authorUrl, $searchForm, $menuItems, $menu, $theme): void {
            $file = $this->resolveThemeFile('partials/' . $name . '.php');
            extract($context, EXTR_SKIP);
            require $file;
        };

        I18n::pushCataloguePath($this->themeLangPath());

        try {
            $pageTitle = $theme->pageTitle(isset($data['pageTitle']) ? (string)$data['pageTitle'] : null);
            $head = $theme->head($data);
            extract($data, EXTR_SKIP);

            ob_start();
            require $templateFile;
            $content = (string)ob_get_clean();

            ob_start();
            require $layoutFile;
            $output = (string)ob_get_clean();

            echo $this->adminBar->inject($output, $data);
        } finally {
            I18n::popCataloguePath();
        }
    }

    private function resolveTheme(string $theme): string
    {
        $clean = trim($theme);
        if ($clean === '') {
            return 'default';
        }

        $path = $this->themePath($clean);
        return is_dir($path) ? $clean : 'default';
    }

    private function resolveThemeFile(string $file): string
    {
        $path = $this->themePath($this->theme) . '/' . ltrim($file, '/');
        $real = realpath($path);
        $root = realpath($this->themePath($this->theme));
        $normalizedReal = $real === false ? '' : str_replace('\\', '/', $real);
        $normalizedRoot = $root === false ? '' : str_replace('\\', '/', $root);

        if ($normalizedReal === '' || $normalizedRoot === '' || !str_starts_with($normalizedReal, $normalizedRoot) || !is_file($real)) {
            http_response_code(404);
            exit('404');
        }

        return $real;
    }

    private function themePath(string $theme): string
    {
        $themeDir = trim((string)(defined('THEMES_DIR') ? THEMES_DIR : 'themes/'), '/');
        return $this->rootPath . '/' . $themeDir . '/' . trim($theme, '/');
    }

    private function themeLangPath(): string
    {
        return $this->themePath($this->theme) . '/lang';
    }

    private function themeText(string $key, ?string $fallback = null): string
    {
        I18n::pushCataloguePath($this->themeLangPath());
        try {
            return t($key, $fallback);
        } finally {
            I18n::popCataloguePath();
        }
    }

    private function resolvedLanguage(): string
    {
        $lang = trim((string)($this->settings['app_lang'] ?? 'en'));
        return $lang !== '' ? $lang : 'en';
    }
}
