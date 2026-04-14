<?php
declare(strict_types=1);

namespace App\Controller\Api;

use App\Controller\Admin\Content as AdminContent;

final class Content
{
    public function __construct(private AdminContent $controller)
    {
    }

    public function listApiV1(callable $redirect): void { $this->controller->listApiV1($redirect); }
    public function deleteApiV1(callable $redirect, int $id): void { $this->controller->deleteApiV1($redirect, $id); }
    public function restoreApiV1(callable $redirect, int $id): void { $this->controller->restoreApiV1($redirect, $id); }
    public function statusApiV1(callable $redirect, int $id): void { $this->controller->statusApiV1($redirect, $id); }
    public function addApiV1(callable $redirect): void { $this->controller->addApiV1($redirect); }
    public function editApiV1(callable $redirect, int $id): void { $this->controller->editApiV1($redirect, $id); }
    public function draftInitApiV1(callable $redirect): void { $this->controller->draftInitApiV1($redirect); }
    public function autosaveApiV1(callable $redirect): void { $this->controller->autosaveApiV1($redirect); }
    public function linkTitleApiV1(callable $redirect): void { $this->controller->linkTitleApiV1($redirect); }
}
