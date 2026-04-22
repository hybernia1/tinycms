<?php
declare(strict_types=1);

namespace App\Service\Support;

final class Csrf
{
    private const SESSION_KEY = '_csrf_token';

    public function token(): string
    {
        $this->ensureSession();

        if (!isset($_SESSION[self::SESSION_KEY]) || !is_string($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        }

        return $_SESSION[self::SESSION_KEY];
    }

    public function field(string $name = '_csrf'): string
    {
        return '<input type="hidden" name="' . esc_attr($name) . '" value="' . esc_attr($this->token()) . '">';
    }

    public function verify(?string $token): bool
    {
        $this->ensureSession();
        $current = $_SESSION[self::SESSION_KEY] ?? '';
        return is_string($current) && is_string($token) && $token !== '' && hash_equals($current, $token);
    }

    private function ensureSession(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }
}
