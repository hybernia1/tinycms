<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\AuthService;
use App\Service\ContentService;
use App\Service\ContentTypeService;
use App\Service\CsrfService;
use App\Service\FlashService;
use App\View\PageView;

final class AdminContentController
{
    private const PER_PAGE_ALLOWED = [10, 20, 50];
    private const FORM_STATE_KEY = 'admin_content_form_state';

    public function __construct(
        private PageView $pages,
        private AuthService $authService,
        private ContentService $content,
        private ContentTypeService $contentTypes,
        private FlashService $flash,
        private CsrfService $csrf
    ) {
    }

    public function list(callable $redirect): void
    {
        if (!$this->guard($redirect)) {
            return;
        }

        $type = $this->resolveType();
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = (int)($_GET['per_page'] ?? 10);
        $status = trim((string)($_GET['status'] ?? 'all'));
        $query = trim((string)($_GET['q'] ?? ''));

        if (!in_array($perPage, self::PER_PAGE_ALLOWED, true)) {
            $perPage = 10;
        }

        $availableStatuses = $this->content->statusesForType($type['type']);
        if ($status !== 'all' && !in_array($status, $availableStatuses, true)) {
            $status = 'all';
        }

        $pagination = $this->content->paginate($type['type'], $page, $perPage, $status, $query);
        $this->pages->adminContentList($pagination, self::PER_PAGE_ALLOWED, $status, $query, $type, $availableStatuses);
    }

    public function deleteSubmit(callable $redirect): void
    {
        if (!$this->guard($redirect) || !$this->guardCsrf($redirect)) {
            return;
        }

        $type = $this->resolveType();
        $id = (int)($_POST['id'] ?? 0);

        if ($id <= 0) {
            $this->flash->add('error', 'Neplatné ID obsahu.');
            $redirect('admin/content?type=' . urlencode($type['type']));
        }

        $ok = $this->content->delete($id, $type['type']);
        $this->flash->add($ok ? 'success' : 'error', $ok ? 'Obsah smazán.' : 'Obsah se nepodařilo smazat.');
        $redirect('admin/content?type=' . urlencode($type['type']));
    }

    public function bulkActionSubmit(callable $redirect): void
    {
        if (!$this->guard($redirect) || !$this->guardCsrf($redirect)) {
            return;
        }

        $type = $this->resolveType();
        $rawIds = (string)($_POST['ids'] ?? '');
        $action = (string)($_POST['action'] ?? '');
        $ids = array_filter(array_map('intval', explode(',', $rawIds)), static fn(int $v): bool => $v > 0);

        if ($ids === []) {
            $this->flash->add('info', 'Nebyly vybrány žádné záznamy.');
            $redirect('admin/content?type=' . urlencode($type['type']));
        }

        if ($action === 'delete') {
            $affected = $this->content->deleteMany($ids, $type['type']);
            $this->flash->add($affected > 0 ? 'success' : 'error', $affected > 0 ? "Smazáno $affected záznamů." : 'Vybrané záznamy se nepodařilo smazat.');
            $redirect('admin/content?type=' . urlencode($type['type']));
        }

        $this->flash->add('info', 'Nebyla vybrána žádná hromadná akce.');
        $redirect('admin/content?type=' . urlencode($type['type']));
    }

    public function addForm(callable $redirect): void
    {
        if (!$this->guard($redirect)) {
            return;
        }

        $type = $this->resolveType();
        $fallback = ['id' => null, 'type' => $type['type'], 'name' => '', 'status' => 'draft', 'excerpt' => '', 'body' => ''];
        $state = $this->consumeFormState('add', null, $type['type']);
        $statuses = $this->content->statusesForType($type['type']);
        $this->pages->adminContentForm('add', $state['data'] ?? $fallback, $state['errors'] ?? [], $type, $statuses);
    }

