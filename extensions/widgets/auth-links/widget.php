<?php
declare(strict_types=1);

use App\Service\Application\Settings;
use App\Service\Auth\Auth;

if (!defined('BASE_DIR')) {
    exit;
}

register_widget('auth-links', static function (array $settings = []): string {
    $auth = new Auth();
    $link = static fn(string $url, string $label): string => '<a href="' . esc_url($url) . '">' . esc_html($label) . '</a>';
    $links = [];

    if ($auth->check()) {
        $links[] = $link(site_url('account'), t('front.account_title', 'My account'));
        if ($auth->isAdmin()) {
            $links[] = $link(site_url('admin'), t('admin.menu.dashboard', 'Dashboard'));
        }
        $links[] = $link(site_url('admin/logout'), t('admin.logout', 'Log out'));
    } else {
        $links[] = $link(site_url('auth/login'), t('auth.login', 'Login'));
        if ((int)((new Settings())->resolved()['allow_registration'] ?? 0) === 1) {
            $links[] = $link(site_url('auth/register'), t('auth.register', 'Register'));
        }
    }

    $title = trim((string)($settings['title'] ?? ''));
    return widget_box($title !== '' ? $title : t('widget.auth_links.title', 'Account'), '<nav class="widget-links">' . implode('', $links) . '</nav>', 'widget-auth-links');
}, [
    'label' => t('widget.auth_links.label', 'Account links'),
    'fields' => [
        'title' => [
            'type' => 'text',
            'label' => t('widget.field.title', 'Title'),
            'default' => t('widget.auth_links.title', 'Account'),
        ],
    ],
]);
