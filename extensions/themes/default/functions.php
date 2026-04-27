<?php
declare(strict_types=1);

if (!defined('BASE_DIR')) {
    exit;
}

register_sidebar('left', ['label' => t('theme.sidebar.left', 'Left sidebar')]);
register_sidebar('right', ['label' => t('theme.sidebar.right', 'Right sidebar')]);

add_filter('widgets_default_layout', static fn(array $layout): array => [
    'left' => [
        ['id' => 'default_search', 'type' => 'search', 'enabled' => 1, 'settings' => []],
    ],
    'right' => [
        ['id' => 'default_auth_links', 'type' => 'auth-links', 'enabled' => 1, 'settings' => []],
    ],
]);
