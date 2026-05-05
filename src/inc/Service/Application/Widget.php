<?php
declare(strict_types=1);

namespace App\Service\Application;

use App\Service\Infrastructure\Db\Connection;
use App\Service\Infrastructure\Db\SchemaRules;
use App\Service\Infrastructure\Db\Table;
use App\Service\Support\Date;
use App\Service\Support\I18n;
use App\Service\Support\Media as MediaSupport;
use App\Service\Support\Shortcode;
use App\Service\Support\Slugger;

final class Widget
{
    private \PDO $pdo;
    private SchemaRules $schemaRules;
    private ?Slugger $slugger = null;
    private array $definitions = [];
    private array $areas = [];
    private ?array $items = null;
    private array $context = [];
    private array $contextCache = [];

    public function __construct(private string $rootPath, private string $theme)
    {
        $this->rootPath = rtrim($rootPath, '/\\');
        $this->theme = trim($theme) !== '' ? trim($theme) : 'default';
        $this->pdo = Connection::get();
        $this->schemaRules = new SchemaRules();
        $this->areas = ThemeDefinition::load($this->rootPath, $this->theme)->widgetAreas();
    }

    public function areas(): array
    {
        return array_keys($this->areas);
    }

    public function areaLabels(): array
    {
        return array_map(static fn(array $area): string => $area['label'] !== '' ? $area['label'] : $area['name'], $this->areas);
    }

    public function definitions(): array
    {
        if ($this->definitions !== []) {
            return $this->definitions;
        }

        $root = $this->widgetsPath();
        if (!is_dir($root)) {
            return [];
        }

        foreach (glob($root . '/*/widget.php') ?: [] as $file) {
            $name = $this->slug(basename(dirname($file)));
            if ($name === '') {
                continue;
            }

            $definition = $this->loadDefinition($name);
            if ($definition !== null) {
                $this->definitions[$name] = $definition;
            }
        }

        ksort($this->definitions, SORT_NATURAL);
        return $this->definitions;
    }

    public function items(?string $area = null): array
    {
        if ($this->items === null) {
            $table = Table::name('widgets');
            $stmt = $this->pdo->prepare("SELECT id, area, widget, data, position FROM $table ORDER BY area ASC, position ASC, id ASC");
            $stmt->execute();
            $this->items = array_map([$this, 'mapItem'], $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: []);
        }

        $cleanArea = $area !== null ? $this->slug($area) : '';
        if ($cleanArea === '') {
            return $this->items;
        }

        return array_values(array_filter(
            $this->items,
            static fn(array $item): bool => (string)($item['area'] ?? '') === $cleanArea
        ));
    }

    public function inactiveAreaItems(): array
    {
        $activeAreas = array_fill_keys(array_keys($this->areas), true);
        $items = [];

        foreach ($this->items() as $item) {
            $area = (string)($item['area'] ?? '');
            if ($area !== '' && !isset($activeAreas[$area])) {
                $items[] = $item;
            }
        }

        return $items;
    }

    public function renderArea(string $area, bool $editable = false): string
    {
        $area = $this->slug($area);
        if ($area === '') {
            return '';
        }

        $definitions = $this->definitions();
        $output = [];
        $index = 0;
        foreach ($this->items($area) as $item) {
            $position = $index++;
            $name = (string)($item['widget'] ?? '');
            $definition = $definitions[$name] ?? null;
            if (!is_array($definition)) {
                continue;
            }

            $html = $this->renderWidget($name, $definition, (array)($item['data'] ?? []));
            if ($html !== '') {
                $output[] = '<section class="widget widget-' . esc_attr($name) . '"' . $this->customizerAttrs($area, $position, $editable) . '>' . $this->customizerAction($editable) . $html . '</section>';
            }
        }

        return $output !== [] ? '<div class="widget-area widget-area-' . esc_attr($area) . '">' . implode('', $output) . '</div>' : '';
    }

