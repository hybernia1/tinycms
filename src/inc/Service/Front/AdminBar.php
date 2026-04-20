<?php
declare(strict_types=1);

namespace App\Service\Front;

use App\Service\Auth\Auth;
use App\Service\Infrastructure\Router\Router;
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
        $edit = $this->editLink($context);

        return '<div class="tinycms-admin-bar" role="navigation" aria-label="' . $this->esc(I18n::t('admin.brand')) . '">'
            . '<a class="tinycms-admin-bar-brand" href="' . $dashboard . '" aria-label="' . $this->esc(I18n::t('admin.menu.dashboard')) . '"><img src="' . $logo . '" alt=""></a>'
            . '<a href="' . $newContent . '">' . $this->esc(I18n::t('admin.add_content')) . '</a>'
            . $edit
            . '<a href="' . $logout . '">' . $this->esc(I18n::t('admin.logout')) . '</a>'
            . '</div>';
    }

    private function editLink(array $context): string
    {
        $contentId = (int)($context['item']['id'] ?? 0);
        if ($contentId > 0) {
            return $this->link('admin/content/edit?id=' . $contentId, I18n::t('admin.edit_content'));
        }

        $userId = (int)($context['user']['id'] ?? 0);
        if ($userId > 0) {
            return $this->link('admin/users/edit?id=' . $userId, I18n::t('admin.edit_user'));
        }

        $termId = (int)($context['term']['id'] ?? 0);
        if ($termId > 0) {
            return $this->link('admin/terms/edit?id=' . $termId, I18n::t('admin.edit_term'));
        }

        return '';
    }

    private function link(string $path, string $label): string
    {
        return '<a href="' . $this->esc($this->router->url($path)) . '">' . $this->esc($label) . '</a>';
    }

    private function esc(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
