<?php
declare(strict_types=1);

namespace App\View;

use App\Service\Application\Menu;
use App\Service\Application\Widgets as WidgetSettings;
use App\Service\Front\AdminBar;
use App\Service\Front\Theme;
use App\Service\Front\WidgetExtensions;
use App\Service\Infrastructure\Router\Router;
use App\Service\Support\ExtensionPaths;
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
        $this->render('search', [
            'kind' => 'search',
            'pagination' => $pagination,
            'query' => $query,
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
        $includePartial = function (string $name, array $context = []): void {
            $file = $this->resolveThemeFile('partials/' . $name . '.php');
            extract($context, EXTR_SKIP);
            require $file;
        };

        Theme::setCurrent($theme);
        I18n::pushCataloguePath($this->themeLangPath());

        try {
            WidgetExtensions::load($this->rootPath);
            $this->loadThemeFunctions();
            do_action('theme_loaded', $this->theme, $theme);
            (new WidgetSettings())->apply();
            $theme->setContext($data);
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
            Theme::setCurrent(null);
        }
    }

    private function resolveTheme(string $theme): string
    {
        $clean = trim($theme, '/\\');
        if ($clean === '' || preg_match('/^[a-z0-9_-]+$/i', $clean) !== 1) {
            return 'default';
        }

        return $this->themeExists($clean) ? $clean : 'default';
    }

    private function resolveThemeFile(string $file): string
    {
        $themePath = $this->themePath($this->theme);
        $real = ExtensionPaths::safeFile($themePath . '/' . ltrim($file, '/'), $themePath);

        if ($real === '') {
            http_response_code(404);
            exit('404');
        }

        return $real;
    }

    private function loadThemeFunctions(): void
    {
        $themePath = $this->themePath($this->theme);
        $real = ExtensionPaths::safeFile($themePath . '/functions.php', $themePath);

        if ($real === '') {
            return;
        }

        require_once $real;
    }

    private function themePath(string $theme): string
    {
        return ExtensionPaths::themePath($this->rootPath, $theme);
    }

    private function themeExists(string $theme): bool
    {
        $path = $this->themePath($theme);
        return is_dir($path) && ExtensionPaths::safeFile($path . '/layout.php', $path) !== '';
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
}
