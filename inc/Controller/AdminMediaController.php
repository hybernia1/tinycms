<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\Feature\AuthService;
use App\Service\Feature\MediaService;
use App\Service\Feature\UploadService;
use App\Service\Support\CsrfService;
use App\Service\Support\FlashService;
use App\View\PageView;

final class AdminMediaController extends BaseAdminController
{
    private const PER_PAGE_ALLOWED = [10, 20, 50];
    private const FORM_STATE_KEY = 'admin_media_form_state';

    public function __construct(
        private PageView $pages,
        AuthService $authService,
        private MediaService $media,
        private UploadService $upload,
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

        $pagination = $this->media->paginate($page, $perPage, $query);
        $this->pages->adminMediaList($pagination, self::PER_PAGE_ALLOWED, $query);
    }

    public function addForm(callable $redirect): void
    {
        if (!$this->guardAdmin($redirect, false)) {
            return;
        }

        $fallback = ['id' => null, 'name' => '', 'path' => '', 'path_webp' => '', 'author' => null];
        $state = $this->consumeFormState(self::FORM_STATE_KEY, 'add', null);
        $this->pages->adminMediaForm('add', $state['data'] ?? $fallback, $state['errors'] ?? [], $this->media->authorOptions());
    }

    public function addSubmit(callable $redirect): void
    {
        if (
            !$this->guardAdmin($redirect, false)
            || !$this->guardCsrf($redirect, 'admin/media', 'Neplatný CSRF token.')
        ) {
            return;
        }

        $result = $this->media->save($_POST);

        if (($result['success'] ?? false) === true) {
            $this->flash->add('success', 'Médium vytvořeno.');
            $newId = (int)($result['id'] ?? 0);
            $redirect($newId > 0 ? $this->editPath($newId) : 'admin/media');
        }

        $this->flash->add('error', 'Nepodařilo se uložit médium.');
        $this->storeFormState(self::FORM_STATE_KEY, 'add', null, $_POST, $result['errors'] ?? []);
        $redirect('admin/media/add');
    }

    public function editForm(callable $redirect): void
    {
        if (!$this->guardAdmin($redirect, false)) {
            return;
        }

        $id = (int)($_GET['id'] ?? 0);
        $item = $this->media->find($id);

        if ($item === null) {
            $this->flash->add('info', 'Médium nenalezeno.');
            $redirect('admin/media');
            return;
        }

        $state = $this->consumeFormState(self::FORM_STATE_KEY, 'edit', $id);
        $this->pages->adminMediaForm('edit', $state['data'] ?? $item, $state['errors'] ?? [], $this->media->authorOptions());
    }

    public function editSubmit(callable $redirect): void
    {
        if (
            !$this->guardAdmin($redirect, false)
            || !$this->guardCsrf($redirect, 'admin/media', 'Neplatný CSRF token.')
        ) {
            return;
        }

        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            $this->flash->add('error', 'Neplatné ID média.');
            $redirect('admin/media');
            return;
        }

        $result = $this->media->save($_POST, $id);

        if (($result['success'] ?? false) === true) {
            $this->flash->add('success', 'Médium upraveno.');
            $redirect($this->editPath($id));
        }

        $this->flash->add('error', 'Nepodařilo se upravit médium.');
        $this->storeFormState(self::FORM_STATE_KEY, 'edit', $id, array_merge($_POST, ['id' => $id]), $result['errors'] ?? []);
        $redirect($this->editPath($id));
    }

    public function deleteSubmit(callable $redirect): void
    {
        if (
            !$this->guardAdmin($redirect, false)
            || !$this->guardCsrf($redirect, 'admin/media', 'Neplatný CSRF token.')
        ) {
            return;
        }

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            $this->flash->add('error', 'Neplatné ID média.');
            $redirect('admin/media');
            return;
        }

        $item = $this->media->find($id);
        if ($item === null) {
            $this->flash->add('info', 'Médium nenalezeno.');
            $redirect('admin/media');
            return;
        }

        if (!$this->media->delete($id)) {
            $this->flash->add('error', 'Médium se nepodařilo smazat.');
            $redirect('admin/media');
            return;
        }

        $this->upload->deleteMediaFiles($item);
        $this->flash->add('success', 'Médium smazáno.');
        $redirect('admin/media');
    }

    private function editPath(int $id): string
    {
        return 'admin/media/edit?id=' . $id;
    }
}
