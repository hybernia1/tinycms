<?php
declare(strict_types=1);

namespace App\Service\Front;

use App\Service\Support\ExtensionPaths;
use App\Service\Support\I18n;

final class WidgetExtensions
{
    private static array $loaded = [];

    public static function load(string $rootPath): void
    {
        $root = ExtensionPaths::widgetsPath($rootPath);
        if (!is_dir($root)) {
            return;
        }

        $items = scandir($root);
        if (!is_array($items)) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            self::loadWidget($root, $item);
        }
    }

    private static function loadWidget(string $root, string $widget): void
    {
        $path = $root . '/' . trim($widget, '/\\');
        $real = ExtensionPaths::safeFile($path . '/widget.php', $root);

        if ($real === '') {
            return;
        }
        if (isset(self::$loaded[$real])) {
            return;
        }

        $langPath = $path . '/lang';
        if (is_dir($langPath)) {
            I18n::addCataloguePath($langPath);
        }

        require_once $real;
        self::$loaded[$real] = true;
    }
}
