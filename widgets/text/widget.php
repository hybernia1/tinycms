<?php
declare(strict_types=1);

if (!defined('BASE_DIR')) {
    exit;
}

return [
    'name' => t('widgets.text.name'),
    'fields' => [
        ['name' => 'title', 'type' => 'text', 'label' => t('widgets.text.title')],
        ['name' => 'body', 'type' => 'textarea', 'label' => t('widgets.text.body')],
    ],
    'render' => static function (array $data): string {
        $title = trim((string)($data['title'] ?? ''));
        $body = trim((string)($data['body'] ?? ''));

        if ($title === '' && $body === '') {
            return '';
        }

        $html = widget_title($title, 'document');
        if ($body !== '') {
            $html .= '<div class="widget-content">' . esc_content($body) . '</div>';
        }

        return $html;
    },
];
