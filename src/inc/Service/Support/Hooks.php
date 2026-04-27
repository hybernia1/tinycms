<?php
declare(strict_types=1);

namespace App\Service\Support;

final class Hooks
{
    private static array $hooks = [];

    public static function add(string $hook, callable $callback, int $priority = 10, int $acceptedArgs = 99): void
    {
        $name = trim($hook);
        if ($name === '') {
            return;
        }

        self::$hooks[$name][$priority][] = [
            'callback' => $callback,
            'acceptedArgs' => max(0, $acceptedArgs),
        ];

        ksort(self::$hooks[$name]);
    }

    public static function action(string $hook, mixed ...$args): void
    {
        foreach (self::callbacks($hook) as $entry) {
            $entry['callback'](...array_slice($args, 0, $entry['acceptedArgs']));
        }
    }

    public static function filter(string $hook, mixed $value, mixed ...$args): mixed
    {
        foreach (self::callbacks($hook) as $entry) {
            $value = $entry['callback'](...array_slice([$value, ...$args], 0, $entry['acceptedArgs']));
        }

        return $value;
    }

    private static function callbacks(string $hook): array
    {
        $name = trim($hook);
        if ($name === '' || !isset(self::$hooks[$name])) {
            return [];
        }

        return array_merge(...array_values(self::$hooks[$name]));
    }
}
