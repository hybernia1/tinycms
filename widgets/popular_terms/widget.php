<?php
declare(strict_types=1);

use App\Service\Infrastructure\Db\Connection;
use App\Service\Infrastructure\Db\Table;
use App\Service\Support\Slugger;

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
    'render' => static function (array $data): string {
        $title = trim((string)($data['title'] ?? ''));
        $display = (string)($data['display'] ?? 'cloud') === 'list' ? 'list' : 'cloud';
        $showCounts = (string)($data['show_counts'] ?? '0') === '1';
        $limit = max(1, min(50, (int)($data['limit'] ?? 10)));

        $termsTable = Table::name('terms');
        $contentTable = Table::name('content');
        $contentTermsTable = Table::name('content_terms');
        $stmt = Connection::get()->prepare(implode("\n", [
            'SELECT t.id, t.name, COUNT(DISTINCT c.id) AS total',
            "FROM $termsTable t",
            "INNER JOIN $contentTermsTable ct ON ct.term = t.id",
            "INNER JOIN $contentTable c ON c.id = ct.content",
            'WHERE c.status = :status AND c.created <= :now',
            'GROUP BY t.id, t.name',
            'ORDER BY total DESC, t.name ASC',
            'LIMIT :limit',
        ]));
        $stmt->bindValue(':status', 'published');
        $stmt->bindValue(':now', date('Y-m-d H:i:s'));
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        $items = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        if ($items === []) {
            return '';
        }

        $slugger = new Slugger();
        $links = array_map(static function (array $term) use ($slugger, $showCounts): string {
            $id = (int)($term['id'] ?? 0);
            $name = trim((string)($term['name'] ?? ''));
            $count = (int)($term['total'] ?? 0);
            if ($id <= 0 || $name === '') {
                return '';
            }

            $label = esc_html($name) . ($showCounts ? ' <span class="popular-terms-count">' . $count . '</span>' : '');
            return '<a href="' . esc_url(site_url('term/' . $slugger->slug($name, $id))) . '">' . $label . '</a>';
        }, $items);
        $links = array_values(array_filter($links));

        $html = widget_title($title, 'tag');

        if ($display === 'list') {
            return $html . '<ul class="popular-terms popular-terms-list"><li>' . implode('</li><li>', $links) . '</li></ul>';
        }

        return $html . '<div class="popular-terms popular-terms-cloud">' . implode('', $links) . '</div>';
    },
];
