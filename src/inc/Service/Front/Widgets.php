<?php
declare(strict_types=1);

namespace App\Service\Front;

final class Widgets
{
    private static array $sidebars = [];
    private static array $definitions = [];
    private static array $widgets = [];

    public static function registerSidebar(string $id, array $options = []): void
    {
        $key = self::key($id);
        if ($key === '') {
            return;
        }

        self::$sidebars[$key] = array_merge([
            'label' => $key,
        ], $options);
    }

    public static function add(string $sidebar, callable $callback, int $priority = 10, int $acceptedArgs = 0): void
    {
        self::addCallback($sidebar, $callback, $priority, $acceptedArgs, false);
    }

    public static function addManaged(string $sidebar, callable $callback, int $priority = 10, int $acceptedArgs = 0): void
    {
        self::addCallback($sidebar, $callback, $priority, $acceptedArgs, true);
    }

    public static function define(string $id, callable $callback, array $options = []): void
    {
        $key = self::key($id);
        if ($key === '') {
            return;
        }

        self::$definitions[$key] = array_merge([
            'id' => $key,
            'label' => $key,
            'fields' => [],
            'callback' => $callback,
        ], $options);
    }

    public static function addDefined(string $sidebar, string $widget, array $settings = [], int $priority = 10): void
    {
        $key = self::key($widget);
        if ($key === '' || !isset(self::$definitions[$key])) {
            return;
        }

        $callback = self::$definitions[$key]['callback'];
        self::addManaged($sidebar, static fn(): string => (string)$callback($settings, $key, $sidebar), $priority);
    }

    public static function sidebars(): array
    {
        return self::$sidebars;
    }

    public static function definitions(): array
    {
        $definitions = [];
        foreach (self::$definitions as $key => $definition) {
            $definitions[$key] = [
                'id' => $key,
                'label' => (string)($definition['label'] ?? $key),
                'fields' => (array)($definition['fields'] ?? []),
            ];
        }

        return $definitions;
    }

    public static function clear(string $sidebar = ''): void
    {
        $key = self::key($sidebar);
        if ($key === '') {
            self::$widgets = [];
            return;
        }

        unset(self::$widgets[$key]);
    }

    public static function clearManaged(string $sidebar = ''): void
    {
        $key = self::key($sidebar);
        $keys = $key !== '' ? [$key] : array_keys(self::$widgets);
        foreach ($keys as $key) {
            foreach ((array)(self::$widgets[$key] ?? []) as $priority => $entries) {
                self::$widgets[$key][$priority] = array_values(array_filter(
                    (array)$entries,
                    static fn(array $entry): bool => !($entry['managed'] ?? false)
                ));

                if (self::$widgets[$key][$priority] === []) {
                    unset(self::$widgets[$key][$priority]);
                }
            }

            if ((self::$widgets[$key] ?? []) === []) {
                unset(self::$widgets[$key]);
            }
        }
    }

    public static function has(string $sidebar): bool
    {
        return self::callbacks($sidebar) !== [];
    }

    public static function render(string $sidebar): string
    {
        $key = self::key($sidebar);
        if ($key === '') {
            return '';
        }

        $html = '';
        foreach (self::callbacks($key) as $entry) {
            ob_start();
            $result = $entry['callback'](...array_slice([$key, self::$sidebars[$key] ?? []], 0, $entry['acceptedArgs']));
            $printed = (string)ob_get_clean();
            $html .= $printed . (is_scalar($result) ? (string)$result : '');
        }

        return trim($html);
    }

    private static function callbacks(string $sidebar): array
    {
        $key = self::key($sidebar);
        if ($key === '' || !isset(self::$widgets[$key])) {
            return [];
        }

        $callbacks = [];
        foreach (self::$widgets[$key] as $entries) {
            foreach ($entries as $entry) {
                $callbacks[] = $entry;
            }
        }

        return $callbacks;
    }

    private static function addCallback(string $sidebar, callable $callback, int $priority, int $acceptedArgs, bool $managed): void
    {
        $key = self::key($sidebar);
        if ($key === '') {
            return;
        }

        self::$widgets[$key][$priority][] = [
            'callback' => $callback,
            'acceptedArgs' => max(0, $acceptedArgs),
            'managed' => $managed,
        ];

        ksort(self::$widgets[$key]);
    }

    private static function key(string $id): string
    {
        return preg_replace('/[^a-z0-9_-]/i', '', strtolower(trim($id))) ?? '';
    }
}
