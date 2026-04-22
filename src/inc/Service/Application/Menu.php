<?php
declare(strict_types=1);

namespace App\Service\Application;

use App\Service\Infrastructure\Db\Connection;
use App\Service\Infrastructure\Db\SchemaConstraintValidator;
use App\Service\Infrastructure\Db\Table;
use App\Service\Support\I18n;

final class Menu
{
    private \PDO $pdo;
    private SchemaConstraintValidator $schemaConstraintValidator;

    public function __construct()
    {
        $this->pdo = Connection::get();
        $this->schemaConstraintValidator = new SchemaConstraintValidator();
    }

    public function items(): array
    {
        $table = Table::name('menu');
        $stmt = $this->pdo->query("SELECT id, label, url, icon, link_target, position FROM $table ORDER BY position ASC, id ASC");
        return array_map([$this, 'mapItem'], $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: []);
    }

    public function icons(): array
    {
        $base = defined('BASE_DIR') ? BASE_DIR : dirname(__DIR__, 4);
        $file = rtrim($base, '/\\') . '/src/assets/svg/icons.svg';
        if (!is_file($file)) {
            return [];
        }

        $svg = (string)file_get_contents($file);
        preg_match_all('/<symbol\b[^>]*\bid="icon-([^"]+)"/', $svg, $matches);

        $icons = array_values(array_unique(array_map('trim', $matches[1] ?? [])));
        sort($icons, SORT_NATURAL);
        return $icons;
    }

    public function save(array $input): array
    {
        [$items, $errors] = $this->normalizeItems($input);
        if ($errors !== []) {
            return ['success' => false, 'errors' => $errors];
        }

        $table = Table::name('menu');
        $this->pdo->beginTransaction();

        try {
            $this->pdo->exec("DELETE FROM $table");
            $insert = $this->pdo->prepare("INSERT INTO $table (label, url, icon, link_target, position) VALUES (:label, :url, :icon, :link_target, :position)");

            foreach ($items as $position => $item) {
                $insert->execute([
                    'label' => $item['label'],
                    'url' => $item['url'],
                    'icon' => $item['icon'] !== '' ? $item['icon'] : null,
                    'link_target' => $item['link_target'],
                    'position' => $position,
                ]);
            }

            $this->pdo->commit();
            return ['success' => true, 'errors' => []];
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            return ['success' => false, 'errors' => ['_global' => I18n::t('menu.save_failed')]];
        }
    }

    private function normalizeItems(array $input): array
    {
        $labels = (array)($input['item_label'] ?? []);
        $urls = (array)($input['item_url'] ?? []);
        $icons = (array)($input['item_icon'] ?? []);
        $targets = (array)($input['item_target'] ?? []);
        $total = max(count($labels), count($urls), count($icons), count($targets));
        $availableIcons = array_flip($this->icons());
        $items = [];
        $errors = [];

        for ($index = 0; $index < $total; $index++) {
            $label = $this->schemaConstraintValidator->truncate('menu', 'label', $this->plainText((string)($labels[$index] ?? '')), 255);
            $url = $this->schemaConstraintValidator->truncate('menu', 'url', trim((string)($urls[$index] ?? '')), 500);
            $icon = $this->normalizeIcon((string)($icons[$index] ?? ''), $availableIcons);
            $target = trim((string)($targets[$index] ?? '_self')) === '_blank' ? '_blank' : '_self';

            if ($label === '' && $url === '' && $icon === '') {
                continue;
            }

            if ($label === '' && $icon === '') {
                $errors['item_label[]'] = I18n::t('menu.item_label_required');
            }

            if ($url === '') {
                $errors['item_url[]'] = I18n::t('menu.item_url_required');
            }

            $items[] = [
                'label' => $label,
                'url' => $url,
                'icon' => $icon,
                'link_target' => $target,
            ];
        }

        return [$items, $errors];
    }

    private function plainText(string $value): string
    {
        $clean = trim(html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        return preg_replace('/\s+/', ' ', $clean) ?? '';
    }

    private function normalizeIcon(string $value, array $availableIcons): string
    {
        $raw = trim($value);
        $icon = preg_replace('/[^a-z0-9_-]/i', '', str_starts_with($raw, 'icon-') ? substr($raw, 5) : $raw) ?? '';
        return isset($availableIcons[$icon]) ? $this->schemaConstraintValidator->truncate('menu', 'icon', $icon, 100) : '';
    }

    private function mapItem(array $row): array
    {
        return [
            'id' => (int)($row['id'] ?? 0),
            'label' => (string)($row['label'] ?? ''),
            'url' => (string)($row['url'] ?? ''),
            'icon' => (string)($row['icon'] ?? ''),
            'link_target' => (string)($row['link_target'] ?? '_self'),
            'position' => (int)($row['position'] ?? 0),
        ];
    }
}
