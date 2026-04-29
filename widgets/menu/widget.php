<?php
declare(strict_types=1);

use App\Service\Application\Menu;

if (!defined('BASE_DIR')) {
    exit;
}

$url = static function (string $url): string {
    $value = trim($url);
    if ($value === '') {
        return site_url('');
    }

    if (preg_match('#^(https?:)?//#i', $value) === 1 || preg_match('#^(mailto|tel):#i', $value) === 1 || str_starts_with($value, '#')) {
        return $value;
    }

    return site_url($value);
};

$icon = static function (string $name): string {
    $icon = trim(str_starts_with($name, 'icon-') ? substr($name, 5) : $name);
    return preg_match('/^[a-z0-9_-]+$/i', $icon) === 1 ? $icon : '';
};

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
    'render' => static function (array $data) use ($url, $icon): string {
        $items = (new Menu())->items();
        if ($items === []) {
            return '';
        }

        $title = trim((string)($data['title'] ?? ''));
        $showIcons = (string)($data['show_icons'] ?? '1') === '1';
        $links = [];

        foreach ($items as $item) {
            $label = trim((string)($item['label'] ?? ''));
            $iconName = $icon((string)($item['icon'] ?? ''));
            if ($label === '' && $iconName === '') {
                continue;
            }

            $target = (string)($item['link_target'] ?? '_self') === '_blank' ? '_blank' : '_self';
            $targetAttr = $target === '_blank' ? ' target="_blank"' : '';
            $rel = $target === '_blank' ? ' rel="noopener noreferrer"' : '';
            $content = $showIcons && $iconName !== '' ? icon($iconName) : '';
            $content .= $label !== '' ? '<span>' . esc_html($label) . '</span>' : '';

            $links[] = '<li><a href="' . esc_url($url((string)($item['url'] ?? ''))) . '"' . $targetAttr . $rel . '>' . $content . '</a></li>';
        }

        if ($links === []) {
            return '';
        }

        $html = $title !== '' ? '<h2 class="widget-title">' . esc_html($title) . '</h2>' : '';
        return $html . '<ul class="widget-menu">' . implode('', $links) . '</ul>';
    },
];
