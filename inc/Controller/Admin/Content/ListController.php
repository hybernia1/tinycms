<?php
declare(strict_types=1);

namespace App\Controller\Admin\Content;

use App\Service\Support\PaginationConfig;

final class ListController extends BaseContentController
{
    public function list(callable $redirect): void
    {
        if (!$this->guardAdmin($redirect, false)) {
            return;
        }

        [$page, $perPage, $status, $query, $availableStatuses] = $this->resolveListQuery();

        $pagination = $this->content->paginate($page, $perPage, $status, $query);
        $statusCounts = $this->content->statusCounts($availableStatuses);
        $this->pages->adminContentList($pagination, PaginationConfig::allowed(), $status, $query, $availableStatuses, $statusCounts);
    }

    public function listApiV1(callable $redirect): void
    {
        if (!$this->guardAdmin($redirect, false)) {
            return;
        }

        [$page, $perPage, $status, $query, $availableStatuses] = $this->resolveListQuery();
        $pagination = $this->content->paginate($page, $perPage, $status, $query);
        $items = array_map([$this, 'mapListItem'], (array)($pagination['data'] ?? []));
        $statusCounts = $this->content->statusCounts($availableStatuses);

        $this->apiOk($items, $this->buildListMeta($pagination, $perPage, $status, $query, $statusCounts));
    }
}
