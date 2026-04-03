<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\AuthService;
use App\Service\ContentService;
use App\Service\CsrfService;
use App\Service\FlashService;
use App\Service\UserService;
use App\View\PageView;

final class AdminContentController extends BaseAdminController
{
    private const PER_PAGE_ALLOWED = [10, 20, 50];
    private const FORM_STATE_KEY = 'admin_content_form_state';

    public function __construct(
        private PageView $pages,
        AuthService $authService,
        private ContentService $content,
        private UserService $users,
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
        if (
            !$this->guardAdmin($redirect, false)
            || !$this->guardCsrf($redirect, 'admin/content', 'Neplatný CSRF token.')
        ) {
            return;
        }

        $id = (int)($_POST['id'] ?? 0);

        if ($id <= 0) {
            $this->flash->add('error', 'Neplatné ID obsahu.');
            $redirect('admin/content');
            return;
        }

        $ok = $this->content->delete($id);
        $this->flash->add($ok ? 'success' : 'error', $ok ? 'Obsah smazán.' : 'Obsah se nepodařilo smazat.');
        $redirect('admin/content');
    }

    public function addForm(callable $redirect): void
    {
        if (!$this->guardAdmin($redirect, false)) {
            return;
        }

        $fallback = ['id' => null, 'name' => '', 'status' => 'draft', 'excerpt' => '', 'body' => '', 'created' => date('Y-m-d H:i:s'), 'updated' => null];
        $fallback['author'] = (int)($this->authService->auth()->id() ?? 0);
        $state = $this->consumeFormState(self::FORM_STATE_KEY, 'add', null);
        $statuses = $this->content->statuses();
        $this->pages->adminContentForm('add', $state['data'] ?? $fallback, $state['errors'] ?? [], $statuses, $this->users->authorOptions());
    }

    public function addSubmit(callable $redirect): void
    {
        if (
            !$this->guardAdmin($redirect, false)
            || !$this->guardCsrf($redirect, 'admin/content', 'Neplatný CSRF token.')
        ) {
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
        $this->storeFormState(self::FORM_STATE_KEY, 'add', null, $_POST, $result['errors'] ?? []);
        $redirect('admin/content/add');
    }

    public function editForm(callable $redirect): void
    {
        if (!$this->guardAdmin($redirect, false)) {
            return;
        }

        $id = (int)($_GET['id'] ?? 0);
        $item = $this->content->find($id);

        if ($item === null) {
            $this->flash->add('info', 'Obsah nenalezen.');
            $redirect('admin/content');
            return;
        }

        $state = $this->consumeFormState(self::FORM_STATE_KEY, 'edit', $id);
        $statuses = $this->content->statuses();
        $this->pages->adminContentForm('edit', $state['data'] ?? $item, $state['errors'] ?? [], $statuses, $this->users->authorOptions());
    }

    public function editSubmit(callable $redirect): void
    {
        if (
            !$this->guardAdmin($redirect, false)
            || !$this->guardCsrf($redirect, 'admin/content', 'Neplatný CSRF token.')
        ) {
            return;
        }

        $id = (int)($_GET['id'] ?? 0);

        if ($id <= 0) {
            $this->flash->add('error', 'Neplatné ID obsahu.');
            $redirect('admin/content');
            return;
        }

        $authorId = (int)($this->authService->auth()->id() ?? 0);
        $result = $this->content->save($_POST, $authorId, $id);

        if (($result['success'] ?? false) === true) {
            $this->flash->add('success', 'Obsah upraven.');
            $redirect($this->editPath($id));
        }

        $this->flash->add('error', 'Nepodařilo se upravit obsah.');
        $this->storeFormState(self::FORM_STATE_KEY, 'edit', $id, array_merge($_POST, ['id' => $id]), $result['errors'] ?? []);
        $redirect('admin/content/edit?id=' . $id);
    }

    public function statusToggleSubmit(callable $redirect): void
    {
        if (
            !$this->guardAdmin($redirect, false)
            || !$this->guardCsrf($redirect, 'admin/content', 'Neplatný CSRF token.')
        ) {
            return;
        }

        $id = (int)($_POST['id'] ?? 0);
        $mode = (string)($_POST['mode'] ?? 'draft');

        if ($id <= 0) {
            $this->flash->add('error', 'Neplatné ID obsahu.');
            $redirect('admin/content');
            return;
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

    private function editPath(int $id): string
    {
        return 'admin/content/edit?id=' . $id;
    }
}
