<?php
declare(strict_types=1);

if (!defined('BASE_DIR')) {
    exit;
}

return [
    'name' => t('widgets.popular_terms.name'),
    'fields' => [
        [
            'name' => 'title',
            'type' => 'text',
            'label' => t('widgets.popular_terms.title'),
        ],
        [
            'name' => 'display',
            'type' => 'select',
            'label' => t('widgets.popular_terms.display'),
            'options' => [
                'cloud' => t('widgets.popular_terms.display_cloud'),
                'list' => t('widgets.popular_terms.display_list'),
            ],
        ],
        [
            'name' => 'show_counts',
            'type' => 'checkbox',
            'label' => t('widgets.popular_terms.show_counts'),
        ],
        [
            'name' => 'limit',
            'type' => 'number',
            'label' => t('widgets.popular_terms.limit'),
            'default' => '10',
            'min' => 1,
            'max' => 50,
        ],
    ],
    'render' => static function (array $data, array $widget): string {
        $title = trim((string)($data['title'] ?? ''));
        $display = (string)($data['display'] ?? 'cloud') === 'list' ? 'list' : 'cloud';
        $showCounts = (string)($data['show_counts'] ?? '0') === '1';
        $limit = max(1, min(50, (int)($data['limit'] ?? 10)));

        $items = $widget['items']('terms', ['limit' => $limit]);
        if ($items === []) {
            return '';
        }

        $links = array_map(static function (array $term) use ($widget, $showCounts): string {
            $id = (int)($term['id'] ?? 0);
            $name = trim((string)($term['name'] ?? ''));
            $count = (int)($term['total'] ?? 0);
            $termUrl = $widget['term_url']($term);
            if ($id <= 0 || $name === '' || $termUrl === '') {
                return '';
            }

            $label = esc_html($name) . ($showCounts ? ' <span class="popular-terms-count">(' . $count . ')</span>' : '');
            return '<a href="' . esc_url($termUrl) . '">' . $label . '</a>';
        }, $items);
        $links = array_values(array_filter($links));

        $html = $widget['title']($title, 'tag');

        if ($display === 'list') {
            return $html . '<ul class="popular-terms popular-terms-list"><li>' . implode('</li><li>', $links) . '</li></ul>';
        }

        return $html . '<div class="popular-terms popular-terms-cloud">' . implode('', $links) . '</div>';
    },
];
