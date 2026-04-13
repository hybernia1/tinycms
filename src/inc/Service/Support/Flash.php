<?php
declare(strict_types=1);

namespace App\Service\Support;

final class Flash
{
    public function add(string $type, string $message): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $_SESSION['flash'][] = [
            'type' => $type,
            'message' => $message,
        ];
    }

    public function consume(): array
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $messages = $_SESSION['flash'] ?? [];
        unset($_SESSION['flash']);

        return is_array($messages) ? $messages : [];
    }
}
