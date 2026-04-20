<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\Application\Auth;
use App\Service\Application\Settings as SettingsService;
use App\Service\Support\Csrf;
use App\Service\Support\Flash;
use App\View\AdminView;

final class Settings extends Admin
{
    public function __construct(
        private AdminView $pages,
        Auth $authService,
        private SettingsService $settings,
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

        $resolved = $this->settings->resolved();
        $this->pages->adminSettingsForm($this->settings->fields(), [
            'app_lang' => (string)($resolved['app_lang'] ?? APP_LANG),
            'sitename' => (string)($resolved['sitename'] ?? ''),
            'siteauthor' => (string)($resolved['siteauthor'] ?? ''),
            'meta_description' => (string)($resolved['meta_description'] ?? ''),
            'front_home_mode' => (string)($resolved['front_home_mode'] ?? 'latest'),
            'front_home_content' => (string)($resolved['front_home_content'] ?? ''),
            'front_posts_per_page' => (string)($resolved['front_posts_per_page'] ?? APP_POSTS_PER_PAGE),
            'front_theme' => (string)($resolved['front_theme'] ?? 'default'),
            'allow_registration' => (string)($resolved['allow_registration'] ?? '0'),
            'favicon' => (string)($resolved['favicon'] ?? ''),
            'logo' => (string)($resolved['logo'] ?? ''),
            'website_url' => (string)($resolved['website_url'] ?? ''),
            'website_email' => (string)($resolved['website_email'] ?? ''),
        ]);
    }
}
