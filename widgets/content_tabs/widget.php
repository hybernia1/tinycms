<?php
declare(strict_types=1);

use App\Service\Application\Content;
use App\Service\Infrastructure\Db\Connection;
use App\Service\Infrastructure\Db\Table;
use App\Service\Support\Date;
use App\Service\Support\Slugger;

if (!defined('BASE_DIR')) {
    exit;
}

$fetchContentTabs = static function (int $limit): array {
    $contentTable = Table::name('content');
    $contentStatsTable = Table::name('content_stats');
    $mediaTable = Table::name('media');
    $now = date('Y-m-d H:i:s');
    $since = date('Y-m-d H:i:s', strtotime('-7 days') ?: time());
    $hotItems = [];

    try {
        $hot = Connection::get()->prepare(implode("\n", [
            'SELECT c.id, c.name, c.created, c.updated, c.thumbnail, m.path AS thumbnail_path, m.name AS thumbnail_name,',
            'COUNT(cs.ip_address) AS views_count, MAX(cs.last_visit) AS last_visit',
            "FROM $contentTable c",
            "INNER JOIN $contentStatsTable cs ON cs.content = c.id AND cs.last_visit >= :since",
            "LEFT JOIN $mediaTable m ON m.id = c.thumbnail",
            'WHERE ' . Content::publicWhere('c'),
            'GROUP BY c.id, c.name, c.created, c.updated, c.thumbnail, m.path, m.name',
            'ORDER BY views_count DESC, last_visit DESC, c.id DESC',
            'LIMIT :limit',
        ]));
        $hot->bindValue(':status', Content::STATUS_PUBLISHED);
        $hot->bindValue(':now', $now);
        $hot->bindValue(':since', $since);
        $hot->bindValue(':limit', $limit, PDO::PARAM_INT);
        $hot->execute();
        $hotItems = $hot->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (PDOException) {
        $hotItems = [];
    }

    $latest = Connection::get()->prepare(implode("\n", [
        'SELECT c.id, c.name, c.created, c.updated, c.thumbnail, m.path AS thumbnail_path, m.name AS thumbnail_name,',
        '0 AS views_count, NULL AS last_visit',
        "FROM $contentTable c",
        "LEFT JOIN $mediaTable m ON m.id = c.thumbnail",
        'WHERE ' . Content::publicWhere('c'),
        'ORDER BY COALESCE(c.updated, c.created) DESC, c.id DESC',
        'LIMIT :limit',
    ]));
    $latest->bindValue(':status', Content::STATUS_PUBLISHED);
    $latest->bindValue(':now', $now);
    $latest->bindValue(':limit', $limit, PDO::PARAM_INT);
    $latest->execute();

    return [
        'hot' => $hotItems,
        'latest' => $latest->fetchAll(PDO::FETCH_ASSOC) ?: [],
    ];
};

$contentTabsRows = static function (array $items, bool $showThumbnail, string $mode): string {
    $slugger = new Slugger();
    $rows = array_map(static function (array $item) use ($slugger, $showThumbnail, $mode): string {
        $id = (int)($item['id'] ?? 0);
        $name = trim((string)($item['name'] ?? ''));
        if ($id <= 0 || $name === '') {
            return '';
        }

        $item['thumbnail'] = trim((string)($item['thumbnail_path'] ?? ''));
        $url = site_url($slugger->slug($name, $id));
        $thumbnail = $showThumbnail ? get_content_thumbnail($item, [
            'size' => 'small',
            'sizes' => '56px',
            'class' => 'content-tabs-thumb',
            'wrap' => false,
        ]) : '';
        $viewsCount = (int)($item['views_count'] ?? 0);
        $meta = $mode === 'hot'
            ? '<span class="content-tabs-views" title="' . esc_attr(sprintf(t('front.views_count', '%d unique views'), $viewsCount)) . '">' . icon('show') . '<span>' . $viewsCount . '</span></span>'
            : esc_html(Date::formatDateTimeValue((string)($item['updated'] ?? $item['created'] ?? '')));

        return implode('', [
            '<li>',
            '<a class="content-tabs-item' . ($thumbnail !== '' ? ' content-tabs-item-thumb' : '') . '" href="' . esc_url($url) . '">',
            $thumbnail,
            '<span class="content-tabs-text"><span class="content-tabs-title">' . esc_html($name) . '</span>',
            $meta !== '' ? '<span class="content-tabs-meta">' . $meta . '</span>' : '',
            '</span></a></li>',
        ]);
    }, $items);
    $rows = array_values(array_filter($rows));

    return $rows !== [] ? '<ul class="content-tabs-list">' . implode('', $rows) . '</ul>' : '<p class="text-muted small content-tabs-empty">' . esc_html(t('widgets.content_tabs.empty')) . '</p>';
};

return [
    'name' => t('widgets.content_tabs.name'),
    'fields' => [
        [
            'name' => 'title',
            'type' => 'text',
            'label' => t('widgets.content_tabs.title'),
        ],
        [
            'name' => 'limit',
            'type' => 'number',
            'label' => t('widgets.content_tabs.limit'),
            'default' => '5',
            'min' => 1,
            'max' => 20,
        ],
        [
            'name' => 'hot_label',
            'type' => 'text',
            'label' => t('widgets.content_tabs.hot_label'),
            'default' => t('widgets.content_tabs.hot_default'),
        ],
        [
            'name' => 'latest_label',
            'type' => 'text',
            'label' => t('widgets.content_tabs.latest_label'),
            'default' => t('widgets.content_tabs.latest_default'),
        ],
        [
            'name' => 'first_tab',
            'type' => 'select',
            'label' => t('widgets.content_tabs.first_tab'),
            'options' => [
                'hot' => t('widgets.content_tabs.hot_default'),
                'latest' => t('widgets.content_tabs.latest_default'),
            ],
        ],
        [
            'name' => 'show_thumbnail',
            'type' => 'checkbox',
            'label' => t('widgets.content_tabs.show_thumbnail'),
            'default' => '1',
        ],
    ],
    'render' => static function (array $data) use ($fetchContentTabs, $contentTabsRows): string {
        static $instance = 0;

        $title = trim((string)($data['title'] ?? ''));
        $limit = max(1, min(20, (int)($data['limit'] ?? 5)));
        $hotLabel = trim((string)($data['hot_label'] ?? ''));
        $latestLabel = trim((string)($data['latest_label'] ?? ''));
        $firstTab = (string)($data['first_tab'] ?? 'hot') === 'latest' ? 'latest' : 'hot';
        $showThumbnail = (string)($data['show_thumbnail'] ?? '1') === '1';
        $items = $fetchContentTabs($limit);

        if (($items['hot'] ?? []) === [] && ($items['latest'] ?? []) === []) {
            return '';
        }

        $instance++;
        $id = 'content-tabs-' . $instance;
        $hotId = $id . '-hot';
        $latestId = $id . '-latest';
        $hotLabel = $hotLabel !== '' ? $hotLabel : t('widgets.content_tabs.hot_default');
        $latestLabel = $latestLabel !== '' ? $latestLabel : t('widgets.content_tabs.latest_default');

        return widget_title($title, 'content')
            . '<div class="content-tabs">'
            . '<input class="content-tabs-input content-tabs-input-hot" id="' . esc_attr($hotId) . '" name="' . esc_attr($id) . '" type="radio"' . ($firstTab === 'hot' ? ' checked' : '') . '>'
            . '<input class="content-tabs-input content-tabs-input-latest" id="' . esc_attr($latestId) . '" name="' . esc_attr($id) . '" type="radio"' . ($firstTab === 'latest' ? ' checked' : '') . '>'
            . '<div class="content-tabs-controls">'
            . '<label class="content-tabs-tab content-tabs-tab-hot" for="' . esc_attr($hotId) . '">' . esc_html($hotLabel) . '</label>'
            . '<label class="content-tabs-tab content-tabs-tab-latest" for="' . esc_attr($latestId) . '">' . esc_html($latestLabel) . '</label>'
            . '</div>'
            . '<div class="content-tabs-panels">'
            . '<div class="content-tabs-panel content-tabs-panel-hot">' . $contentTabsRows((array)($items['hot'] ?? []), $showThumbnail, 'hot') . '</div>'
            . '<div class="content-tabs-panel content-tabs-panel-latest">' . $contentTabsRows((array)($items['latest'] ?? []), $showThumbnail, 'latest') . '</div>'
            . '</div></div>';
    },
];
