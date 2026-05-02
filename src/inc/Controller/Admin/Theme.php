<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\Application\Auth;
use App\Service\Application\Menu as MenuService;
use App\Service\Application\Theme as ThemeService;
use App\Service\Application\Widget as WidgetService;
use App\Service\Support\Csrf;
use App\Service\Support\Flash;
use App\Service\Support\RequestContext;
use App\View\AdminView;

final class Theme extends Admin
{
    public function __construct(
        private AdminView $pages,
        Auth $authService,
        private ThemeService $themes,
        private MenuService $menu,
        private WidgetService $widgets,
        Flash $flash,
        Csrf $csrf
    ) {
        parent::__construct($authService, $flash, $csrf);
    }

    public function form(callable $redirect): void
    {
        if (!$this->guardAdmin($redirect, false)) {
            return;
        }

        $this->pages->adminThemeForm(
            $this->themes->themes(),
            $this->themes->active()
        );
    }

    public function customizer(callable $redirect): void
    {
        if (!$this->guardAdmin($redirect, false)) {
            return;
        }

        $themes = $this->themes->themes();
        $activeTheme = $this->themes->active();

        $this->pages->adminThemeCustomizer(
            $activeTheme,
            (string)($themes[$activeTheme]['name'] ?? $activeTheme),
            $this->themes->resolved(),
            $this->themes->fields(),
            $this->themes->customizerSections(),
            $this->menu->items(),
            $this->menu->icons(),
            $this->widgets->items(),
            $this->widgets->definitions(),
            $this->widgets->areas(),
            $this->widgets->areaLabels(),
            $this->customizerPreviewUrl()
        );
    }

    private function customizerPreviewUrl(): string
    {
        $value = trim((string)($_GET['url'] ?? ''));
        if ($value === '') {
            return '';
        }

        $parts = parse_url($value);
        if (!is_array($parts)) {
            return '';
        }

        $scheme = strtolower((string)($parts['scheme'] ?? ''));
        if ($scheme !== '' && !in_array($scheme, ['http', 'https'], true)) {
            return '';
        }

        $host = trim((string)($parts['host'] ?? ''));
        $port = isset($parts['port']) ? ':' . (int)$parts['port'] : '';
        $authority = $host . $port;
        if ($authority !== '' && (!RequestContext::hasAuthority() || strcasecmp($authority, RequestContext::authority()) !== 0)) {
            return '';
        }

        $path = '/' . ltrim((string)($parts['path'] ?? ''), '/');
        if ($path === '/customizer' || str_starts_with($path, '/admin/') || str_starts_with($path, '/auth/')) {
            return '';
        }

        parse_str((string)($parts['query'] ?? ''), $query);
        unset($query['theme_preview'], $query['theme']);

        $queryString = http_build_query($query);
        $fragment = trim((string)($parts['fragment'] ?? ''));
        $relativeUrl = $path . ($queryString !== '' ? '?' . $queryString : '') . ($fragment !== '' ? '#' . $fragment : '');

        if (!RequestContext::hasAuthority()) {
            return $relativeUrl;
        }

        return RequestContext::scheme() . '://' . RequestContext::authority() . $relativeUrl;
    }
}
