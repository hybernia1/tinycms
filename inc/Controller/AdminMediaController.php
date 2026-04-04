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
        $this->pages->adminMediaList($pagination, self::PER_PAGE_ALLOWED, $query);
    }

    public function addForm(callable $redirect): void
    {
        if (!$this->guardAdmin($redirect, false)) {
            return;
        }

        $fallback = ['id' => null, 'name' => '', 'path' => '', 'path_webp' => '', 'author' => (int)($this->authService->auth()->id() ?? 0)];
        $state = $this->consumeFormState(self::FORM_STATE_KEY, 'add', null);
        $this->pages->adminMediaForm('add', $state['data'] ?? $fallback, $state['errors'] ?? [], $this->media->authorOptions(), []);
    }

    public function addSubmit(callable $redirect): void
    {
        if (
            !$this->guardAdmin($redirect, false)
            || !$this->guardCsrf($redirect, 'admin/media', 'Neplatný CSRF token.')
        ) {
            return;
        }

        if (!$this->hasUpload('file')) {
            $this->flash->add('error', 'Nahrajte soubor média.');
            $this->storeFormState(self::FORM_STATE_KEY, 'add', null, $_POST, ['file' => 'Soubor je povinný.']);
            $redirect('admin/media/add');
            return;
        }

        $upload = $this->upload->uploadImage($_FILES['file'] ?? []);
        if (($upload['success'] ?? false) !== true) {
            $this->flash->add('error', (string)($upload['error'] ?? 'Soubor se nepodařilo nahrát.'));
            $this->storeFormState(self::FORM_STATE_KEY, 'add', null, $_POST, ['file' => (string)($upload['error'] ?? 'Soubor se nepodařilo nahrát.')]);
            $redirect('admin/media/add');
            return;
        }

        $uploadData = (array)($upload['data'] ?? []);
        $authorId = (int)($this->authService->auth()->id() ?? 0);
        $input = array_merge($_POST, [
            'name' => trim((string)($_POST['name'] ?? '')) !== '' ? (string)$_POST['name'] : (string)($uploadData['name'] ?? ''),
            'path' => (string)($uploadData['path'] ?? ''),
            'path_webp' => (string)($uploadData['path_webp'] ?? ''),
            'author' => trim((string)($_POST['author'] ?? '')) !== '' ? (string)$_POST['author'] : ($authorId > 0 ? (string)$authorId : ''),
        ]);
        $result = $this->media->save($input);

        if (($result['success'] ?? false) === true) {
            $this->flash->add('success', 'Médium vytvořeno.');
            $newId = (int)($result['id'] ?? 0);
            $redirect($newId > 0 ? $this->editPath($newId) : 'admin/media');
        }

        $this->upload->deleteMediaFiles($uploadData);
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
        $this->pages->adminMediaForm('edit', $state['data'] ?? $item, $state['errors'] ?? [], $this->media->authorOptions(), $this->media->thumbnailUsages($id));
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

        $item = $this->media->find($id);
        if ($item === null) {
            $this->flash->add('error', 'Médium nenalezeno.');
            $redirect('admin/media');
            return;
        }

        $input = array_merge($_POST, [
            'path' => (string)($item['path'] ?? ''),
            'path_webp' => (string)($item['path_webp'] ?? ''),
        ]);
        $newUploadData = null;

        if ($this->hasUpload('file')) {
            $upload = $this->upload->uploadImage($_FILES['file'] ?? []);
            if (($upload['success'] ?? false) !== true) {
                $this->flash->add('error', (string)($upload['error'] ?? 'Soubor se nepodařilo nahrát.'));
                $this->storeFormState(self::FORM_STATE_KEY, 'edit', $id, array_merge($_POST, ['id' => $id]), ['file' => (string)($upload['error'] ?? 'Soubor se nepodařilo nahrát.')]);
                $redirect($this->editPath($id));
                return;
            }

            $newUploadData = (array)($upload['data'] ?? []);
            $input['path'] = (string)($newUploadData['path'] ?? '');
            $input['path_webp'] = (string)($newUploadData['path_webp'] ?? '');
            if (trim((string)($input['name'] ?? '')) === '') {
                $input['name'] = (string)($newUploadData['name'] ?? '');
            }
        }

        $result = $this->media->save($input, $id);

        if (($result['success'] ?? false) === true) {
            if (is_array($newUploadData)) {
                $this->upload->deleteMediaFiles($item);
            }
            $this->flash->add('success', 'Médium upraveno.');
            $redirect($this->editPath($id));
        }

        if (is_array($newUploadData)) {
            $this->upload->deleteMediaFiles($newUploadData);
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
            if ($this->wantsJson()) {
                $this->jsonError('Neplatné ID média.');
                return;
            }
            $this->flash->add('error', 'Neplatné ID média.');
            $redirect('admin/media');
            return;
        }

        $item = $this->media->find($id);
        if ($item === null) {
            if ($this->wantsJson()) {
                $this->jsonError('Médium nenalezeno.');
                return;
            }
            $this->flash->add('info', 'Médium nenalezeno.');
            $redirect('admin/media');
            return;
        }

        if (!$this->media->delete($id)) {
            if ($this->wantsJson()) {
                $this->jsonError('Médium se nepodařilo smazat.');
                return;
            }
            $this->flash->add('error', 'Médium se nepodařilo smazat.');
            $redirect('admin/media');
            return;
        }

        $this->upload->deleteMediaFiles($item);
        if ($this->wantsJson()) {
            $this->jsonSuccess(['id' => $id]);
            return;
        }
        $this->flash->add('success', 'Médium smazáno.');
        $redirect('admin/media');
    }

    private function editPath(int $id): string
    {
        return 'admin/media/edit?id=' . $id;
    }

    private function hasUpload(string $field): bool
    {
        return isset($_FILES[$field]) && (int)($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
    }

    private function mapListItem(array $row): array
    {
        return [
            'id' => (int)($row['id'] ?? 0),
            'name' => (string)($row['name'] ?? ''),
            'path' => (string)($row['path'] ?? ''),
            'path_webp' => (string)($row['path_webp'] ?? ''),
            'preview_path' => $this->resolvePreviewPath($row),
            'author_name' => (string)($row['author_name'] ?? '—'),
            'created' => (string)($row['created'] ?? ''),
        ];
    }

    private function resolvePreviewPath(array $row): string
    {
        $pathWebp = trim((string)($row['path_webp'] ?? ''));
        if ($pathWebp !== '') {
            return (string)(preg_replace('/\.webp$/i', $this->thumbnailSuffix(), $pathWebp) ?? $pathWebp);
        }
        return trim((string)($row['path'] ?? ''));
    }

    private function thumbnailSuffix(): string
    {
        $suffix = '_100x100.webp';
        if (defined('MEDIA_THUMB_VARIANTS') && is_array(MEDIA_THUMB_VARIANTS)) {
            $firstVariant = MEDIA_THUMB_VARIANTS[0] ?? null;
            if (is_array($firstVariant) && !empty($firstVariant['suffix'])) {
                $suffix = (string)$firstVariant['suffix'];
            }
        }
        return $suffix;
    }

    private function wantsJson(): bool
    {
        $accept = strtolower((string)($_SERVER['HTTP_ACCEPT'] ?? ''));
        return str_contains($accept, 'application/json');
    }

    private function jsonError(string $message): void
    {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => $message], JSON_UNESCAPED_UNICODE);
    }

    private function jsonSuccess(array $payload): void
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(array_merge(['success' => true], $payload), JSON_UNESCAPED_UNICODE);
    }
}
