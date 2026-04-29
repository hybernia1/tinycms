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

        $assetHref = esc_url($this->router->url(ASSETS_DIR . 'css/admin-bar.css'));
        $stylesheet = '<link rel="stylesheet" href="' . $assetHref . '">';
        $bar = $this->html($context);

        if (str_contains($output, '</head>')) {
            $output = str_replace('</head>', $stylesheet . '</head>', $output);
        } else {
            $bar = $stylesheet . $bar;
        }

        if (str_contains($output, '</body>')) {
            return str_replace('</body>', $bar . '</body>', $output);
        }

        return $output . $bar;
    }

    private function html(array $context): string
    {
        $dashboard = esc_url($this->router->url('admin/dashboard'));
        $newContent = esc_url($this->router->url('admin/content/add'));
        $logout = esc_url($this->router->url('admin/logout'));
        $logo = esc_url($this->router->url(ASSETS_DIR . 'svg/logo.svg'));
        $edit = $this->editLink($context);

        return '<div class="tinycms-admin-bar" role="navigation" aria-label="' . esc_attr(I18n::t('admin.brand')) . '">'
            . '<a class="tinycms-admin-bar-brand" href="' . $dashboard . '" aria-label="' . esc_attr(I18n::t('admin.menu.dashboard')) . '"><img src="' . $logo . '" alt=""></a>'
            . $this->link($newContent, I18n::t('admin.add_content'), 'add')
            . $edit
            . $this->link($logout, I18n::t('admin.logout'), 'logout', 'tinycms-admin-bar-link-logout')
            . '</div>';
    }

    private function editLink(array $context): string
    {
        $contentId = (int)($context['item']['id'] ?? 0);
        if ($contentId > 0) {
            return $this->link(esc_url($this->router->url('admin/content/edit?id=' . $contentId)), I18n::t('admin.edit_content'), 'concept');
        }

        $userId = (int)($context['user']['id'] ?? 0);
        if ($userId > 0) {
            return $this->link(esc_url($this->router->url('admin/users/edit?id=' . $userId)), I18n::t('admin.edit_user'), 'users');
        }

        $termId = (int)($context['term']['id'] ?? 0);
        if ($termId > 0) {
            return $this->link(esc_url($this->router->url('admin/terms/edit?id=' . $termId)), I18n::t('admin.edit_term'), 'terms');
        }

        return '';
    }

    private function link(string $href, string $label, string $icon, string $class = ''): string
    {
        $linkClass = trim('tinycms-admin-bar-link ' . $class);

        return '<a class="' . esc_attr($linkClass) . '" href="' . $href . '">'
            . icon($icon, 'tinycms-admin-bar-icon')
            . '<span class="tinycms-admin-bar-link-label">' . esc_html($label) . '</span>'
            . '</a>';
    }
}
