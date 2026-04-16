<?php
declare(strict_types=1);

namespace App\Controller\Api;

use App\Controller\Admin\BaseAdmin;
use App\Service\Application\Auth;
use App\Service\Application\Term as TermService;
use App\Service\Support\Csrf;
use App\Service\Support\Flash;
use App\Service\Support\I18n;

final class Term extends BaseAdmin
{
    public function __construct(
        Auth $authService,
        private TermService $terms,
        Flash $flash,
        Csrf $csrf
    ) {
        parent::__construct($authService, $flash, $csrf);
    }

    public function listApiV1(callable $redirect): void
    {
        if (!$this->guardApiAdmin()) {
            return;
        }

        [$page, $perPage, $status, $query] = $this->resolveListQuery();
        $pagination = $this->terms->paginate($page, $perPage, $query, $status);
        $items = array_map([$this, 'mapListItem'], (array)($pagination['data'] ?? []));
        $statusCounts = $this->terms->statusCounts();

        $this->apiOk($items, $this->buildListMeta($pagination, $perPage, $status, $query, $statusCounts));
    }

    public function suggestApiV1(callable $redirect): void
    {
        if (!$this->guardApiAdmin()) {
            return;
        }

        $query = trim((string)($_GET['q'] ?? ''));
        $this->apiOk($this->terms->search($query, 15), [
            'query' => $query,
        ]);
    }

    public function addApiV1(callable $redirect): void
    {
        if (!$this->guardApiAdminCsrfInvalid()) {
            return;
        }

        $result = $this->terms->save($_POST);
        if (($result['success'] ?? false) === true) {
            $newId = (int)($result['id'] ?? 0);
            $this->apiOk([
                'redirect' => $newId > 0 ? $this->buildEditPath('admin/terms', $newId) : $this->buildPath('admin/terms'),
                'message' => I18n::t('terms.created'),
            ]);
            return;
        }

        $this->apiError('SAVE_FAILED', I18n::t('terms.save_failed'), 422, [
            'errors' => $result['errors'] ?? [],
        ]);
    }

    public function editApiV1(callable $redirect, int $id): void
    {
        if (!$this->guardApiAdminCsrfInvalid()) {
            return;
        }

        if (!$this->requirePositiveId($id, 'INVALID_ID', I18n::t('terms.invalid_id'))) {
            return;
        }

        $result = $this->terms->save($_POST, $id);
        if (($result['success'] ?? false) === true) {
            $this->apiOk([
                'message' => I18n::t('terms.updated'),
            ]);
            return;
        }

        $this->apiError('UPDATE_FAILED', I18n::t('terms.update_failed'), 422, [
            'errors' => $result['errors'] ?? [],
        ]);
    }

    public function deleteApiV1(callable $redirect, int $id): void
    {
        if (!$this->guardApiAdminCsrfInvalid()) {
            return;
        }

        if (!$this->requirePositiveId($id, 'INVALID_ID', I18n::t('terms.invalid_id'))) {
            return;
        }

        if (!$this->terms->delete($id)) {
            $this->apiError('DELETE_FAILED', I18n::t('terms.delete_failed'));
            return;
        }

        $this->apiOk(['id' => $id]);
    }

    private function mapListItem(array $row): array
    {
        return [
            'id' => (int)($row['id'] ?? 0),
            'name' => (string)($row['name'] ?? ''),
            'created' => (string)($row['created'] ?? ''),
            'created_label' => $this->formatDateTime((string)($row['created'] ?? '')),
        ];
    }

    private function resolveListQuery(): array
    {
        [$page, $perPage, $query] = $this->resolvePaginationQuery();
        $status = $this->resolveStatusFilter(['all', 'unassigned']);

        return [$page, $perPage, $status, $query];
    }
}
