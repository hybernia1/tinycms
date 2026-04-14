<?php
declare(strict_types=1);

namespace App\Controller\Api;

use App\Controller\Admin\Term as AdminTerm;

final class Term
{
    public function __construct(private AdminTerm $controller)
    {
    }

    public function listApiV1(callable $redirect): void { $this->controller->listApiV1($redirect); }
    public function addApiV1(callable $redirect): void { $this->controller->addApiV1($redirect); }
    public function editApiV1(callable $redirect, int $id): void { $this->controller->editApiV1($redirect, $id); }
    public function deleteApiV1(callable $redirect, int $id): void { $this->controller->deleteApiV1($redirect, $id); }
    public function suggest(callable $redirect): void { $this->controller->suggest($redirect); }
}
