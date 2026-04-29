<?php
declare(strict_types=1);

if (!defined('BASE_DIR')) {
    exit;
}

return [
    'name' => t('widgets.menu.name'),
    'fields' => [
        [
            'name' => 'title',
            'type' => 'text',
            'label' => t('widgets.menu.title'),
        ],
        [
            'name' => 'show_icons',
            'type' => 'checkbox',
            'label' => t('widgets.menu.show_icons'),
        ],
    ],
    'render' => static function (array $data): string {
        $title = trim((string)($data['title'] ?? ''));
        $menu = get_menu([
            'class' => 'widget-menu-list',
            'item_class' => 'widget-menu-link',
            'label' => $title !== '' ? $title : t('widgets.menu.name'),
            'show_icons' => (string)($data['show_icons'] ?? '1') === '1',
        ]);

        return $menu !== '' ? widget_title($title, 'home') . $menu : '';
    },
];
