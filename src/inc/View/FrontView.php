<?php
declare(strict_types=1);

namespace App\View;

use App\Service\Application\Menu;
use App\Service\Application\Comment;
use App\Service\Application\ContentStats;
use App\Service\Application\Widget;
use App\Service\Auth\Auth;
use App\Service\Front\AdminBar;
use App\Service\Front\Theme;
use App\Service\Infrastructure\Router\Router;
use App\Service\Support\Csrf;
use App\Service\Support\I18n;

final class FrontView
{
    private string $rootPath;
    private Router $router;
    private array $settings;
    private string $theme;

    public function __construct(
        string $rootPath,
        Router $router,
        array $settings,
        private AdminBar $adminBar,
        private Menu $menu,
        private Widget $widgets,
        private Comment $comments,
        private ContentStats $contentStats,
        private Auth $auth,
        private Csrf $csrf
    ) {
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
        $query = trim($query);
        $this->render('search', [
            'kind' => 'search',
            'pagination' => $pagination,
            'query' => $query,
            'paginationPath' => $query !== '' ? 'search?q=' . rawurlencode($query) : 'search',
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

    public function commentsFragment(array $item, int $page, string $sort = 'relevant'): array
    {
        $theme = $this->createTheme();
        Theme::setCurrent($theme);
        I18n::pushCataloguePath($this->themeLangPath());

        try {
            return $theme->commentsFragment($item, $page, $sort);
        } finally {
            I18n::popCataloguePath();
            Theme::setCurrent(null);
        }
    }

    public function commentRepliesFragment(array $item, int $parentId, int $page): array
    {
        $theme = $this->createTheme();
        Theme::setCurrent($theme);
        I18n::pushCataloguePath($this->themeLangPath());

        try {
            return $theme->commentRepliesFragment($item, $parentId, $page);
        } finally {
            I18n::popCataloguePath();
            Theme::setCurrent(null);
        }
    }

    private function render(string $template, array $data): void
    {
        if (is_array($data['pagination'] ?? null)) {
            $data['items'] = (array)($data['pagination']['data'] ?? []);
            $data['paginationPath'] = (string)($data['paginationPath'] ?? $data['archivePath'] ?? '');
        }

        $layoutFile = $this->resolveThemeFile('layout.php');
        $templateFile = $this->resolveThemeFile($template . '.php');
        $theme = $this->createTheme();
        $theme->setIncludeThemeFile(function (string $name, array $context = []): void {
            $file = $this->resolveThemeFile($this->partialPath($name));
            extract($context, EXTR_SKIP);
            require $file;
        });

        Theme::setCurrent($theme);
        I18n::pushCataloguePath($this->themeLangPath());

        try {
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

    private function createTheme(): Theme
    {
        return new Theme($this->router, $this->settings, $this->theme, $this->menu, $this->widgets, $this->comments, $this->contentStats, $this->auth, $this->csrf);
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

    private function partialPath(string $name): string
    {
        return 'partials/' . trim($name, '/\\') . '.php';
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

}
