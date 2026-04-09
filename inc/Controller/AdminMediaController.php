<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\Feature\AuthService;
use App\Service\Feature\MediaService;
use App\Service\Feature\UploadService;
use App\Service\Support\CsrfService;
use App\Service\Support\FlashService;
use App\Service\Support\I18n;
use App\Service\Support\PaginationConfig;
use App\View\PageView;

final class AdminMediaController extends BaseAdminController
{
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

        [$page, $perPage, $status, $query] = $this->resolveListQuery();
        $pagination = $this->media->paginate($page, $perPage, $query, $status);
        $statusCounts = $this->media->statusCounts();
        $this->pages->adminMediaList($pagination, PaginationConfig::allowed(), $status, $query, $statusCounts);
    }

    public function listApiV1(callable $redirect): void
    {
        if (!$this->guardAdmin($redirect, false)) {
            return;
        }

        [$page, $perPage, $status, $query] = $this->resolveListQuery();
        $pagination = $this->media->paginate($page, $perPage, $query, $status);
        $items = array_map([$this, 'mapListItem'], (array)($pagination['data'] ?? []));
        $statusCounts = $this->media->statusCounts();

        $this->apiOk($items, [
            'page' => (int)($pagination['page'] ?? 1),
            'per_page' => (int)($pagination['per_page'] ?? $perPage),
            'total_pages' => (int)($pagination['total_pages'] ?? 1),
            'status' => $status,
            'query' => $query,
            'status_counts' => $statusCounts,
        ]);
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
        if (!$this->guardAdminCsrf($redirect, 'admin/media', I18n::t('common.invalid_csrf'), false)) {
            return;
        }

        if (!$this->hasUpload('file')) {
            $this->flash->add('error', I18n::t('media.upload_file_required'));
            $this->storeFormState(self::FORM_STATE_KEY, 'add', null, $_POST, ['file' => I18n::t('media.file_required')]);
            $redirect('admin/media/add');
            return;
        }

        $upload = $this->upload->uploadImage($_FILES['file'] ?? []);
        if (($upload['success'] ?? false) !== true) {
            $this->flash->add('error', (string)($upload['error'] ?? I18n::t('upload.file_upload_failed')));
            $this->storeFormState(self::FORM_STATE_KEY, 'add', null, $_POST, ['file' => (string)($upload['error'] ?? I18n::t('upload.file_upload_failed'))]);
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
        $result = $this->media->save($this->normalizeMediaInput($input, $authorId));

        if (($result['success'] ?? false) === true) {
            $this->flash->add('success', I18n::t('media.created'));
            $newId = (int)($result['id'] ?? 0);
            $redirect($newId > 0 ? $this->editPath($newId) : 'admin/media');
        }

        $this->upload->deleteMediaFiles($uploadData);
        $this->flash->add('error', I18n::t('media.save_failed'));
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
            $this->flash->add('info', I18n::t('media.not_found'));
            $redirect('admin/media');
            return;
        }

        if (!$this->canManageByAuthor($item)) {
            $this->flash->add('error', I18n::t('admin.access_denied'));
            $redirect('admin/media');
            return;
        }

        $state = $this->consumeFormState(self::FORM_STATE_KEY, 'edit', $id);
        $authorFilter = $this->isEditor() ? $this->currentUserId() : null;
        $navigation = $this->media->editNavigation($id, $authorFilter > 0 ? $authorFilter : null);
        $this->pages->adminMediaForm('edit', $state['data'] ?? $item, $state['errors'] ?? [], $this->media->authorOptions(), $this->media->thumbnailUsages($id), $navigation);
    }

    public function editSubmit(callable $redirect): void
    {
        if (!$this->guardAdminCsrf($redirect, 'admin/media', I18n::t('common.invalid_csrf'), false)) {
            return;
        }

        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            $this->flash->add('error', I18n::t('media.invalid_id'));
            $redirect('admin/media');
            return;
        }

        $item = $this->media->find($id);
        if ($item === null) {
            $this->flash->add('error', I18n::t('media.not_found'));
            $redirect('admin/media');
            return;
        }

        if (!$this->canManageByAuthor($item)) {
            $this->flash->add('error', I18n::t('admin.access_denied'));
            $redirect('admin/media');
            return;
        }

        $input = array_merge($_POST, [
            'path' => (string)($item['path'] ?? ''),
            'path_webp' => (string)($item['path_webp'] ?? ''),
        ]);

        $authorId = (int)($this->authService->auth()->id() ?? 0);
        $result = $this->media->save($this->normalizeMediaInput($input, $authorId), $id);

        if (($result['success'] ?? false) === true) {
            $this->flash->add('success', I18n::t('media.updated'));
            $redirect($this->editPath($id));
        }

        $this->flash->add('error', I18n::t('media.update_failed'));
        $this->storeFormState(self::FORM_STATE_KEY, 'edit', $id, array_merge($_POST, ['id' => $id]), $result['errors'] ?? []);
        $redirect($this->editPath($id));
    }

    public function deleteApiV1(callable $redirect, int $id): void
    {
        if (!$this->guardAdminCsrf($redirect, 'admin/media', I18n::t('common.invalid_csrf'), false)) {
            return;
        }

        if ($id <= 0) {
            $this->apiError('INVALID_ID', I18n::t('media.invalid_id'));
            return;
        }

        $item = $this->media->find($id);
        if ($item === null) {
            $this->apiError('NOT_FOUND', I18n::t('media.not_found'), 404);
            return;
        }

        if (!$this->canManageByAuthor($item)) {
            $this->apiError('FORBIDDEN', I18n::t('admin.access_denied'), 403);
            return;
        }

        if (!$this->media->delete($id)) {
            $this->apiError('DELETE_FAILED', I18n::t('media.delete_failed'));
            return;
        }

        $this->upload->deleteMediaFiles($item);
        $this->apiOk(['id' => $id]);
    }

    public function deleteSubmit(callable $redirect): void
    {
        if (!$this->guardAdminCsrf($redirect, 'admin/media', I18n::t('common.invalid_csrf'), false)) {
            return;
        }

        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            $this->flash->add('error', I18n::t('media.invalid_id'));
            $redirect('admin/media');
            return;
        }

        $item = $this->media->find($id);
        if ($item === null) {
            $this->flash->add('error', I18n::t('media.not_found'));
            $redirect('admin/media');
            return;
        }

        if (!$this->canManageByAuthor($item)) {
            $this->flash->add('error', I18n::t('admin.access_denied'));
            $redirect('admin/media');
            return;
        }

        $authorFilter = $this->isEditor() ? $this->currentUserId() : null;
        $nextId = $this->media->nextIdAfterDelete($id, $authorFilter > 0 ? $authorFilter : null);

        if (!$this->media->delete($id)) {
            $this->flash->add('error', I18n::t('media.delete_failed'));
            $redirect($this->editPath($id));
            return;
        }

        $this->upload->deleteMediaFiles($item);
        $this->flash->add('success', I18n::t('media.deleted'));
        $redirect($nextId !== null ? $this->editPath($nextId) : 'admin/media');
    }

    private function editPath(int $id): string
    {
        return 'admin/media/edit?id=' . $id;
    }

    private function resolveListQuery(): array
    {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $defaultPerPage = PaginationConfig::perPage();
        $perPage = (int)($_GET['per_page'] ?? $defaultPerPage);
        $status = (string)($_GET['status'] ?? 'all');
        $query = trim((string)($_GET['q'] ?? ''));

        if (!in_array($perPage, PaginationConfig::allowed(), true)) {
            $perPage = $defaultPerPage;
        }

        if (!in_array($status, ['all', 'unassigned'], true)) {
            $status = 'all';
        }

        return [$page, $perPage, $status, $query];
    }

    private function hasUpload(string $field): bool
    {
        return isset($_FILES[$field]) && (int)($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
    }

    private function normalizeMediaInput(array $input, int $authorId): array
    {
        if (!$this->isEditor()) {
            return $input;
        }

        $input['author'] = $authorId > 0 ? (string)$authorId : '';
        return $input;
    }

    private function mapListItem(array $row): array
    {
        return [
            'id' => (int)($row['id'] ?? 0),
            'name' => (string)($row['name'] ?? ''),
            'can_edit' => $this->canManageByAuthor($row),
            'can_delete' => $this->canManageByAuthor($row),
            'path' => (string)($row['path'] ?? ''),
            'path_webp' => (string)($row['path_webp'] ?? ''),
            'preview_path' => $this->resolvePreviewPath($row),
            'author_name' => (string)($row['author_name'] ?? '—'),
            'created' => (string)($row['created'] ?? ''),
            'created_label' => $this->formatDateTime((string)($row['created'] ?? '')),
        ];
    }

    private function resolvePreviewPath(array $row): string
    {
        $pathWebp = trim((string)($row['path_webp'] ?? ''));
        if ($pathWebp !== '') {
            $preview = (string)(preg_replace('/\.webp$/i', $this->thumbnailSuffix(), $pathWebp) ?? $pathWebp);
            return str_starts_with($preview, '/') ? $preview : '/' . ltrim($preview, '/');
        }
        $path = trim((string)($row['path'] ?? ''));
        if ($path === '') {
            return '';
        }
        return str_starts_with($path, '/') ? $path : '/' . ltrim($path, '/');
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

}
