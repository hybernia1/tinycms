<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\Feature\AuthService;
use App\Service\Feature\TermService;
use App\Service\Support\CsrfService;
use App\Service\Support\FlashService;
use App\Service\Support\I18n;
use App\Service\Support\PaginationConfig;
use App\View\PageView;

final class AdminTermController extends BaseAdminController
{
    private const FORM_STATE_KEY = 'admin_term_form_state';

    public function __construct(
        private PageView $pages,
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
        $this->pages->adminTermList($pagination, PaginationConfig::allowed(), $status, $query, $statusCounts);
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
        $state = $this->consumeFormState(self::FORM_STATE_KEY, 'add', null);
        $this->pages->adminTermForm('add', $state['data'] ?? $fallback, $state['errors'] ?? [], []);
    }

    public function addSubmit(callable $redirect): void
    {
        if (!$this->guardAdminCsrf($redirect, 'admin/terms', I18n::t('common.invalid_csrf'), false)) {
            return;
        }

        $result = $this->terms->save($_POST);
        if (($result['success'] ?? false) === true) {
            $newId = (int)($result['id'] ?? 0);
            $this->flash->add('success', I18n::t('terms.created'));
            $redirect($newId > 0 ? $this->buildEditPath('admin/terms', $newId) : 'admin/terms');
            return;
        }

        $this->flash->add('error', I18n::t('terms.save_failed'));
        $this->storeFormState(self::FORM_STATE_KEY, 'add', null, $_POST, $result['errors'] ?? []);
        $redirect('admin/terms/add');
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

        $state = $this->consumeFormState(self::FORM_STATE_KEY, 'edit', $id);
        $this->pages->adminTermForm('edit', $state['data'] ?? $item, $state['errors'] ?? [], $this->terms->contentUsages($id));
    }

    public function editSubmit(callable $redirect): void
    {
        if (!$this->guardAdminCsrf($redirect, 'admin/terms', I18n::t('common.invalid_csrf'), false)) {
            return;
        }

        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            $this->flash->add('error', I18n::t('terms.invalid_id'));
            $redirect('admin/terms');
            return;
        }

        $result = $this->terms->save($_POST, $id);
        if (($result['success'] ?? false) === true) {
            $this->flash->add('success', I18n::t('terms.updated'));
            $redirect($this->buildEditPath('admin/terms', $id));
            return;
        }

        $this->flash->add('error', I18n::t('terms.update_failed'));
        $this->storeFormState(self::FORM_STATE_KEY, 'edit', $id, array_merge($_POST, ['id' => $id]), $result['errors'] ?? []);
        $redirect($this->buildEditPath('admin/terms', $id));
    }

    public function deleteApiV1(callable $redirect, int $id): void
    {
        if (!$this->guardAdminCsrf($redirect, 'admin/terms', I18n::t('common.invalid_csrf'), false)) {
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