    public function save(array $input): array
    {
        $managedAreas = $this->managedAreas((array)($input['managed_area'] ?? []));
        [$items, $errors] = $this->normalizeItems($input, $managedAreas);
        if ($errors !== []) {
            return ['success' => false, 'errors' => $errors];
        }

        $table = Table::name('widgets');
        $this->pdo->beginTransaction();

        try {
            $insert = $this->pdo->prepare("INSERT INTO $table (area, widget, data, position) VALUES (:area, :widget, :data, :position)");
            $positions = [];

            if ($managedAreas !== []) {
                $placeholders = [];
                $params = [];
                foreach ($managedAreas as $index => $area) {
                    $placeholder = ':area' . $index;
                    $placeholders[] = $placeholder;
                    $params[$placeholder] = $area;
                }

                $delete = $this->pdo->prepare("DELETE FROM $table WHERE area IN (" . implode(',', $placeholders) . ")");
                $delete->execute($params);
            }

            foreach ($items as $item) {
                $area = (string)$item['area'];
                $position = (int)($positions[$area] ?? 0);
                $positions[$area] = $position + 1;

                $insert->execute([
                    'area' => $area,
                    'widget' => $item['widget'],
                    'data' => json_encode($item['data'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'position' => $position,
                ]);
            }

            $this->pdo->commit();
            $this->items = null;
            return ['success' => true, 'errors' => []];
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            return ['success' => false, 'errors' => ['_global' => I18n::t('widgets.save_failed')]];
        }
    }

    private function normalizeItems(array $input, array $managedAreas): array
    {
        $areas = (array)($input['item_area'] ?? []);
        $widgets = (array)($input['item_widget'] ?? []);
        $data = (array)($input['item_data'] ?? []);
        $definitions = $this->definitions();
        $availableAreas = array_fill_keys($managedAreas, true);
        $items = [];
        $errors = [];

        foreach ($widgets as $index => $rawWidget) {
            $widget = $this->slug((string)$rawWidget);
            $area = $this->slug((string)($areas[$index] ?? ''));

            if ($widget === '' && $area === '') {
                continue;
            }
            if (!isset($definitions[$widget])) {
                $errors["item_widget[$index]"] = I18n::t('widgets.widget_required');
                continue;
            }
            if (!isset($availableAreas[$area])) {
                $errors["item_area[$index]"] = I18n::t('widgets.area_required');
                continue;
            }

            $items[] = [
                'area' => $this->schemaRules->truncate('widgets', 'area', $area, 100),
                'widget' => $this->schemaRules->truncate('widgets', 'widget', $widget, 100),
                'data' => $this->normalizeData($definitions[$widget], is_array($data[$index] ?? null) ? $data[$index] : []),
            ];
        }

        return [$items, $errors];
    }

    private function managedAreas(array $input): array
    {
        $areas = array_fill_keys(array_keys($this->areas), true);
        $existing = [];
        foreach ($this->items() as $item) {
            $area = $this->slug((string)($item['area'] ?? ''));
            if ($area !== '') {
                $existing[$area] = true;
            }
        }

        foreach ($input as $rawArea) {
            $area = $this->slug((string)$rawArea);
            if ($area !== '' && isset($existing[$area])) {
                $areas[$area] = true;
            }
        }

        return array_keys($areas);
    }

    private function normalizeData(array $definition, array $input): array
    {
        $data = [];
        foreach ((array)($definition['fields'] ?? []) as $field) {
            if (!is_array($field)) {
                continue;
            }

            $name = $this->fieldName((string)($field['name'] ?? ''));
            if ($name === '') {
                continue;
            }

            $type = (string)($field['type'] ?? 'text');
            $value = trim((string)($input[$name] ?? ''));

            if ($type === 'checkbox') {
                $data[$name] = $value === '1' ? '1' : '0';
                continue;
            }

            if ($type === 'number') {
                $min = (int)($field['min'] ?? 0);
                $max = (int)($field['max'] ?? 1000);
                $number = (int)$value;
                $data[$name] = (string)max($min, min($max, $number));
                continue;
            }

            if ($type === 'select') {
                $options = array_keys((array)($field['options'] ?? []));
                $data[$name] = in_array($value, $options, true) ? $value : (string)($options[0] ?? '');
                continue;
            }

            $limit = $type === 'textarea' ? 20000 : 1000;
            $data[$name] = mb_substr($value, 0, $limit);
        }

        return $data;
    }

    private function renderWidget(string $name, array $definition, array $data): string
    {
        $render = $definition['render'] ?? null;
        if (!is_callable($render)) {
            return '';
        }

        I18n::pushCataloguePath($this->widgetPath($name) . '/lang');
        try {
            $args = [$data];
            if ($this->callableParameterCount($render) >= 2) {
                $args[] = $this->widgetContext();
            }

            return trim((string)$render(...$args));
        } finally {
            I18n::popCataloguePath();
        }
    }

    private function widgetContext(): array
    {
        if ($this->context !== []) {
            return $this->context;
        }

        $this->context = [
            'items' => fn(string $source, array $options = []): array => $this->cachedContext('items_' . $this->itemSource($source), $options, fn(): array => $this->widgetItems($source, $options)),
            'content_url' => fn(array $item): string => $this->contentUrl($item),
            'author_url' => fn(array $user): string => $this->authorUrl($user),
            'term_url' => fn(array $term): string => function_exists('get_term_url') ? (string)get_term_url($term) : '',
            'media_url' => fn(array|string $media, string $size = 'origin'): string => $this->mediaUrl($media, $size),
            'thumbnail' => fn(array $item, array $options = []): string => $this->thumbnail($item, $options),
            'excerpt' => fn(array|string $source, int $limit = 120): string => $this->excerpt($source, $limit),
            'date' => fn(string $value): string => Date::formatDateTimeValue($value),
            'title' => fn(string $title, string $icon = ''): string => widget_title($title, $icon),
        ];

        return $this->context;
    }

    private function widgetItems(string $source, array $options): array
    {
        $source = $this->itemSource($source);
        if ($source === '') {
            return [];
        }

        $limit = max(1, min(50, (int)($options['limit'] ?? 10)));
        [$sql, $params, $intParams] = match ($source) {
            'comments' => $this->commentItemsQuery($options),
            'terms' => $this->termItemsQuery($options),
            'users' => $this->userItemsQuery($options),
            'media' => $this->mediaItemsQuery($options),
            default => $this->contentItemsQuery($options),
        };

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $name => $value) {
            $stmt->bindValue(':' . $name, $value, in_array($name, $intParams, true) ? \PDO::PARAM_INT : \PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }

    private function itemSource(string $source): string
    {
        return match ($this->slug($source)) {
            'content', 'contents', 'article', 'articles', 'post', 'posts' => 'content',
            'comment', 'comments' => 'comments',
            'term', 'terms', 'tag', 'tags' => 'terms',
            'user', 'users', 'author', 'authors' => 'users',
            'media', 'medium', 'image', 'images' => 'media',
            default => '',
        };
    }

    private function contentItemsQuery(array $options): array
    {
        $sort = $this->sort($options, ['latest', 'popular', 'commented', 'random'], 'latest');
        $days = max(1, min(365, (int)($options['days'] ?? 7)));
        $contentTable = Table::name('content');
        $contentStatsTable = Table::name('content_stats');
        $commentsTable = Table::name('comments');
        $mediaTable = Table::name('media');
        $usersTable = Table::name('users');
        $filters = $this->itemFilters($options);
        $params = Content::publicParams();
        $intParams = [];
        $joins = [
            "LEFT JOIN $usersTable u ON u.id = c.author",
            "LEFT JOIN $mediaTable m ON m.id = c.thumbnail",
        ];
        $where = [Content::publicWhere('c')];
        $views = '0 AS views_count';
        $comments = '0 AS comments_count';
        $lastVisit = 'NULL AS last_visit';
        $lastComment = 'NULL AS last_comment';
        $groupBy = '';
        $orderBy = 'COALESCE(c.updated, c.created) DESC, c.id DESC';

        if ($sort === 'popular') {
            $params['since'] = date('Y-m-d H:i:s', strtotime('-' . $days . ' days') ?: time());
            $joins[] = "INNER JOIN $contentStatsTable cs ON cs.content = c.id AND cs.last_visit >= :since";
            $views = 'COUNT(cs.ip_address) AS views_count';
            $lastVisit = 'MAX(cs.last_visit) AS last_visit';
            $groupBy = $this->contentItemsGroupBy();
            $orderBy = 'views_count DESC, last_visit DESC, c.id DESC';
        } elseif ($sort === 'commented') {
            $params['comment_status'] = Comment::STATUS_PUBLISHED;
            $joins[] = "INNER JOIN $commentsTable cm ON cm.content = c.id AND cm.status = :comment_status";
            $joins[] = "LEFT JOIN $commentsTable parent_comment ON parent_comment.id = cm.parent";
            $where[] = 'c.comments_enabled = 1';
            $where[] = '(cm.parent IS NULL OR parent_comment.status = :comment_status)';
            $comments = 'COUNT(cm.id) AS comments_count';
            $lastComment = 'MAX(cm.created) AS last_comment';
            $groupBy = $this->contentItemsGroupBy();
            $orderBy = 'comments_count DESC, last_comment DESC, c.id DESC';
        } elseif ($sort === 'random') {
            $orderBy = 'RAND()';
        }

        $this->applyContentFilters($filters, $where, $params, $intParams);

        return [implode("\n", array_filter([
            'SELECT c.id, c.name, c.excerpt, c.body, c.author, u.name AS author_name, c.created, c.updated, c.thumbnail AS thumbnail_id,',
            'm.path AS thumbnail_path, m.path AS thumbnail, m.name AS thumbnail_name,',
            "$views, $comments, $lastVisit, $lastComment",
            "FROM $contentTable c",
            ...$joins,
            'WHERE ' . implode(' AND ', $where),
            $groupBy !== '' ? 'GROUP BY ' . $groupBy : '',
            'ORDER BY ' . $orderBy,
            'LIMIT :limit',
        ])), $params, $intParams];
    }

    private function commentItemsQuery(array $options): array
    {
        $sort = $this->sort($options, ['latest', 'oldest', 'random', 'author_count'], 'latest');
        $commentsTable = Table::name('comments');
        $contentTable = Table::name('content');
        $usersTable = Table::name('users');
        $params = array_merge(Content::publicParams(), ['comment_status' => Comment::STATUS_PUBLISHED]);
        $intParams = [];
        $joins = [
            "INNER JOIN $contentTable content ON content.id = c.content",
            "LEFT JOIN $commentsTable parent_comment ON parent_comment.id = c.parent",
            "LEFT JOIN $usersTable u ON u.id = c.author",
        ];
        $where = [
            'c.status = :comment_status',
            'content.comments_enabled = 1',
            Content::publicWhere('content'),
            '(c.parent IS NULL OR parent_comment.status = :comment_status)',
        ];

        $this->applyCommentFilters($this->itemFilters($options), $where, $params, $intParams);

        if ($sort === 'author_count') {
            $commenterKey = implode(' ', [
                'CASE',
                "WHEN c.author IS NOT NULL AND c.author > 0 THEN CONCAT('user:', c.author)",
                "WHEN COALESCE(c.author_email, '') <> '' THEN CONCAT('email:', LOWER(c.author_email))",
                "ELSE CONCAT('name:', LOWER(COALESCE(c.author_name, '')))",
                'END',
            ]);

            return [implode("\n", [
                'SELECT ' . $commenterKey . ' AS commenter_key,',
                'MAX(c.author) AS author,',
                'COALESCE(NULLIF(MAX(u.name), \'\'), NULLIF(MAX(c.author_name), \'\'), \'\') AS author_name,',
                'COALESCE(NULLIF(MAX(u.email), \'\'), NULLIF(MAX(c.author_email), \'\'), \'\') AS author_email,',
                'COUNT(c.id) AS comments_count, COUNT(DISTINCT c.content) AS content_count, MAX(c.created) AS last_comment',
                "FROM $commentsTable c",
                ...$joins,
                'WHERE ' . implode(' AND ', $where),
                'GROUP BY commenter_key',
                "HAVING author_name <> ''",
                'ORDER BY comments_count DESC, last_comment DESC, author_name ASC',
                'LIMIT :limit',
            ]), $params, $intParams];
        }

        $orderBy = match ($sort) {
            'oldest' => 'c.created ASC, c.id ASC',
            'random' => 'RAND()',
            default => 'c.created DESC, c.id DESC',
        };

        return [implode("\n", [
            'SELECT c.id, c.content, c.author, c.body, c.created, content.name AS content_name,',
            'COALESCE(NULLIF(u.name, \'\'), c.author_name) AS author_name,',
            'COALESCE(NULLIF(u.email, \'\'), c.author_email) AS author_email',
            "FROM $commentsTable c",
            ...$joins,
            'WHERE ' . implode(' AND ', $where),
            'ORDER BY ' . $orderBy,
            'LIMIT :limit',
        ]), $params, $intParams];
    }

    private function termItemsQuery(array $options): array
    {
        $sort = $this->sort($options, ['popular', 'latest', 'name'], 'popular');
        $termsTable = Table::name('terms');
        $contentTable = Table::name('content');
        $contentTermsTable = Table::name('content_terms');
        $usersTable = Table::name('users');
        $filters = $this->itemFilters($options);
        $params = Content::publicParams();
        $intParams = [];
        $joins = [
            "INNER JOIN $contentTermsTable ct ON ct.term = t.id",
            "INNER JOIN $contentTable c ON c.id = ct.content",
            "LEFT JOIN $usersTable u ON u.id = c.author",
        ];
        $where = [Content::publicWhere('c')];

        $this->applyIdNameFilter('term', $filters['term'] ?? $filters['terms'] ?? null, 't.id', 't.name', $where, $params, $intParams);
        $this->applyContentFilters(array_diff_key($filters, array_flip(['term', 'terms'])), $where, $params, $intParams);

        $orderBy = match ($sort) {
            'latest' => 'last_content_at DESC, t.name ASC',
            'name' => 't.name ASC',
            default => 'total DESC, t.name ASC',
        };

        return [implode("\n", [
            'SELECT t.id, t.name, COUNT(DISTINCT c.id) AS total, MAX(COALESCE(c.updated, c.created)) AS last_content_at',
            "FROM $termsTable t",
            ...$joins,
            'WHERE ' . implode(' AND ', $where),
            'GROUP BY t.id, t.name',
            'ORDER BY ' . $orderBy,
            'LIMIT :limit',
        ]), $params, $intParams];
    }

    private function userItemsQuery(array $options): array
    {
        $sort = $this->sort($options, ['latest', 'content_count', 'name'], 'latest');
        $filters = $this->itemFilters($options);
        $usersTable = Table::name('users');
        $contentTable = Table::name('content');
        $mediaTable = Table::name('media');
        $joins = [];
        $where = ['u.suspend = 0'];
        $params = [];
        $intParams = [];
        $needsContent = $sort === 'content_count' || $this->hasAnyFilter($filters, ['content', 'term', 'terms', 'type', 'types', 'has_thumbnail', 'date', 'day']);

        $this->applyIdNameFilter('user', $filters['user'] ?? $filters['users'] ?? $filters['author'] ?? $filters['authors'] ?? null, 'u.id', 'u.name', $where, $params, $intParams);

        if ($needsContent) {
            $params = array_merge($params, Content::publicParams());
            $joins[] = "INNER JOIN $contentTable c ON c.author = u.id";
            $joins[] = "LEFT JOIN $mediaTable m ON m.id = c.thumbnail";
            $where[] = Content::publicWhere('c');
            $this->applyContentFilters(array_diff_key($filters, array_flip(['author', 'authors', 'user', 'users'])), $where, $params, $intParams);
        }

        $selectStats = $needsContent ? 'COUNT(DISTINCT c.id) AS content_count, MAX(COALESCE(c.updated, c.created)) AS last_content_at' : '0 AS content_count, NULL AS last_content_at';
        $groupBy = $needsContent ? 'GROUP BY u.id, u.name, u.created' : '';
        $orderBy = match ($sort) {
            'content_count' => 'content_count DESC, last_content_at DESC, u.name ASC',
            'name' => 'u.name ASC, u.id ASC',
            default => 'u.created DESC, u.id DESC',
        };

        return [implode("\n", array_filter([
            "SELECT u.id, u.name, u.created, $selectStats",
            "FROM $usersTable u",
            ...$joins,
            'WHERE ' . implode(' AND ', $where),
            $groupBy,
            'ORDER BY ' . $orderBy,
            'LIMIT :limit',
        ])), $params, $intParams];
    }

    private function mediaItemsQuery(array $options): array
    {
        $sort = $this->sort($options, ['latest_content', 'content_count'], 'latest_content');
        $mediaTable = Table::name('media');
        $contentTable = Table::name('content');
        $contentMediaTable = Table::name('content_media');
        $publicParams = Content::publicParams();
        $params = [
            'thumb_status' => $publicParams['status'],
            'thumb_now' => $publicParams['now'],
            'body_status' => $publicParams['status'],
            'body_now' => $publicParams['now'],
        ];
        $orderBy = $sort === 'content_count'
            ? 'content_count DESC, last_content_at DESC, m.id DESC'
            : 'last_content_at DESC, m.id DESC';

        return [implode("\n", [
            'SELECT m.id, m.name, m.path, m.created, m.updated,',
            'COUNT(DISTINCT media_usage.content) AS content_count, MAX(media_usage.content_date) AS last_content_at',
            "FROM $mediaTable m",
            'INNER JOIN (',
            '    SELECT c.thumbnail AS media, c.id AS content, COALESCE(c.updated, c.created) AS content_date',
            "    FROM $contentTable c",
            '    WHERE c.thumbnail IS NOT NULL AND c.status = :thumb_status AND c.created <= :thumb_now',
            '    UNION',
            '    SELECT cm.media, c.id AS content, COALESCE(c.updated, c.created) AS content_date',
            "    FROM $contentTable c",
            "    INNER JOIN $contentMediaTable cm ON cm.content = c.id",
            '    WHERE c.status = :body_status AND c.created <= :body_now',
            ') media_usage ON media_usage.media = m.id',
            'GROUP BY m.id, m.name, m.path, m.created, m.updated',
            'ORDER BY ' . $orderBy,
            'LIMIT :limit',
        ]), $params, []];
    }

    private function itemFilters(array $options): array
    {
        $filters = is_array($options['filters'] ?? null) ? $options['filters'] : [];
        foreach (['author', 'authors', 'content', 'term', 'terms', 'type', 'types', 'has_thumbnail', 'date', 'day'] as $key) {
            if (array_key_exists($key, $options) && !array_key_exists($key, $filters)) {
                $filters[$key] = $options[$key];
            }
        }

        return $filters;
    }

    private function hasAnyFilter(array $filters, array $keys): bool
    {
        foreach ($keys as $key) {
            if (!array_key_exists($key, $filters)) {
                continue;
            }

            if (is_bool($filters[$key]) || $this->filterValues($filters[$key]) !== []) {
                return true;
            }
        }

        return false;
    }

    private function applyContentFilters(array $filters, array &$where, array &$params, array &$intParams): void
    {
        $this->applyIdNameFilter('author', $filters['author'] ?? $filters['authors'] ?? null, 'c.author', 'u.name', $where, $params, $intParams);
        $this->applyIdNameFilter('content', $filters['content'] ?? null, 'c.id', 'c.name', $where, $params, $intParams);
        $this->applyTermFilter($filters['term'] ?? $filters['terms'] ?? null, 'c.id', $where, $params, $intParams);
        $this->applyTypeFilter($filters['type'] ?? $filters['types'] ?? null, 'c.type', $where, $params, $intParams);
        $this->applyDateFilter($filters['date'] ?? $filters['day'] ?? null, 'c.created', $where, $params);

        if (array_key_exists('has_thumbnail', $filters)) {
            $where[] = $this->boolValue($filters['has_thumbnail']) ? 'c.thumbnail IS NOT NULL' : 'c.thumbnail IS NULL';
        }
    }

    private function applyCommentFilters(array $filters, array &$where, array &$params, array &$intParams): void
    {
        $this->applyIdNameFilter('author', $filters['author'] ?? $filters['authors'] ?? null, 'c.author', "COALESCE(NULLIF(u.name, ''), c.author_name)", $where, $params, $intParams);
        $this->applyIdNameFilter('content', $filters['content'] ?? null, 'c.content', 'content.name', $where, $params, $intParams);
        $this->applyTermFilter($filters['term'] ?? $filters['terms'] ?? null, 'content.id', $where, $params, $intParams);
        $this->applyTypeFilter($filters['type'] ?? $filters['types'] ?? null, 'content.type', $where, $params, $intParams);
        $this->applyDateFilter($filters['date'] ?? $filters['day'] ?? null, 'c.created', $where, $params);

        if (array_key_exists('has_thumbnail', $filters)) {
            $where[] = $this->boolValue($filters['has_thumbnail']) ? 'content.thumbnail IS NOT NULL' : 'content.thumbnail IS NULL';
        }
    }

    private function applyIdNameFilter(string $name, mixed $value, string $idColumn, string $nameColumn, array &$where, array &$params, array &$intParams): void
    {
        [$ids, $names] = $this->splitIdNameValues($value);
        $conditions = [];
        if ($ids !== []) {
            $conditions[] = $idColumn . ' IN (' . $this->bindWidgetList($name . '_id', $ids, $params, $intParams, true) . ')';
        }
        if ($names !== []) {
            $conditions[] = 'LOWER(' . $nameColumn . ') IN (' . $this->bindWidgetList($name . '_name', $names, $params, $intParams) . ')';
        }
        if ($conditions !== []) {
            $where[] = '(' . implode(' OR ', $conditions) . ')';
        }
    }

    private function applyTermFilter(mixed $value, string $contentColumn, array &$where, array &$params, array &$intParams): void
    {
        [$ids, $names] = $this->splitIdNameValues($value);
        if ($ids === [] && $names === []) {
            return;
        }

        $termsTable = Table::name('terms');
        $contentTermsTable = Table::name('content_terms');
        $conditions = [];
        if ($ids !== []) {
            $conditions[] = 'ct.term IN (' . $this->bindWidgetList('term_id', $ids, $params, $intParams, true) . ')';
        }
        if ($names !== []) {
            $conditions[] = 'LOWER(t.name) IN (' . $this->bindWidgetList('term_name', $names, $params, $intParams) . ')';
        }

        $where[] = sprintf(
            'EXISTS (SELECT 1 FROM %s ct INNER JOIN %s t ON t.id = ct.term WHERE ct.content = %s AND (%s))',
            $contentTermsTable,
            $termsTable,
            $contentColumn,
            implode(' OR ', $conditions)
        );
    }

    private function applyTypeFilter(mixed $value, string $column, array &$where, array &$params, array &$intParams): void
    {
        $types = array_values(array_intersect($this->stringValues($value), Content::TYPES));
        if ($types !== []) {
            $where[] = $column . ' IN (' . $this->bindWidgetList('type', $types, $params, $intParams) . ')';
        }
    }

    private function applyDateFilter(mixed $value, string $column, array &$where, array &$params): void
    {
        $raw = trim((string)($value ?? ''));
        if ($raw === '') {
            return;
        }

        $timestamp = strtotime($raw);
        if ($timestamp === false) {
            return;
        }

        $params['date_from'] = date('Y-m-d 00:00:00', $timestamp);
        $params['date_to'] = date('Y-m-d 00:00:00', strtotime('+1 day', $timestamp) ?: $timestamp);
        $where[] = $column . ' >= :date_from AND ' . $column . ' < :date_to';
    }

    private function sort(array $options, array $allowed, string $default): string
    {
        $sort = $this->slug((string)($options['sort'] ?? $default));
        return in_array($sort, $allowed, true) ? $sort : $default;
    }

    private function splitIdNameValues(mixed $value): array
    {
        $ids = [];
        $names = [];
        foreach ($this->filterValues($value) as $item) {
            if (is_int($item) || (is_string($item) && ctype_digit($item))) {
                $id = (int)$item;
                if ($id > 0) {
                    $ids[] = $id;
                }
                continue;
            }

            $name = mb_strtolower(trim((string)$item));
            if ($name !== '') {
                $names[] = $name;
            }
        }

        return [array_values(array_unique($ids)), array_values(array_unique($names))];
    }

    private function stringValues(mixed $value): array
    {
        return array_values(array_unique(array_map(
            static fn(mixed $item): string => trim((string)$item),
            $this->filterValues($value)
        )));
    }

    private function filterValues(mixed $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        $values = is_array($value) ? $value : [$value];
        return array_values(array_filter($values, static function (mixed $item): bool {
            return !is_array($item) && !is_object($item) && trim((string)$item) !== '';
        }));
    }

    private function bindWidgetList(string $prefix, array $values, array &$params, array &$intParams, bool $ints = false): string
    {
        $placeholders = [];
        foreach (array_values($values) as $index => $value) {
            $key = $prefix . '_' . $index;
            $placeholders[] = ':' . $key;
            $params[$key] = $ints ? (int)$value : (string)$value;
            if ($ints) {
                $intParams[] = $key;
            }
        }

        return implode(', ', $placeholders);
    }

    private function boolValue(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    private function contentItemsGroupBy(): string
    {
        return 'c.id, c.name, c.excerpt, c.body, c.author, u.name, c.created, c.updated, c.thumbnail, m.path, m.name';
    }

    private function contentUrl(array $item): string
    {
        $id = (int)($item['id'] ?? $item['content'] ?? 0);
        $name = trim((string)($item['name'] ?? $item['content_name'] ?? ''));

        return $id > 0 && $name !== '' ? site_url($this->slugger()->slug($name, $id)) : '';
    }

    private function authorUrl(array $user): string
    {
        $id = (int)($user['id'] ?? $user['ID'] ?? $user['author'] ?? 0);
        $name = trim((string)($user['name'] ?? $user['author_name'] ?? 'author'));

        return $id > 0 ? site_url('author/' . $this->slugger()->slug($name !== '' ? $name : 'author', $id)) : '';
    }

    private function mediaUrl(array|string $media, string $size): string
    {
        $path = is_array($media) ? trim((string)($media['path'] ?? '')) : trim($media);

        return $path !== '' ? site_url(MediaSupport::bySize($path, $size)) : '';
    }

    private function thumbnail(array $item, array $options): string
    {
        if (!function_exists('get_content_thumbnail')) {
            return '';
        }

        $item['thumbnail'] = trim((string)($item['thumbnail'] ?? $item['thumbnail_path'] ?? ''));
        return $item['thumbnail'] !== '' ? get_content_thumbnail($item, $options) : '';
    }

    private function excerpt(array|string $source, int $limit): string
    {
        $value = is_array($source)
            ? (string)($source['excerpt'] ?? $source['body'] ?? '')
            : $source;

        if (is_array($source) && trim((string)($source['excerpt'] ?? '')) === '') {
            $value = (string)($source['body'] ?? '');
        }

        $trustedBlocks = [];
        $value = trim(preg_replace('/\s+/u', ' ', strip_tags(html_entity_decode(Shortcode::render($value, $trustedBlocks), ENT_QUOTES | ENT_HTML5, 'UTF-8'))) ?? '');
        $limit = max(1, $limit);

        return mb_strlen($value) > $limit ? mb_substr($value, 0, max(1, $limit - 3)) . '...' : $value;
    }

    private function callableParameterCount(callable $callable): int
    {
        try {
            if (is_array($callable)) {
                return (new \ReflectionMethod($callable[0], (string)$callable[1]))->getNumberOfParameters();
            }

            return (new \ReflectionFunction($callable))->getNumberOfParameters();
        } catch (\ReflectionException) {
            return 1;
        }
    }

    private function cachedContext(string $name, array $options, callable $resolver): array
    {
        ksort($options);
        $key = $name . ':' . md5((string)json_encode($options));
        if (!array_key_exists($key, $this->contextCache)) {
            $this->contextCache[$key] = $resolver();
        }

        return $this->contextCache[$key];
    }

    private function slugger(): Slugger
    {
        return $this->slugger ??= new Slugger();
    }

    private function customizerAttrs(string $area, int $index, bool $editable): string
    {
        if (!$editable) {
            return '';
        }

        return ' data-customizer-widget data-customizer-widget-area="' . esc_attr($area) . '" data-customizer-widget-index="' . $index . '"';
    }

    private function customizerAction(bool $editable): string
    {
        if (!$editable) {
            return '';
        }

        $label = esc_attr(I18n::t('widgets.edit_in_customizer', 'Edit widget'));
        return '<button class="customizer-widget-edit" type="button" data-customizer-widget-edit aria-label="' . $label . '" title="' . $label . '">' . icon('concept') . '</button>';
    }

    private function loadDefinition(string $name): ?array
    {
        $path = $this->widgetPath($name);
        $file = $path . '/widget.php';
        if (!$this->isInside($file, $this->widgetsPath())) {
            return null;
        }

        I18n::pushCataloguePath($path . '/lang');
        try {
            $definition = require $file;
        } finally {
            I18n::popCataloguePath();
        }

        if (!is_array($definition)) {
            return null;
        }

        $definition['name'] = trim((string)($definition['name'] ?? $name));
        $definition['fields'] = array_values(array_filter((array)($definition['fields'] ?? []), 'is_array'));
        return $definition;
    }

    private function mapItem(array $row): array
    {
        return [
            'id' => (int)($row['id'] ?? 0),
            'area' => (string)($row['area'] ?? ''),
            'widget' => (string)($row['widget'] ?? ''),
            'data' => $this->decodeData((string)($row['data'] ?? '')),
            'position' => (int)($row['position'] ?? 0),
        ];
    }

    private function decodeData(string $data): array
    {
        $decoded = json_decode($data, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function themePath(): string
    {
        $themeDir = trim((string)(defined('THEMES_DIR') ? THEMES_DIR : 'themes/'), '/');
        return $this->rootPath . '/' . $themeDir . '/' . trim($this->theme, '/');
    }

    private function widgetsPath(): string
    {
        return $this->rootPath . '/widgets';
    }

    private function widgetPath(string $name): string
    {
        return $this->widgetsPath() . '/' . $this->slug($name);
    }

    private function isInside(string $file, string $root): bool
    {
        $real = realpath($file);
        $base = realpath($root);
        $normalizedReal = $real === false ? '' : str_replace('\\', '/', $real);
        $normalizedBase = $base === false ? '' : str_replace('\\', '/', $base);

        return $normalizedReal !== '' && $normalizedBase !== '' && str_starts_with($normalizedReal, $normalizedBase) && is_file($real);
    }

    private function slug(string $value): string
    {
        $clean = strtolower(trim($value));
        return preg_match('/^[a-z0-9_-]{1,100}$/', $clean) === 1 ? $clean : '';
    }

    private function fieldName(string $value): string
    {
        $clean = trim($value);
        return preg_match('/^[a-z0-9_-]{1,100}$/i', $clean) === 1 ? $clean : '';
    }
}
