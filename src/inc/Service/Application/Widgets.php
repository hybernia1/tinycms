<?php
declare(strict_types=1);

namespace App\Service\Application;

use App\Service\Front\WidgetExtensions;
use App\Service\Front\Widgets as FrontWidgets;
use App\Service\Infrastructure\Db\Connection;
use App\Service\Infrastructure\Db\Table;
use App\Service\Support\ExtensionPaths;
use App\Service\Support\I18n;

final class Widgets
{
    private const EXTENSION_TYPE = 'widget';
    private const STATE_TYPE = 'widget_layout';
    private const STATE_INSTANCE = 'layout';
    private \PDO $pdo;

    public function __construct()
    {
        $this->pdo = Connection::get();
    }

    public function boot(string $rootPath): void
    {
        WidgetExtensions::load($rootPath);
        $this->loadThemeFunctions($rootPath);
    }

    public function layout(): array
    {
        $layout = $this->storedLayout();
        if ($layout === [] && !$this->hasStoredLayout()) {
            $layout = $this->defaultLayout();
        }

        return $this->normalize($layout);
    }

    public function save(array $input): void
    {
        $layout = $this->normalize($input);
        $this->write($layout);
    }

    public function apply(): void
    {
        FrontWidgets::clearManaged();

        foreach ($this->layout() as $sidebar => $instances) {
            foreach ($instances as $position => $instance) {
                if ((int)($instance['enabled'] ?? 1) !== 1) {
                    continue;
                }

                FrontWidgets::addDefined(
                    $sidebar,
                    (string)($instance['type'] ?? ''),
                    (array)($instance['settings'] ?? []),
                    10 + $position,
                );
            }
        }
    }

    public function payload(): array
    {
        return [
            'sidebars' => FrontWidgets::sidebars(),
            'widgets' => FrontWidgets::definitions(),
            'layout' => $this->layout(),
        ];
    }

    private function normalize(array $layout): array
    {
        $sidebars = FrontWidgets::sidebars();
        $definitions = FrontWidgets::definitions();
        $result = [];
        $usedIds = [];

        foreach ($sidebars as $sidebar => $sidebarOptions) {
            $rows = is_array($layout[$sidebar] ?? null) ? $layout[$sidebar] : [];
            $result[$sidebar] = [];

            foreach ($rows as $row) {
                if (!is_array($row)) {
                    continue;
                }

                $type = $this->key((string)($row['type'] ?? $row['widget'] ?? ''));
                if ($type === '' || !isset($definitions[$type])) {
                    continue;
                }

                $result[$sidebar][] = [
                    'id' => $this->instanceId((string)($row['id'] ?? ''), $usedIds),
                    'type' => $type,
                    'enabled' => (int)((int)($row['enabled'] ?? 1) === 1),
                    'settings' => $this->settings((array)($row['settings'] ?? []), (array)($definitions[$type]['fields'] ?? [])),
                ];
            }
        }

        return $result;
    }

    private function defaultLayout(): array
    {
        return is_array($layout = apply_filters('widgets_default_layout', [])) ? $layout : [];
    }

    private function loadThemeFunctions(string $rootPath): void
    {
        $theme = trim((string)((new Settings())->resolved()['front_theme'] ?? 'default'));
        $theme = preg_match('/^[a-z0-9_-]+$/i', $theme) === 1 ? $theme : 'default';
        $themePath = ExtensionPaths::themePath($rootPath, $theme);
        $real = ExtensionPaths::safeFile($themePath . '/functions.php', $themePath);

        if ($real === '') {
            return;
        }

        I18n::pushCataloguePath($themePath . '/lang');
        try {
            require_once $real;
        } finally {
            I18n::popCataloguePath();
        }
    }

    private function settings(array $settings, array $fields): array
    {
        $result = [];
        foreach ($fields as $fieldKey => $field) {
            $key = $this->key((string)$fieldKey);
            if ($key === '') {
                continue;
            }

            $value = trim((string)($settings[$key] ?? ($field['default'] ?? '')));
            $result[$key] = mb_substr($value, 0, 255);
        }

        return $result;
    }

    private function storedLayout(): array
    {
        $table = Table::name('extensions');
        $stmt = $this->pdo->prepare("SELECT instance_key, name, area, enabled, settings FROM $table WHERE extension_type = :type ORDER BY area ASC, position ASC, id ASC");
        $stmt->execute(['type' => self::EXTENSION_TYPE]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

        $layout = [];
        foreach ($rows as $row) {
            $area = $this->key((string)($row['area'] ?? ''));
            if ($area === '') {
                continue;
            }

            $layout[$area][] = [
                'id' => (string)($row['instance_key'] ?? ''),
                'type' => (string)($row['name'] ?? ''),
                'enabled' => (int)($row['enabled'] ?? 1),
                'settings' => $this->decode((string)($row['settings'] ?? '')),
            ];
        }

        return $layout;
    }

    private function write(array $layout): void
    {
        $table = Table::name('extensions');
        $this->pdo->beginTransaction();

        try {
            $delete = $this->pdo->prepare("DELETE FROM $table WHERE extension_type IN (:widget_type, :state_type)");
            $delete->execute([
                'widget_type' => self::EXTENSION_TYPE,
                'state_type' => self::STATE_TYPE,
            ]);

            $insert = $this->pdo->prepare("INSERT INTO $table (extension_type, name, area, instance_key, enabled, position, settings) VALUES (:extension_type, :name, :area, :instance_key, :enabled, :position, :settings)");
            $insert->execute([
                'extension_type' => self::STATE_TYPE,
                'name' => 'layout',
                'area' => null,
                'instance_key' => self::STATE_INSTANCE,
                'enabled' => 1,
                'position' => 0,
                'settings' => '{}',
            ]);

            foreach ($layout as $area => $instances) {
                foreach ((array)$instances as $position => $instance) {
                    $insert->execute([
                        'extension_type' => self::EXTENSION_TYPE,
                        'name' => (string)($instance['type'] ?? ''),
                        'area' => (string)$area,
                        'instance_key' => (string)($instance['id'] ?? ''),
                        'enabled' => (int)($instance['enabled'] ?? 1),
                        'position' => (int)$position,
                        'settings' => json_encode((array)($instance['settings'] ?? []), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    ]);
                }
            }

            $this->pdo->commit();
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $e;
        }
    }

    private function hasStoredLayout(): bool
    {
        $table = Table::name('extensions');
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM $table WHERE extension_type IN (:widget_type, :state_type)");
        $stmt->execute([
            'widget_type' => self::EXTENSION_TYPE,
            'state_type' => self::STATE_TYPE,
        ]);

        return (int)$stmt->fetchColumn() > 0;
    }

    private function decode(string $value): array
    {
        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function key(string $value): string
    {
        return preg_replace('/[^a-z0-9_-]/i', '', strtolower(trim($value))) ?? '';
    }

    private function instanceId(string $value, array &$usedIds): string
    {
        $id = $this->key($value);
        if ($id === '') {
            $id = 'w_' . bin2hex(random_bytes(6));
        }
        while (isset($usedIds[$id])) {
            $id = 'w_' . bin2hex(random_bytes(6));
        }

        $usedIds[$id] = true;
        return $id;
    }
}
