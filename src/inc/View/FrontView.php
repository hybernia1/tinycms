<?php
declare(strict_types=1);

namespace App\View;

use App\Service\Front\AdminBar;
use App\Service\Front\Theme;
use App\Service\Infrastructure\Router\Router;

final class FrontView
{
    private string $rootPath;
    private Router $router;
    private array $settings;
    private string $theme;

    public function __construct(string $rootPath, Router $router, array $settings, private AdminBar $adminBar)
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

    public function termArchive(array $term, array $pagination): void
    {
        $this->render('archive', [
            'kind' => 'archive',
            'term' => $term,
            'pagination' => $pagination,
        ]);
    }

    public function searchResults(array $pagination, string $query): void
    {
        $title = $this->translate('front.search_results');
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
            'pageTitle' => $this->translate('front.account_title'),
        ]);
    }

    private function render(string $template, array $data): void
    {
        $layoutFile = $this->resolveThemeFile('layout.php');
        $templateFile = $this->resolveThemeFile($template . '.php');
        $theme = new Theme($this->router, $this->settings, $this->theme);
        $url = fn(string $path = ''): string => $theme->url($path);
        $themeUrl = fn(string $path = ''): string => $theme->themeUrl($path);
        $e = static fn(mixed $value): string => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
        $setting = fn(string $key, string $default = ''): string => $theme->setting($key, $default);
        $t = fn(string $key, ?string $fallback = null): string => $this->translate($key, $fallback);
        $mediaUrl = fn(string $path = '', string $size = 'origin'): string => $theme->mediaUrl($path, $size);
        $mediaSrcSet = fn(string $path): string => $theme->mediaSrcSet($path);
        $contentThumbnail = fn(array $item, array $options = []): string => $theme->contentThumbnail($item, $options);
        $contentAuthor = fn(array $item, string $fallback = ''): string => $theme->contentAuthor($item, $fallback);
        $contentDate = fn(array $item, string $fallback = ''): string => $theme->contentDate($item, $fallback);
        $contentUrl = fn(array $item): string => $theme->contentUrl($item);
        $termUrl = fn(array $term): string => $theme->termUrl($term);
        $searchForm = fn(string $action = 'search', string $query = ''): string => $theme->searchForm($action, $query, [
            'placeholder' => $this->translate('front.search_placeholder'),
            'button' => $this->translate('front.search_button'),
        ]);
        $icon = static function (string $name, string $classes = 'icon') use ($e, $themeUrl): string {
            $sprite = $e($themeUrl('assets/svg/icons.svg#icon-' . trim($name)));
            $class = trim($classes);
            return '<svg class="' . $e($class !== '' ? $class : 'icon') . '" aria-hidden="true"><use href="' . $sprite . '"></use></svg>';
        };
        $lang = $this->resolvedLanguage();
        $includePartial = function (string $name, array $context = []) use ($e, $url, $themeUrl, $setting, $t, $lang, $mediaUrl, $mediaSrcSet, $contentThumbnail, $contentAuthor, $contentDate, $contentUrl, $termUrl, $searchForm, $theme, $icon): void {
            $file = $this->resolveThemeFile('partials/' . $name . '.php');
            extract($context, EXTR_SKIP);
            require $file;
        };

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
        $root = rtrim($this->themePath($this->theme), '/');

        if ($real === false || !str_starts_with($real, $root) || !is_file($real)) {
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

    private function translate(string $key, ?string $fallback = null): string
    {
        static $cache = [];

        $lang = $this->resolvedLanguage();
        if (!isset($cache[$lang])) {
            $cache[$lang] = $this->loadLang($lang);
        }
        if (!isset($cache['en'])) {
            $cache['en'] = $this->loadLang('en');
        }

        return (string)($cache[$lang][$key] ?? $cache['en'][$key] ?? $fallback ?? $key);
    }

    private function loadLang(string $lang): array
    {
        $file = $this->themePath($this->theme) . '/lang/' . $lang . '.php';
        if (!is_file($file)) {
            return [];
        }

        $payload = require $file;
        return is_array($payload) ? $payload : [];
    }

    private function resolvedLanguage(): string
    {
        $lang = trim((string)($this->settings['app_lang'] ?? 'en'));
        return $lang !== '' ? $lang : 'en';
    }
}
