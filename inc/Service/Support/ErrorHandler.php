<?php
declare(strict_types=1);

namespace App\Service\Support;

use PDOException;
use RuntimeException;
use Throwable;

final class ErrorHandler
{
    public function handle(Throwable $e): void
    {
        http_response_code(500);

        if ($this->isDebug()) {
            $this->renderDebug($e);
            return;
        }

        echo htmlspecialchars($this->friendlyMessage($e), ENT_QUOTES, 'UTF-8');
    }

    private function isDebug(): bool
    {
        return defined('APP_DEBUG') && APP_DEBUG === true;
    }

    private function friendlyMessage(Throwable $e): string
    {
        $dbMessage = $this->databaseMessage($e);

        if ($dbMessage !== null) {
            return $dbMessage;
        }

        return 'Došlo k neočekávané chybě. Zkuste to prosím později.';
    }

    private function databaseMessage(Throwable $e): ?string
    {
        if ($e instanceof RuntimeException && $e->getMessage() === 'Database connection failed.' && $e->getPrevious() instanceof PDOException) {
            return $this->pdoConnectionMessage($e->getPrevious());
        }

        if ($e instanceof PDOException) {
            return $this->pdoConnectionMessage($e);
        }

        return null;
    }

    private function pdoConnectionMessage(PDOException $e): string
    {
        $driverCode = (int)($e->errorInfo[1] ?? 0);
        $message = mb_strtolower($e->getMessage());

        if ($driverCode === 1044 || $driverCode === 1045 || str_contains($message, 'access denied')) {
            return 'Nelze se připojit k databázi: neplatné přihlašovací údaje nebo oprávnění.';
        }

        if (str_contains($message, 'php_network_getaddresses') || str_contains($message, 'getaddrinfo')) {
            return 'Nelze se připojit k databázi: nepodařilo se přeložit DB host.';
        }

        if ($driverCode === 2002 || str_contains($message, 'connection refused') || str_contains($message, 'timed out')) {
            return 'Nelze se připojit k databázi: server není dostupný.';
        }

        return 'Nelze se připojit k databázi.';
    }

    private function renderDebug(Throwable $e): void
    {
        $message = htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        $trace = htmlspecialchars($e->getTraceAsString(), ENT_QUOTES, 'UTF-8');

        echo '<h1>Application Error</h1>';
        echo '<p><strong>' . $message . '</strong></p>';
        echo '<pre>' . $trace . '</pre>';
    }
}
