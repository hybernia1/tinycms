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
        $stmt = $this->pdo->query("SELECT id, label, url, link_target, position FROM $table ORDER BY position ASC, id ASC");
        return array_map([$this, 'mapItem'], $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: []);
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
            $insert = $this->pdo->prepare("INSERT INTO $table (label, url, link_target, position) VALUES (:label, :url, :link_target, :position)");

            foreach ($items as $position => $item) {
                $insert->execute([
                    'label' => $item['label'],
                    'url' => $item['url'],
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
        $targets = (array)($input['item_target'] ?? []);
        $total = max(count($labels), count($urls), count($targets));
        $items = [];
        $errors = [];

        for ($index = 0; $index < $total; $index++) {
            $label = $this->schemaConstraintValidator->truncate('menu', 'label', $this->plainText((string)($labels[$index] ?? '')), 255);
            $url = $this->schemaConstraintValidator->truncate('menu', 'url', trim((string)($urls[$index] ?? '')), 500);
            $target = trim((string)($targets[$index] ?? '_self')) === '_blank' ? '_blank' : '_self';

            if ($label === '' && $url === '') {
                continue;
            }

            if ($label === '') {
                $errors['item_label[]'] = I18n::t('menu.item_label_required');
            }

            if ($url === '') {
                $errors['item_url[]'] = I18n::t('menu.item_url_required');
            }

            $items[] = [
                'label' => $label,
                'url' => $url,
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

    private function mapItem(array $row): array
    {
        return [
            'id' => (int)($row['id'] ?? 0),
            'label' => (string)($row['label'] ?? ''),
            'url' => (string)($row['url'] ?? ''),
            'link_target' => (string)($row['link_target'] ?? '_self'),
            'position' => (int)($row['position'] ?? 0),
        ];
    }
}
