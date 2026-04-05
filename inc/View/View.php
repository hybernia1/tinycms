<?php
declare(strict_types=1);

namespace App\View;

use App\Service\Support\FlashService;
use App\Service\Infra\Router\Router;
use App\Service\Support\CsrfService;
use App\Service\Support\DateTimeFormatter;
use App\Service\Support\I18n;

final class View
{
    private string $rootPath;
    private Router $router;
    private FlashService $flash;
    private CsrfService $csrf;
    private DateTimeFormatter $dateTimeFormatter;
    private MetaHead $metaHead;

    public function __construct(string $rootPath, Router $router, FlashService $flash, CsrfService $csrf, DateTimeFormatter $dateTimeFormatter)
    {
        $this->rootPath = rtrim($rootPath, '/');
        $this->router = $router;
        $this->flash = $flash;
        $this->csrf = $csrf;
        $this->dateTimeFormatter = $dateTimeFormatter;
        $this->metaHead = new MetaHead();
    }

    public function render(string $layout, string $template, array $data = []): void
    {
        $this->renderFiles(
            $this->resolve('view/' . $template . '.php', '/view/'),
            $this->resolve('view/' . $layout . '.php', '/view/'),
            $layout,
            $data
        );
    }

    public function renderTheme(string $theme, string $template, array $data = []): void
    {
        $base = 'themes/' . trim($theme, '/');
        $this->renderFiles(
            $this->resolve($base . '/' . $template . '.php', '/themes/'),
            $this->resolve($base . '/layout.php', '/themes/'),
            'theme/' . $theme,
            $data
        );
    }

    private function renderFiles(string $templateFile, string $layoutFile, string $layout, array $data = []): void
    {
        $url = fn(string $path = ''): string => $this->router->url($path);
        $icon = static function (string $name, string $classes = 'icon') use ($url): string {
            $sprite = htmlspecialchars($url('assets/icons.svg#icon-' . $name), ENT_QUOTES, 'UTF-8');
            $classAttr = htmlspecialchars($classes, ENT_QUOTES, 'UTF-8');
            return '<svg class="' . $classAttr . '" aria-hidden="true" focusable="false"><use href="' . $sprite . '"></use></svg>';
        };
        $csrfField = fn(string $name = '_csrf'): string => $this->csrf->field($name);
        $formatDate = fn(?string $value, string $fallback = ''): string => $this->dateTimeFormatter->formatDate($value, $fallback);
        $formatDateTime = fn(?string $value, string $fallback = ''): string => $this->dateTimeFormatter->formatDateTime($value, $fallback);
        $formatInputDateTime = fn(?string $value, string $fallback = ''): string => $this->dateTimeFormatter->toInputDateTimeLocal($value, $fallback);
        $t = static fn(string $key, ?string $fallback = null): string => I18n::t($key, $fallback);
        $renderFrontHead = fn(array $options = []): string => $this->metaHead->render($options);

        $isAdminLayout = str_starts_with($layout, 'admin/');

        if ($isAdminLayout && !isset($data['adminMenu'])) {
            $data['adminMenu'] = [
                ['label' => I18n::t('admin.menu.dashboard', 'Dashboard'), 'url' => $url('admin/dashboard')],
                ['label' => I18n::t('admin.menu.users', 'Users'), 'url' => $url('admin/users')],
                ['label' => I18n::t('admin.menu.settings', 'Settings'), 'url' => $url('admin/settings')],
            ];
        } elseif ($isAdminLayout && isset($data['adminMenu']) && is_array($data['adminMenu'])) {
            $data['adminMenu'] = array_map(static function (array $item) use ($url): array {
                $path = (string)($item['url'] ?? '');
                return [
                    'label' => (string)($item['label'] ?? ''),
                    'url' => str_starts_with($path, 'http') ? $path : $url($path),
                ];
            }, $data['adminMenu']);
        }

        $data['pageTitle'] = $data['pageTitle'] ?? 'Admin';
        $data['icon'] = $icon;
        $data['csrfField'] = $csrfField;
        $data['formatDate'] = $formatDate;
        $data['formatDateTime'] = $formatDateTime;
        $data['formatInputDateTime'] = $formatInputDateTime;
        $data['t'] = $t;
        $data['renderFrontHead'] = $renderFrontHead;
        $data['lang'] = I18n::htmlLang();
        $data['flashes'] = $this->flash->consume();
        extract($data, EXTR_SKIP);

        ob_start();
        require $templateFile;
        $content = (string)ob_get_clean();

        require $layoutFile;
    }

    private function resolve(string $relativePath, string $allowedPath): string
    {
        $fullPath = $this->rootPath . '/' . ltrim($relativePath, '/');
        $realPath = realpath($fullPath);

        if ($realPath === false || !str_starts_with($realPath, $this->rootPath . $allowedPath) || !is_file($realPath)) {
            http_response_code(404);
            exit('404');
        }

        return $realPath;
    }
}
