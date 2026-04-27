<?php
declare(strict_types=1);

if (!defined('BASE_DIR')) {
    exit;
}

register_widget('search', static function (array $settings = []): string {
    $form = get_search_form();
    if ($form === '') {
        return '';
    }

    $title = trim((string)($settings['title'] ?? ''));
    return widget_box($title !== '' ? $title : t('widget.search.title', 'Search'), $form, 'widget-search');
}, [
    'label' => t('widget.search.label', 'Search'),
    'fields' => [
        'title' => [
            'type' => 'text',
            'label' => t('widget.field.title', 'Title'),
            'default' => t('widget.search.title', 'Search'),
        ],
    ],
]);