    public function addSubmit(callable $redirect): void
    {
        if (!$this->guard($redirect) || !$this->guardCsrf($redirect)) {
            return;
        }

        $type = $this->resolveType();
        $authorId = (int)($this->authService->auth()->id() ?? 0);
        $result = $this->content->save($_POST, $authorId, $type['type']);

        if (($result['success'] ?? false) === true) {
            $this->flash->add('success', 'Obsah vytvořen.');
            $redirect('admin/content?type=' . urlencode($type['type']));
        }

        $this->flash->add('error', 'Nepodařilo se uložit obsah.');
        $this->storeFormState('add', null, $type['type'], $_POST, $result['errors'] ?? []);
        $redirect('admin/content/add?type=' . urlencode($type['type']));
    }

    public function editForm(callable $redirect): void
    {
        if (!$this->guard($redirect)) {
            return;
        }

        $type = $this->resolveType();
        $id = (int)($_GET['id'] ?? 0);
        $item = $this->content->find($id, $type['type']);

        if ($item === null) {
            $this->flash->add('info', 'Obsah nenalezen.');
            $redirect('admin/content?type=' . urlencode($type['type']));
        }

        $state = $this->consumeFormState('edit', $id, $type['type']);
        $statuses = $this->content->statusesForType($type['type']);
        $this->pages->adminContentForm('edit', $state['data'] ?? $item, $state['errors'] ?? [], $type, $statuses);
    }

    public function editSubmit(callable $redirect): void
    {
        if (!$this->guard($redirect) || !$this->guardCsrf($redirect)) {
            return;
        }

        $type = $this->resolveType();
        $id = (int)($_GET['id'] ?? 0);

        if ($id <= 0) {
            $this->flash->add('error', 'Neplatné ID obsahu.');
            $redirect('admin/content?type=' . urlencode($type['type']));
        }

        $authorId = (int)($this->authService->auth()->id() ?? 0);
        $result = $this->content->save($_POST, $authorId, $type['type'], $id);

        if (($result['success'] ?? false) === true) {
            $this->flash->add('success', 'Obsah upraven.');
            $redirect('admin/content?type=' . urlencode($type['type']));
        }

        $this->flash->add('error', 'Nepodařilo se upravit obsah.');
        $this->storeFormState('edit', $id, $type['type'], array_merge($_POST, ['id' => $id]), $result['errors'] ?? []);
        $redirect('admin/content/edit?id=' . $id . '&type=' . urlencode($type['type']));
    }

    private function guard(callable $redirect): bool
    {
        if (!$this->authService->auth()->check()) {
            $redirect('login');
        }

        if (!$this->authService->canAccessAdmin()) {
            $redirect('');
        }

        return true;
    }

    private function guardCsrf(callable $redirect): bool
    {
        $token = (string)($_POST['_csrf'] ?? '');

        if ($token === '' || !$this->csrf->verify($token)) {
            $this->flash->add('error', 'Neplatný CSRF token.');
            $redirect('admin/content?type=' . urlencode($this->resolveType()['type']));
        }

        return true;
    }

    private function resolveType(): array
    {
        return $this->contentTypes->resolve((string)($_GET['type'] ?? $_POST['type'] ?? ''));
    }

    private function storeFormState(string $mode, ?int $id, string $type, array $data, array $errors): void
    {
        $this->ensureSession();
        $_SESSION[self::FORM_STATE_KEY] = [
            'mode' => $mode,
            'id' => $id,
            'type' => $type,
            'data' => $data,
            'errors' => $errors,
        ];
    }

    private function consumeFormState(string $mode, ?int $id, string $type): ?array
    {
        $this->ensureSession();
        $state = $_SESSION[self::FORM_STATE_KEY] ?? null;

        if (!is_array($state)) {
            return null;
        }

        unset($_SESSION[self::FORM_STATE_KEY]);

        if (($state['mode'] ?? null) !== $mode || ($state['id'] ?? null) !== $id || ($state['type'] ?? null) !== $type) {
            return null;
        }

        return $state;
    }

    private function ensureSession(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }
}
