<?php
declare(strict_types=1);

namespace App\Service\Support;

use PDOException;
use RuntimeException;
use Throwable;

final class ErrorHandler
{
    private Escaper $escaper;

    public function __construct()
    {
        $this->escaper = new Escaper();
    }

    public function handle(Throwable $e): void
    {
        http_response_code(500);

        if ($this->isDebug()) {
            $this->renderDebug($e);
            return;
        }

        echo $this->escaper->html($this->friendlyMessage($e));
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

        return I18n::t('errors.unexpected');
    }

    private function databaseMessage(Throwable $e): ?string
    {
        if ($e instanceof PDOException) {
            return 'Database error';
        }

        if ($e instanceof RuntimeException && $e->getMessage() === 'Database connection failed.' && $e->getPrevious() instanceof PDOException) {
            return 'Database error';
        }

        return null;
    }

    private function renderDebug(Throwable $e): void
    {
        $message = $this->escaper->html($e->getMessage());
        $trace = $this->escaper->html($e->getTraceAsString());

        echo '<h1>Application Error</h1>';
        echo '<p><strong>' . $message . '</strong></p>';
        echo '<pre>' . $trace . '</pre>';
    }
}
