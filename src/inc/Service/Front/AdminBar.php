<?php
declare(strict_types=1);

namespace App\Service\Front;

use App\Service\Auth\Auth;
use App\Service\Infrastructure\Router\Router;

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
        $edit = $this->editLink($context);

        return '<div class="tinycms-admin-bar" role="navigation" aria-label="TinyCMS admin">'
            . '<span class="tinycms-admin-bar-brand">TinyCMS</span>'
            . '<a href="' . $dashboard . '">Dashboard</a>'
            . '<a href="' . $newContent . '">Nový příspěvek</a>'
            . $edit
            . '<a href="' . $logout . '">Odhlásit</a>'
            . '</div>';
    }

    private function editLink(array $context): string
    {
        $contentId = (int)($context['item']['id'] ?? 0);
        if ($contentId > 0) {
            return $this->link('admin/content/edit?id=' . $contentId);
        }

        $termId = (int)($context['term']['id'] ?? 0);
        if ($termId > 0) {
            return $this->link('admin/terms/edit?id=' . $termId);
        }

        $userId = (int)($context['user']['id'] ?? 0);
        if ($userId > 0) {
            return $this->link('admin/users/edit?id=' . $userId);
        }

        return '';
    }

    private function link(string $path): string
    {
        return '<a href="' . $this->esc($this->router->url($path)) . '">Editovat</a>';
    }

    private function esc(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
