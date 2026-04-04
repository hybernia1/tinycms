<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\Feature\AuthService;
use App\Service\Feature\TermService;
use App\Service\Support\CsrfService;
use App\Service\Support\FlashService;
use App\View\PageView;

final class AdminTermController extends BaseAdminController
{
    private const PER_PAGE_ALLOWED = [10, 20, 50];
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

        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = (int)($_GET['per_page'] ?? 10);
        $query = trim((string)($_GET['q'] ?? ''));

        if (!in_array($perPage, self::PER_PAGE_ALLOWED, true)) {
            $perPage = 10;
        }

        $pagination = $this->terms->paginate($page, $perPage, $query);
        if ($this->wantsJson()) {
            $items = array_map([$this, 'mapListItem'], (array)($pagination['data'] ?? []));
            $this->jsonSuccess([
                'items' => $items,
                'page' => (int)($pagination['page'] ?? 1),
                'per_page' => (int)($pagination['per_page'] ?? $perPage),
                'total_pages' => (int)($pagination['total_pages'] ?? 1),
                'query' => $query,
            ]);
            return;
        }

        $this->pages->adminTermList($pagination, self::PER_PAGE_ALLOWED, $query);
    }

    public function listApiV1(callable $redirect): void
    {
        if (!$this->guardAdmin($redirect, false)) {
            return;
        }

        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = (int)($_GET['per_page'] ?? 10);
        $query = trim((string)($_GET['q'] ?? ''));
        if (!in_array($perPage, self::PER_PAGE_ALLOWED, true)) {
            $perPage = 10;
        }

        $pagination = $this->terms->paginate($page, $perPage, $query);
        $items = array_map([$this, 'mapListItem'], (array)($pagination['data'] ?? []));

        $this->respondJson([
            'ok' => true,
            'data' => $items,
            'meta' => [
                'page' => (int)($pagination['page'] ?? 1),
                'per_page' => (int)($pagination['per_page'] ?? $perPage),
                'total_pages' => (int)($pagination['total_pages'] ?? 1),
                'query' => $query,
            ],
        ]);
    }

    public function suggest(callable $redirect): void
    {
        if (!$this->guardAdmin($redirect, false)) {
            return;
        }

        $query = trim((string)($_GET['q'] ?? ''));
        $this->jsonSuccess([
            'items' => $this->terms->search($query, 15),
        ]);
    }

    public function addForm(callable $redirect): void
    {
        if (!$this->guardAdmin($redirect, false)) {
            return;
        }

        $fallback = ['id' => null, 'name' => '', 'body' => '', 'created' => date('Y-m-d H:i:s'), 'updated' => null];
        $state = $this->consumeFormState(self::FORM_STATE_KEY, 'add', null);
        $this->pages->adminTermForm('add', $state['data'] ?? $fallback, $state['errors'] ?? []);
    }

    public function addSubmit(callable $redirect): void
    {
        if (
            !$this->guardAdmin($redirect, false)
            || !$this->guardCsrf($redirect, 'admin/terms', 'Neplatný CSRF token.')
        ) {
            return;
        }

        $result = $this->terms->save($_POST);
        if (($result['success'] ?? false) === true) {
            $newId = (int)($result['id'] ?? 0);
            $this->flash->add('success', 'Štítek vytvořen.');
            $redirect($newId > 0 ? $this->editPath($newId) : 'admin/terms');
            return;
        }

        $this->flash->add('error', 'Nepodařilo se uložit štítek.');
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
            $this->flash->add('info', 'Štítek nenalezen.');
            $redirect('admin/terms');
            return;
        }

        $state = $this->consumeFormState(self::FORM_STATE_KEY, 'edit', $id);
        $this->pages->adminTermForm('edit', $state['data'] ?? $item, $state['errors'] ?? []);
    }

    public function editSubmit(callable $redirect): void
    {
        if (
            !$this->guardAdmin($redirect, false)
            || !$this->guardCsrf($redirect, 'admin/terms', 'Neplatný CSRF token.')
        ) {
            return;
        }

        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            $this->flash->add('error', 'Neplatné ID štítku.');
            $redirect('admin/terms');
            return;
        }

        $result = $this->terms->save($_POST, $id);
        if (($result['success'] ?? false) === true) {
            $this->flash->add('success', 'Štítek upraven.');
            $redirect($this->editPath($id));
            return;
        }

        $this->flash->add('error', 'Nepodařilo se upravit štítek.');
        $this->storeFormState(self::FORM_STATE_KEY, 'edit', $id, array_merge($_POST, ['id' => $id]), $result['errors'] ?? []);
        $redirect($this->editPath($id));
    }

    public function deleteSubmit(callable $redirect): void
    {
        if (
            !$this->guardAdmin($redirect, false)
            || !$this->guardCsrf($redirect, 'admin/terms', 'Neplatný CSRF token.')
        ) {
            return;
        }

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            if ($this->wantsJson()) {
                $this->jsonError('Neplatné ID štítku.');
                return;
            }
            $this->flash->add('error', 'Neplatné ID štítku.');
            $redirect('admin/terms');
            return;
        }

        $ok = $this->terms->delete($id);
        if ($this->wantsJson()) {
            if ($ok) {
                $this->jsonSuccess(['id' => $id]);
                return;
            }
            $this->jsonError('Štítek se nepodařilo smazat.');
            return;
        }

        $this->flash->add($ok ? 'success' : 'error', $ok ? 'Štítek smazán.' : 'Štítek se nepodařilo smazat.');
        $redirect('admin/terms');
    }

    public function deleteApiV1(callable $redirect, int $id): void
    {
        if (
            !$this->guardAdmin($redirect, false)
            || !$this->guardCsrf($redirect, 'admin/terms', 'Neplatný CSRF token.')
        ) {
            return;
        }

        if ($id <= 0) {
            $this->respondJson(['ok' => false, 'error' => ['code' => 'INVALID_ID', 'message' => 'Neplatné ID štítku.']], 422);
            return;
        }

        if (!$this->terms->delete($id)) {
            $this->respondJson(['ok' => false, 'error' => ['code' => 'DELETE_FAILED', 'message' => 'Štítek se nepodařilo smazat.']], 422);
            return;
        }

        $this->respondJson(['ok' => true, 'data' => ['id' => $id]]);
    }

    private function editPath(int $id): string
    {
        return 'admin/terms/edit?id=' . $id;
    }

    private function mapListItem(array $row): array
    {
        return [
            'id' => (int)($row['id'] ?? 0),
            'name' => (string)($row['name'] ?? ''),
            'body' => (string)($row['body'] ?? ''),
            'created' => (string)($row['created'] ?? ''),
        ];
    }
}
