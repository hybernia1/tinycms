<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\AuthService;
use App\Service\ContentService;
use App\Service\CsrfService;
use App\Service\FlashService;
use App\Service\UserService;
use App\View\PageView;

final class AdminContentController
{
    private const PER_PAGE_ALLOWED = [10, 20, 50];
    private const FORM_STATE_KEY = 'admin_content_form_state';

    public function __construct(
        private PageView $pages,
        private AuthService $authService,
        private ContentService $content,
        private UserService $users,
        private FlashService $flash,
        private CsrfService $csrf
    ) {
    }

    public function list(callable $redirect): void
    {
        if (!$this->guard($redirect)) {
            return;
        }

        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = (int)($_GET['per_page'] ?? 10);
        $status = trim((string)($_GET['status'] ?? 'all'));
        $query = trim((string)($_GET['q'] ?? ''));

        if (!in_array($perPage, self::PER_PAGE_ALLOWED, true)) {
            $perPage = 10;
        }

        $availableStatuses = $this->content->statuses();
        if ($status !== 'all' && !in_array($status, $availableStatuses, true)) {
            $status = 'all';
        }

        $pagination = $this->content->paginate($page, $perPage, $status, $query);
        $this->pages->adminContentList($pagination, self::PER_PAGE_ALLOWED, $status, $query, $availableStatuses);
    }

    public function deleteSubmit(callable $redirect): void
    {
        if (!$this->guard($redirect) || !$this->guardCsrf($redirect)) {
            return;
        }

        $id = (int)($_POST['id'] ?? 0);

        if ($id <= 0) {
            $this->flash->add('error', 'Neplatné ID obsahu.');
            $redirect('admin/content');
        }

        $ok = $this->content->delete($id);
        $this->flash->add($ok ? 'success' : 'error', $ok ? 'Obsah smazán.' : 'Obsah se nepodařilo smazat.');
        $redirect('admin/content');
    }

    public function bulkActionSubmit(callable $redirect): void
    {
        if (!$this->guard($redirect) || !$this->guardCsrf($redirect)) {
            return;
        }

        $rawIds = (string)($_POST['ids'] ?? '');
        $action = (string)($_POST['action'] ?? '');
        $ids = array_filter(array_map('intval', explode(',', $rawIds)), static fn(int $v): bool => $v > 0);

        if ($ids === []) {
            $this->flash->add('info', 'Nebyly vybrány žádné záznamy.');
            $redirect('admin/content');
        }

        if ($action === 'delete') {
            $affected = $this->content->deleteMany($ids);
            $this->flash->add($affected > 0 ? 'success' : 'error', $affected > 0 ? "Smazáno $affected záznamů." : 'Vybrané záznamy se nepodařilo smazat.');
            $redirect('admin/content');
        }

        if ($action === 'publish') {
            $affected = $this->content->setStatusMany($ids, 'published');
            $this->flash->add($affected > 0 ? 'success' : 'info', $affected > 0 ? "Publikováno $affected záznamů." : 'Vybrané záznamy už byly publikované nebo nejsou dostupné.');
            $redirect('admin/content');
        }

        if ($action === 'draft') {
            $affected = $this->content->setStatusMany($ids, 'draft');
            $this->flash->add($affected > 0 ? 'success' : 'info', $affected > 0 ? "Přepnuto do draftu $affected záznamů." : 'Vybrané záznamy už byly v draftu nebo nejsou dostupné.');
            $redirect('admin/content');
        }

        $this->flash->add('info', 'Nebyla vybrána žádná hromadná akce.');
        $redirect('admin/content');
    }

    public function addForm(callable $redirect): void
    {
        if (!$this->guard($redirect)) {
            return;
        }

        $fallback = ['id' => null, 'name' => '', 'status' => 'draft', 'excerpt' => '', 'body' => '', 'created' => date('Y-m-d H:i:s'), 'updated' => null];
        $fallback['author'] = (int)($this->authService->auth()->id() ?? 0);
        $state = $this->consumeFormState('add', null);
        $statuses = $this->content->statuses();
        $this->pages->adminContentForm('add', $state['data'] ?? $fallback, $state['errors'] ?? [], $statuses, $this->users->authorOptions());
    }

