<?php
declare(strict_types=1);

namespace App\Controller\Api;

use App\Controller\Admin\Settings as AdminSettings;

final class Settings
{
    public function __construct(private AdminSettings $controller)
    {
    }

    public function submitApiV1(callable $redirect): void
    {
        $this->controller->submitApiV1($redirect);
    }
}
