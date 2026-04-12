<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\Feature\AuthService;
use App\Service\Feature\TermService;
use App\Service\Support\CsrfService;
use App\Service\Support\FlashService;
use App\Service\Support\I18n;
use App\View\AdminView;

final class AdminTermController extends BaseAdminController
{
    public function __construct(
        private AdminView $pages,
        AuthService $authService,
        private TermService $terms,
        FlashService $flash,
        CsrfService $csrf
    ) {
        parent::__construct($authService, $flash, $csrf);
    }

    public function list(callable $redirect): void
    {
        if (!$this->guardAdmin($redirect, false)) {
            return;
        }

        [$page, $perPage, $status, $query] = $this->resolveListQuery();
        $pagination = $this->terms->paginate($page, $perPage, $query, $status);
        $statusCounts = $this->terms->statusCounts();
        $this->pages->adminTermList($pagination, $status, $query, $statusCounts);
    }

    public function listApiV1(callable $redirect): void
    {
        if (!$this->guardAdmin($redirect, false)) {
            return;
        }

        [$page, $perPage, $status, $query] = $this->resolveListQuery();
        $pagination = $this->terms->paginate($page, $perPage, $query, $status);
        $items = array_map([$this, 'mapListItem'], (array)($pagination['data'] ?? []));
        $statusCounts = $this->terms->statusCounts();

        $this->apiOk($items, $this->buildListMeta($pagination, $perPage, $status, $query, $statusCounts));
    }

    public function suggest(callable $redirect): void
    {
        if (!$this->guardAdmin($redirect, false)) {
            return;
        }

        $query = trim((string)($_GET['q'] ?? ''));
        $this->apiOk($this->terms->search($query, 15), [
            'query' => $query,
        ]);
    }

    public function addForm(callable $redirect): void
    {
        if (!$this->guardAdmin($redirect, false)) {
            return;
        }

        $fallback = ['id' => null, 'name' => '', 'created' => date('Y-m-d H:i:s'), 'updated' => null];
        $this->pages->adminTermForm('add', $fallback, [], []);
    }

    public function addApiV1(callable $redirect): void
    {
        if (!$this->guardApiAdminCsrf(I18n::t('common.invalid_csrf'))) {
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

    public function editForm(callable $redirect): void
    {
        if (!$this->guardAdmin($redirect, false)) {
            return;
        }

        $id = (int)($_GET['id'] ?? 0);
        $item = $this->terms->find($id);

        if ($item === null) {
            $this->flash->add('info', I18n::t('terms.not_found'));
            $redirect('admin/terms');
            return;
        }

        $this->pages->adminTermForm('edit', $item, [], $this->terms->contentUsages($id));
    }

    public function editApiV1(callable $redirect, int $id): void
    {
        if (!$this->guardApiAdminCsrf(I18n::t('common.invalid_csrf'))) {
            return;
        }

        if ($id <= 0) {
            $this->apiError('INVALID_ID', I18n::t('terms.invalid_id'));
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
        if (!$this->guardApiAdminCsrf(I18n::t('common.invalid_csrf'))) {
            return;
        }

        if ($id <= 0) {
            $this->apiError('INVALID_ID', I18n::t('terms.invalid_id'));
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
