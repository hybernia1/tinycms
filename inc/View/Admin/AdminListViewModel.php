<?php
declare(strict_types=1);

namespace App\View\Admin;

use App\Service\Support\PaginationConfig;

final class AdminListViewModel
{
    public array $items;
    public int $page;
    public int $perPage;
    public int $totalPages;
    public string $status;
    public string $query;
    public array $allowedPerPage;
    public array $statusCounts;

    public function __construct(
        array $items,
        int $page,
        int $perPage,
        int $totalPages,
        string $status,
        string $query,
        array $allowedPerPage,
        array $statusCounts
    ) {
        $this->items = $items;
        $this->page = $page;
        $this->perPage = $perPage;
        $this->totalPages = $totalPages;
        $this->status = $status;
        $this->query = $query;
        $this->allowedPerPage = $allowedPerPage;
        $this->statusCounts = $statusCounts;
    }

    public static function fromRaw(array $pagination, array $allowedPerPage, string $status, string $query, array $statusCounts): self
    {
        return new self(
            (array)($pagination['data'] ?? []),
            max(1, (int)($pagination['page'] ?? 1)),
            (int)($pagination['per_page'] ?? PaginationConfig::perPage()),
            max(1, (int)($pagination['total_pages'] ?? 1)),
            $status !== '' ? $status : 'all',
            $query,
            $allowedPerPage,
            $statusCounts
        );
    }
}
