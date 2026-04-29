<?php
declare(strict_types=1);

namespace App\Service\Application;

use App\Service\Infrastructure\Db\Connection;
use App\Service\Infrastructure\Db\SchemaConstraintValidator;
use App\Service\Infrastructure\Db\Table;
use App\Service\Support\I18n;

final class Widget
{
    private static ?self $current = null;
    private \PDO $pdo;
    private SchemaConstraintValidator $schemaConstraintValidator;
    private array $definitions = [];
    private array $areas = [];

    public function __construct(private string $rootPath, private string $theme)
    {
        $this->rootPath = rtrim($rootPath, '/\\');
        $this->theme = trim($theme) !== '' ? trim($theme) : 'default';
        $this->pdo = Connection::get();
        $this->schemaConstraintValidator = new SchemaConstraintValidator();
        $this->ensureTable();
        $this->loadThemeFunctions();
    }

    public static function current(): ?self
    {
        return self::$current;
    }

    public function registerArea(string $area, string $label = ''): void
    {
        $clean = $this->slug($area);
        if ($clean !== '') {
            $this->areas[$clean] = [
                'name' => $clean,
                'label' => trim($label),
            ];
        }
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

    public function items(?string $area = null, bool $activeOnly = false): array
    {
        $table = Table::name('widgets');
        $conditions = [];
        $params = [];

        $cleanArea = $area !== null ? $this->slug($area) : '';
        if ($cleanArea !== '') {
            $conditions[] = 'area = :area';
            $params['area'] = $cleanArea;
        }
        if ($activeOnly) {
            $conditions[] = 'active = 1';
        }

        $where = $conditions !== [] ? ' WHERE ' . implode(' AND ', $conditions) : '';
        $stmt = $this->pdo->prepare("SELECT id, area, widget, data, active, position FROM $table$where ORDER BY area ASC, position ASC, id ASC");
        $stmt->execute($params);

        return array_map([$this, 'mapItem'], $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: []);
    }

    public function renderArea(string $area): string
    {
        $area = $this->slug($area);
        if ($area === '') {
            return '';
        }

        $definitions = $this->definitions();
        $output = [];
        foreach ($this->items($area, true) as $item) {
            $name = (string)($item['widget'] ?? '');
            $definition = $definitions[$name] ?? null;
            if (!is_array($definition)) {
                continue;
            }

            $html = $this->renderWidget($name, $definition, (array)($item['data'] ?? []));
            if ($html !== '') {
                $output[] = '<section class="widget widget-' . esc_attr($name) . '">' . $html . '</section>';
            }
        }

        return $output !== [] ? '<div class="widget-area widget-area-' . esc_attr($area) . '">' . implode('', $output) . '</div>' : '';
    }

    public function save(array $input): array
    {
        [$items, $errors] = $this->normalizeItems($input);
        if ($errors !== []) {
            return ['success' => false, 'errors' => $errors];
        }

        $table = Table::name('widgets');
        $this->pdo->beginTransaction();

        try {
            $this->pdo->exec("DELETE FROM $table");
            $insert = $this->pdo->prepare("INSERT INTO $table (area, widget, data, active, position) VALUES (:area, :widget, :data, :active, :position)");
            $positions = [];

            foreach ($items as $item) {
                $area = (string)$item['area'];
                $position = (int)($positions[$area] ?? 0);
                $positions[$area] = $position + 1;

                $insert->execute([
                    'area' => $area,
                    'widget' => $item['widget'],
                    'data' => json_encode($item['data'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'active' => $item['active'],
                    'position' => $position,
                ]);
            }

            $this->pdo->commit();
            return ['success' => true, 'errors' => []];
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            return ['success' => false, 'errors' => ['_global' => I18n::t('widgets.save_failed')]];
        }
    }

    private function normalizeItems(array $input): array
    {
        $areas = (array)($input['item_area'] ?? []);
        $widgets = (array)($input['item_widget'] ?? []);
        $active = (array)($input['item_active'] ?? []);
        $data = (array)($input['item_data'] ?? []);
        $definitions = $this->definitions();
        $availableAreas = $this->areas;
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
                'area' => $this->schemaConstraintValidator->truncate('widgets', 'area', $area, 100),
                'widget' => $this->schemaConstraintValidator->truncate('widgets', 'widget', $widget, 100),
                'active' => (int)($active[$index] ?? 1) === 1 ? 1 : 0,
                'data' => $this->normalizeData($definitions[$widget], is_array($data[$index] ?? null) ? $data[$index] : []),
            ];
        }

        return [$items, $errors];
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
            return trim((string)$render($data));
        } finally {
            I18n::popCataloguePath();
        }
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
            'active' => (int)($row['active'] ?? 1),
            'position' => (int)($row['position'] ?? 0),
        ];
    }

    private function decodeData(string $data): array
    {
        $decoded = json_decode($data, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function ensureTable(): void
    {
        foreach (SchemaDefinition::widgetsDdl(Table::prefix()) as $query) {
            $this->pdo->exec($query);
        }
    }

    private function loadThemeFunctions(): void
    {
        $file = $this->themePath() . '/functions.php';
        if (!$this->isInside($file, $this->themePath())) {
            return;
        }

        $previous = self::$current;
        self::$current = $this;

        try {
            require $file;
        } finally {
            self::$current = $previous;
        }
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
