<?php
declare(strict_types=1);

namespace App\Service\Feature;

final class InstallRequirementsService
{
    private const MIN_PHP_VERSION = '8.1.0';

    public function evaluate(string $rootPath): array
    {
        $required = [
            $this->phpVersionCheck(),
            $this->extensionCheck('pdo'),
            $this->extensionCheck('pdo_mysql'),
            $this->extensionCheck('mbstring'),
            $this->extensionCheck('fileinfo'),
            $this->extensionCheck('gd'),
            $this->extensionCheck('iconv'),
            $this->extensionCheck('session'),
            $this->gdFunctionCheck('imagecreatefromjpeg'),
            $this->gdFunctionCheck('imagecreatefrompng'),
            $this->gdFunctionCheck('imagecreatefromgif'),
            $this->gdFunctionCheck('imagecreatefromwebp'),
            $this->gdFunctionCheck('imagewebp'),
            $this->writableCheck($rootPath, 'config'),
            $this->writableCheck($rootPath, 'uploads'),
        ];

        $recommended = [
            $this->transliteratorCheck(),
            $this->extensionCheck('curl', false),
        ];

        return [
            'required' => $required,
            'recommended' => $recommended,
            'hasBlockingErrors' => $this->hasFailures($required),
        ];
    }

    private function phpVersionCheck(): array
    {
        $current = PHP_VERSION;
        $ok = version_compare($current, self::MIN_PHP_VERSION, '>=');

        return [
            'key' => 'php_version',
            'ok' => $ok,
            'required' => true,
            'current' => $current,
            'target' => self::MIN_PHP_VERSION,
        ];
    }

    private function extensionCheck(string $name, bool $required = true): array
    {
        return [
            'key' => 'ext_' . $name,
            'ok' => extension_loaded($name),
            'required' => $required,
            'current' => extension_loaded($name) ? 'yes' : 'no',
            'target' => $required ? 'required' : 'recommended',
        ];
    }

    private function gdFunctionCheck(string $function): array
    {
        return [
            'key' => 'gd_' . $function,
            'ok' => function_exists($function),
            'required' => true,
            'current' => function_exists($function) ? 'yes' : 'no',
            'target' => 'required',
        ];
    }

    private function transliteratorCheck(): array
    {
        return [
            'key' => 'intl_transliterator',
            'ok' => class_exists(\Transliterator::class),
            'required' => false,
            'current' => class_exists(\Transliterator::class) ? 'yes' : 'no',
            'target' => 'recommended',
        ];
    }

    private function writableCheck(string $rootPath, string $type): array
    {
        if ($type === 'config') {
            $configPath = rtrim($rootPath, '/') . '/config.php';
            $ok = is_file($configPath) ? is_writable($configPath) : is_writable(rtrim($rootPath, '/'));

            return [
                'key' => 'fs_config_writable',
                'ok' => $ok,
                'required' => true,
                'current' => $ok ? 'yes' : 'no',
                'target' => 'required',
            ];
        }

        $uploadsPath = rtrim($rootPath, '/') . '/uploads';
        $ok = is_dir($uploadsPath) ? is_writable($uploadsPath) : is_writable(rtrim($rootPath, '/'));

        return [
            'key' => 'fs_uploads_writable',
            'ok' => $ok,
            'required' => true,
            'current' => $ok ? 'yes' : 'no',
            'target' => 'required',
        ];
    }

    private function hasFailures(array $checks): bool
    {
        foreach ($checks as $check) {
            if (($check['ok'] ?? false) !== true) {
                return true;
            }
        }

        return false;
    }
}