    public function addSubmit(callable $redirect): void
    {
        if (!$this->guard($redirect) || !$this->guardCsrf($redirect)) {
            return;
        }

        $authorId = (int)($this->authService->auth()->id() ?? 0);
        $result = $this->content->save($_POST, $authorId);

        if (($result['success'] ?? false) === true) {
            $this->flash->add('success', 'Obsah vytvořen.');
            $newId = (int)($result['id'] ?? 0);
            $redirect($newId > 0 ? $this->editPath($newId) : 'admin/content');
        }

        $this->flash->add('error', 'Nepodařilo se uložit obsah.');
        $this->storeFormState('add', null, $_POST, $result['errors'] ?? []);
        $redirect('admin/content/add');
    }

    public function editForm(callable $redirect): void
    {
        if (!$this->guard($redirect)) {
            return;
        }

        $id = (int)($_GET['id'] ?? 0);
        $item = $this->content->find($id);

        if ($item === null) {
            $this->flash->add('info', 'Obsah nenalezen.');
            $redirect('admin/content');
        }

        $state = $this->consumeFormState('edit', $id);
        $statuses = $this->content->statuses();
        $this->pages->adminContentForm('edit', $state['data'] ?? $item, $state['errors'] ?? [], $statuses, $this->users->authorOptions());
    }

    public function editSubmit(callable $redirect): void
    {
        if (!$this->guard($redirect) || !$this->guardCsrf($redirect)) {
            return;
        }

        $id = (int)($_GET['id'] ?? 0);

        if ($id <= 0) {
            $this->flash->add('error', 'Neplatné ID obsahu.');
            $redirect('admin/content');
        }

        $authorId = (int)($this->authService->auth()->id() ?? 0);
        $result = $this->content->save($_POST, $authorId, $id);

        if (($result['success'] ?? false) === true) {
            $this->flash->add('success', 'Obsah upraven.');
            $redirect($this->editPath($id));
        }

        $this->flash->add('error', 'Nepodařilo se upravit obsah.');
        $this->storeFormState('edit', $id, array_merge($_POST, ['id' => $id]), $result['errors'] ?? []);
        $redirect('admin/content/edit?id=' . $id);
    }

    public function statusToggleSubmit(callable $redirect): void
    {
        if (!$this->guard($redirect) || !$this->guardCsrf($redirect)) {
            return;
        }

        $id = (int)($_POST['id'] ?? 0);
        $mode = (string)($_POST['mode'] ?? 'draft');

        if ($id <= 0) {
            $this->flash->add('error', 'Neplatné ID obsahu.');
            $redirect('admin/content');
        }

        if ($mode === 'publish') {
            $ok = $this->content->setStatus($id, 'published');
            $this->flash->add($ok ? 'success' : 'info', $ok ? 'Obsah publikován.' : 'Obsah už byl publikovaný nebo není dostupný.');
            $redirect('admin/content');
        }

        $ok = $this->content->setStatus($id, 'draft');
        $this->flash->add($ok ? 'success' : 'info', $ok ? 'Obsah přepnut do draftu.' : 'Obsah už byl v draftu nebo není dostupný.');
        $redirect('admin/content');
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
            $redirect('admin/content');
        }

        return true;
    }

    private function storeFormState(string $mode, ?int $id, array $data, array $errors): void
    {
        $this->ensureSession();
        $_SESSION[self::FORM_STATE_KEY] = [
            'mode' => $mode,
            'id' => $id,
            'data' => $data,
            'errors' => $errors,
        ];
    }

    private function consumeFormState(string $mode, ?int $id): ?array
    {
        $this->ensureSession();
        $state = $_SESSION[self::FORM_STATE_KEY] ?? null;

        if (!is_array($state)) {
            return null;
        }

        unset($_SESSION[self::FORM_STATE_KEY]);

        if (($state['mode'] ?? null) !== $mode || ($state['id'] ?? null) !== $id) {
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

    private function editPath(int $id): string
    {
        return 'admin/content/edit?id=' . $id;
    }
}
