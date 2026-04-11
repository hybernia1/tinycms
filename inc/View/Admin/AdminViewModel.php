<?php
declare(strict_types=1);

namespace App\View\Admin;

final class AdminViewModel
{
    public string $pageTitle;
    public array $adminMenu;
    public array $headerAction;
    public array $payload;

    public function __construct(string $pageTitle, array $adminMenu, array $headerAction, array $payload = [])
    {
        $this->pageTitle = $pageTitle;
        $this->adminMenu = $adminMenu;
        $this->headerAction = $headerAction;
        $this->payload = $payload;
    }

    public static function fromArray(array $data): self
    {
        $pageTitle = (string)($data['pageTitle'] ?? 'Admin');
        $adminMenu = is_array($data['adminMenu'] ?? null) ? $data['adminMenu'] : [];
        $headerAction = is_array($data['headerAction'] ?? null) ? $data['headerAction'] : [];

        unset($data['pageTitle'], $data['adminMenu'], $data['headerAction']);

        return new self($pageTitle, $adminMenu, $headerAction, $data);
    }
}
