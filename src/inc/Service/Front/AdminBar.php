<?php
declare(strict_types=1);

namespace App\Service\Front;

use App\Service\Auth\Auth;
use App\Service\Infrastructure\Router\Router;
use App\Service\Support\Escape;
use App\Service\Support\I18n;

final class AdminBar
{
    public function __construct(private Router $router, private Auth $auth)
    {
    }

    public function inject(string $output, array $context = []): string
    {
        if (!$this->auth->isAdmin()) {
            return $output;
        }

        $assetHref = $this->esc($this->router->url(ASSETS_DIR . 'css/admin-bar.css'));
        $injection = '<link rel="stylesheet" href="' . $assetHref . '">' . $this->html($context);

        if (str_contains($output, '</body>')) {
            return str_replace('</body>', $injection . '</body>', $output);
        }

        return $output . $injection;
    }

    private function html(array $context): string
    {
        $dashboard = $this->esc($this->router->url('admin/dashboard'));
        $newContent = $this->esc($this->router->url('admin/content/add'));
        $logout = $this->esc($this->router->url('admin/logout'));
        $logo = $this->esc($this->router->url(ASSETS_DIR . 'svg/logo.svg'));
        $icons = $this->esc($this->router->url(ASSETS_DIR . 'svg/icons.svg'));
        $edit = $this->editLink($context);

        return '<div class="tinycms-admin-bar" role="navigation" aria-label="' . $this->esc(I18n::t('admin.brand')) . '">'
            . '<a class="tinycms-admin-bar-brand" href="' . $dashboard . '" aria-label="' . $this->esc(I18n::t('admin.menu.dashboard')) . '"><img src="' . $logo . '" alt=""></a>'
            . $this->link($newContent, I18n::t('admin.add_content'), 'add', $icons)
            . $edit
            . $this->link($logout, I18n::t('admin.logout'), 'logout', $icons, 'tinycms-admin-bar-link-logout')
            . '</div>';
    }

    private function editLink(array $context): string
    {
        $icons = $this->esc($this->router->url(ASSETS_DIR . 'svg/icons.svg'));
        $contentId = (int)($context['item']['id'] ?? 0);
        if ($contentId > 0) {
            return $this->link($this->esc($this->router->url('admin/content/edit?id=' . $contentId)), I18n::t('admin.edit_content'), 'concept', $icons);
        }

        $userId = (int)($context['user']['id'] ?? 0);
        if ($userId > 0) {
            return $this->link($this->esc($this->router->url('admin/users/edit?id=' . $userId)), I18n::t('admin.edit_user'), 'users', $icons);
        }

        $termId = (int)($context['term']['id'] ?? 0);
        if ($termId > 0) {
            return $this->link($this->esc($this->router->url('admin/terms/edit?id=' . $termId)), I18n::t('admin.edit_term'), 'terms', $icons);
        }

        return '';
    }

    private function link(string $href, string $label, string $icon, string $iconsHref, string $class = ''): string
    {
        $linkClass = trim('tinycms-admin-bar-link ' . $class);

        return '<a class="' . $this->esc($linkClass) . '" href="' . $href . '">'
            . '<svg class="tinycms-admin-bar-icon" aria-hidden="true"><use href="' . $iconsHref . '#icon-' . $this->esc($icon) . '"></use></svg>'
            . '<span class="tinycms-admin-bar-link-label">' . $this->esc($label) . '</span>'
            . '</a>';
    }

    private function esc(string $value): string
    {
        return Escape::escAttr($value);
    }
}
