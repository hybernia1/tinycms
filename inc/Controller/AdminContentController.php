<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\Feature\AuthService;
use App\Service\Feature\ContentService;
use App\Service\Feature\MediaService;
use App\Service\Feature\UploadService;
use App\Service\Feature\TermService;
use App\Service\Support\CsrfService;
use App\Service\Support\FlashService;
use App\Service\Feature\UserService;
use App\Service\Support\I18n;
use App\Service\Support\PaginationConfig;
use App\View\PageView;

final class AdminContentController extends BaseAdminController
{
    private const FORM_STATE_KEY = 'admin_content_form_state';

    public function __construct(
        private PageView $pages,
        AuthService $authService,
        private ContentService $content,
        private MediaService $media,
        private UploadService $upload,
        private UserService $users,
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

        [$page, $perPage, $status, $query, $availableStatuses] = $this->resolveListQuery();

        $pagination = $this->content->paginate($page, $perPage, $status, $query);
        $statusCounts = $this->content->statusCounts($availableStatuses);
        $this->pages->adminContentList($pagination, PaginationConfig::allowed(), $status, $query, $availableStatuses, $statusCounts);
    }

    public function listApiV1(callable $redirect): void
    {
        if (!$this->guardAdmin($redirect, false)) {
            return;
        }

        [$page, $perPage, $status, $query, $availableStatuses] = $this->resolveListQuery();
        $pagination = $this->content->paginate($page, $perPage, $status, $query);
        $items = array_map([$this, 'mapListItem'], (array)($pagination['data'] ?? []));
        $statusCounts = $this->content->statusCounts($availableStatuses);

        $this->apiOk($items, [
            'page' => (int)($pagination['page'] ?? 1),
            'per_page' => (int)($pagination['per_page'] ?? $perPage),
            'total_pages' => (int)($pagination['total_pages'] ?? 1),
            'status' => $status,
            'query' => $query,
            'status_counts' => $statusCounts,
        ]);
    }

    public function deleteApiV1(callable $redirect, int $id): void
    {
        if (
            !$this->guardAdmin($redirect, false)
            || !$this->guardCsrf($redirect, 'admin/content', I18n::t('common.invalid_csrf'))
        ) {
            return;
        }

        if ($id <= 0) {
            $this->apiError('INVALID_ID', I18n::t('content.invalid_id'));
            return;
        }

        $item = $this->content->find($id);
        if ($item === null) {
            $this->apiError('NOT_FOUND', I18n::t('content.not_found'), 404);
            return;
        }

        if (!$this->canDeleteContent($item)) {
            $this->apiError('FORBIDDEN', I18n::t('admin.access_denied'), 403);
            return;
        }

        if (!$this->content->delete($id)) {
            $this->apiError('DELETE_FAILED', I18n::t('content.delete_failed'));
            return;
        }

        $this->apiOk(['id' => $id]);
    }

    public function deleteSubmit(callable $redirect): void
    {
        if (
            !$this->guardAdmin($redirect, false)
            || !$this->guardCsrf($redirect, 'admin/content', I18n::t('common.invalid_csrf'))
        ) {
            return;
        }

        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            $this->flash->add('error', I18n::t('content.invalid_id'));
            $redirect('admin/content');
            return;
        }

        $item = $this->content->find($id);
        if ($item === null) {
            $this->flash->add('error', I18n::t('content.not_found'));
            $redirect('admin/content');
            return;
        }

        if (!$this->canDeleteContent($item)) {
            $this->flash->add('error', I18n::t('admin.access_denied'));
            $redirect('admin/content');
            return;
        }

        if (!$this->content->delete($id)) {
            $this->flash->add('error', I18n::t('content.delete_failed'));
            $redirect($this->editPath($id));
            return;
        }

        $this->flash->add('success', I18n::t('content.deleted'));
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
        $item = $state['data'] ?? $fallback;
        $selectedTerms = $this->resolveSelectedTerms($item, null);
        $this->pages->adminContentForm('add', $item, $state['errors'] ?? [], $statuses, $this->users->authorOptions(), $selectedTerms);
    }

    public function addSubmit(callable $redirect): void
    {
        if (
            !$this->guardAdmin($redirect, false)
            || !$this->guardCsrf($redirect, 'admin/content', I18n::t('common.invalid_csrf'))
        ) {
            return;
        }

        $authorId = (int)($this->authService->auth()->id() ?? 0);
        $result = $this->content->save($this->normalizeContentInput($_POST, $authorId), $authorId);

        if (($result['success'] ?? false) === true) {
            $newId = (int)($result['id'] ?? 0);
            if ($newId > 0) {
                $this->terms->syncContentTerms($newId, (string)($_POST['terms'] ?? ''));
            }
            $this->flash->add('success', I18n::t('content.created'));
            $redirect($newId > 0 ? $this->editPath($newId) : 'admin/content');
        }

        $this->flash->add('error', I18n::t('content.save_failed'));
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
            $this->flash->add('info', I18n::t('content.not_found'));
            $redirect('admin/content');
            return;
        }

        if (!$this->canManageContent($item)) {
            $this->flash->add('error', I18n::t('admin.access_denied'));
            $redirect('admin/content');
            return;
        }

        $state = $this->consumeFormState(self::FORM_STATE_KEY, 'edit', $id);
        $statuses = $this->content->statuses();
        $formItem = $state['data'] ?? $item;
        $selectedTerms = $this->resolveSelectedTerms($formItem, $id);
        $this->pages->adminContentForm('edit', $formItem, $state['errors'] ?? [], $statuses, $this->users->authorOptions(), $selectedTerms);
    }

    public function editSubmit(callable $redirect): void
    {
        if (
            !$this->guardAdmin($redirect, false)
            || !$this->guardCsrf($redirect, 'admin/content', I18n::t('common.invalid_csrf'))
        ) {
            return;
        }

        $id = (int)($_GET['id'] ?? 0);

        if ($id <= 0) {
            $this->flash->add('error', I18n::t('content.invalid_id'));
            $redirect('admin/content');
            return;
        }

        $item = $this->content->find($id);
        if ($item === null) {
            $this->flash->add('error', I18n::t('content.not_found'));
            $redirect('admin/content');
            return;
        }

        if (!$this->canManageContent($item)) {
            $this->flash->add('error', I18n::t('admin.access_denied'));
            $redirect('admin/content');
            return;
        }

        $authorId = (int)($this->authService->auth()->id() ?? 0);
        $result = $this->content->save($this->normalizeContentInput($_POST, $authorId), $authorId, $id);

        if (($result['success'] ?? false) === true) {
            $this->terms->syncContentTerms($id, (string)($_POST['terms'] ?? ''));
            $this->flash->add('success', I18n::t('content.updated'));
            $redirect($this->editPath($id));
        }

        $this->flash->add('error', I18n::t('content.update_failed'));
        $this->storeFormState(self::FORM_STATE_KEY, 'edit', $id, array_merge($_POST, ['id' => $id]), $result['errors'] ?? []);
        $redirect('admin/content/edit?id=' . $id);
    }

    public function statusApiV1(callable $redirect, int $id): void
    {
        if (
            !$this->guardAdmin($redirect, false)
            || !$this->guardCsrf($redirect, 'admin/content', I18n::t('common.invalid_csrf'))
        ) {
            return;
        }

        $mode = (string)($_POST['mode'] ?? 'draft');
        if ($id <= 0) {
            $this->apiError('INVALID_ID', I18n::t('content.invalid_id'));
            return;
        }

        $item = $this->content->find($id);
        if ($item === null) {
            $this->apiError('NOT_FOUND', I18n::t('content.not_found'), 404);
            return;
        }

        if (!$this->canManageContent($item)) {
            $this->apiError('FORBIDDEN', I18n::t('admin.access_denied'), 403);
            return;
        }

        if ($mode === 'publish') {
            if (!$this->content->setStatus($id, 'published')) {
                $this->apiError('PUBLISH_FAILED', 'Obsah už byl publikovaný nebo není dostupný.');
                return;
            }

            $this->apiOk(['id' => $id, 'status' => 'published']);
            return;
        }

        if (!$this->content->setStatus($id, 'draft')) {
            $this->apiError('DRAFT_FAILED', 'Obsah už byl v draftu nebo není dostupný.');
            return;
        }

        $this->apiOk(['id' => $id, 'status' => 'draft']);
    }

    public function draftInitApiV1(callable $redirect): void
    {
        if (
            !$this->guardAdmin($redirect, false)
            || !$this->guardCsrf($redirect, 'admin/content', I18n::t('common.invalid_csrf'))
        ) {
            return;
        }

        $authorId = (int)($this->authService->auth()->id() ?? 0);
        $payload = [
            'name' => I18n::t('content.untitled'),
            'status' => 'draft',
            'excerpt' => '',
            'body' => '',
            'author' => $authorId > 0 ? (string)$authorId : '',
            'created' => '',
        ];
        $result = $this->content->save($payload, $authorId);
        if (($result['success'] ?? false) !== true) {
            $this->apiError('CREATE_FAILED', I18n::t('content.draft_create_failed'));
            return;
        }

        $id = (int)($result['id'] ?? 0);
        if ($id <= 0) {
            $this->apiError('INVALID_ID', I18n::t('content.draft_invalid_id'));
            return;
        }

        $this->apiOk(['id' => $id, 'created_new' => true]);
    }

    public function autosaveApiV1(callable $redirect): void
    {
        if (
            !$this->guardAdmin($redirect, false)
            || !$this->guardCsrf($redirect, 'admin/content', I18n::t('common.invalid_csrf'))
        ) {
            return;
        }

        $id = (int)($_POST['id'] ?? 0);
        $name = trim((string)($_POST['name'] ?? ''));
        $body = trim((string)($_POST['body'] ?? ''));
        if ($id <= 0 && $name === '' && $body === '') {
            $this->apiOk(['id' => 0, 'skipped' => true, 'reason' => 'empty']);
            return;
        }

        if ($id > 0) {
            $item = $this->content->find($id);
            if ($item === null) {
                $this->apiError('NOT_FOUND', I18n::t('content.not_found'), 404);
                return;
            }

            if (!$this->canManageContent($item)) {
                $this->apiError('FORBIDDEN', I18n::t('admin.access_denied'), 403);
                return;
            }
        }

        $authorId = (int)($this->authService->auth()->id() ?? 0);
        $payload = $this->resolveAutosavePayload($_POST, $authorId);
        $isCreate = $id <= 0;
        $result = $this->content->save($payload, $authorId, $isCreate ? null : $id);

        if (($result['success'] ?? false) !== true) {
            $this->apiError('SAVE_FAILED', I18n::t('content.autosave_failed'), 422, ['errors' => $result['errors'] ?? []]);
            return;
        }

        $savedId = (int)($result['id'] ?? 0);
        if ($savedId > 0 && isset($_POST['terms'])) {
            $this->terms->syncContentTerms($savedId, (string)$_POST['terms']);
        }

        $this->apiOk([
            'id' => $savedId,
            'created_new' => $isCreate,
            'updated' => date('Y-m-d H:i:s'),
        ]);
    }

    public function linkTitleApiV1(callable $redirect): void
    {
        if (!$this->guardAdmin($redirect, false)) {
            return;
        }

        $url = trim((string)($_GET['url'] ?? ''));
        if ($url === '' || !$this->isValidExternalUrl($url)) {
            $this->apiError('INVALID_URL', I18n::t('common.invalid_data'));
            return;
        }

        $title = $this->fetchRemoteTitle($url);
        if ($title === '') {
            $this->apiError('TITLE_NOT_FOUND', I18n::t('content.link_title_not_found'), 404);
            return;
        }

        $this->apiOk(['title' => $title]);
    }

    public function thumbnailUploadSubmit(callable $redirect): void
    {
        if (
            !$this->guardAdmin($redirect, false)
            || !$this->guardCsrf($redirect, 'admin/content', I18n::t('common.invalid_csrf'))
        ) {
            return;
        }

        $id = (int)($_GET['id'] ?? 0);
        $item = $this->content->find($id);

        if ($item === null) {
            $this->flash->add('error', I18n::t('content.not_found'));
            $redirect('admin/content');
            return;
        }

        if (!$this->canDeleteContent($item)) {
            $this->flash->add('error', I18n::t('admin.access_denied'));
            $redirect('admin/content');
            return;
        }

        $upload = $this->upload->uploadImage($_FILES['thumbnail'] ?? []);
        if (($upload['success'] ?? false) !== true) {
            $this->flash->add('error', (string)($upload['error'] ?? I18n::t('upload.file_upload_failed')));
            $redirect($this->editPath($id));
            return;
        }

        $author = (int)($this->authService->auth()->id() ?? 0);
        $data = (array)($upload['data'] ?? []);
        $mediaId = $this->media->create(
            $author > 0 ? $author : null,
            (string)($data['name'] ?? ''),
            (string)($data['path'] ?? ''),
            (string)($data['path_webp'] ?? '')
        );

        if ($mediaId <= 0 || !$this->content->setThumbnail($id, $mediaId)) {
            if ($mediaId > 0) {
                $this->media->delete($mediaId);
            }
            $this->upload->deleteMediaFiles($data);
            $this->flash->add('error', I18n::t('content.thumbnail_save_failed'));
            $redirect($this->editPath($id));
            return;
        }

        $this->flash->add('success', I18n::t('content.thumbnail_uploaded'));
        $redirect($this->editPath($id));
    }

    public function thumbnailDetachApiV1(callable $redirect, int $id): void
    {
        if (
            !$this->guardAdmin($redirect, false)
            || !$this->guardCsrf($redirect, 'admin/content', I18n::t('common.invalid_csrf'))
        ) {
            return;
        }

        $item = $this->content->find($id);
        if ($id <= 0 || $item === null) {
            $this->apiError('NOT_FOUND', I18n::t('content.not_found'), 404);
            return;
        }

        if (!$this->canManageContent($item)) {
            $this->apiError('FORBIDDEN', I18n::t('admin.access_denied'), 403);
            return;
        }

        if (!$this->content->setThumbnail($id, null)) {
            $this->apiError('DETACH_FAILED', I18n::t('content.thumbnail_detach_failed'));
            return;
        }

        $this->apiOk(['id' => $id]);
    }

    public function thumbnailSelectApiV1(callable $redirect, int $contentId, int $mediaId): void
    {
        if (
            !$this->guardAdmin($redirect, false)
            || !$this->guardCsrf($redirect, 'admin/content', I18n::t('common.invalid_csrf'))
        ) {
            return;
        }

        $content = $this->content->find($contentId);
        if ($contentId <= 0 || $content === null) {
            $this->apiError('NOT_FOUND', I18n::t('content.not_found'), 404);
            return;
        }

        if (!$this->canManageContent($content)) {
            $this->apiError('FORBIDDEN', I18n::t('admin.access_denied'), 403);
            return;
        }

        $media = $this->media->find($mediaId);
        if ($mediaId <= 0 || $media === null) {
            $this->apiError('MEDIA_NOT_FOUND', I18n::t('media.not_found'), 404);
            return;
        }

        if (!$this->canManageMedia($media)) {
            $this->apiError('FORBIDDEN', I18n::t('admin.access_denied'), 403);
            return;
        }

        if (!$this->content->setThumbnail($contentId, $mediaId)) {
            $this->apiError('SELECT_FAILED', I18n::t('content.thumbnail_select_failed'));
            return;
        }

        $this->apiOk([
            'content_id' => $contentId,
            'media_id' => $mediaId,
            'media' => $this->mapLibraryItem($media),
        ]);
    }

    public function thumbnailDeleteSubmit(callable $redirect): void
    {
        if (
            !$this->guardAdmin($redirect, false)
            || !$this->guardCsrf($redirect, 'admin/content', I18n::t('common.invalid_csrf'))
        ) {
            return;
        }

        $id = (int)($_POST['id'] ?? 0);
        $item = $this->content->find($id);

        if ($item === null) {
            $this->flash->add('error', I18n::t('content.not_found'));
            $redirect('admin/content');
            return;
        }

        if (!$this->canDeleteContent($item)) {
            $this->flash->add('error', I18n::t('admin.access_denied'));
            $redirect('admin/content');
            return;
        }

        $thumbnailId = (int)($item['thumbnail'] ?? 0);
        if ($thumbnailId <= 0) {
            $this->flash->add('info', I18n::t('content.thumbnail_missing'));
            $redirect($this->editPath($id));
            return;
        }

        $media = $this->media->find($thumbnailId);
        if ($media === null || !$this->media->delete($thumbnailId)) {
            $this->flash->add('error', I18n::t('content.thumbnail_delete_failed'));
            $redirect($this->editPath($id));
            return;
        }

        $this->upload->deleteMediaFiles($media);
        $this->flash->add('success', I18n::t('content.thumbnail_deleted'));
        $redirect($this->editPath($id));
    }

    public function mediaLibraryApiV1(callable $redirect, int $contentId): void
    {
        if (!$this->guardAdmin($redirect, false)) {
            return;
        }

        $content = $this->content->find($contentId);
        if ($contentId <= 0 || $content === null) {
            $this->apiError('NOT_FOUND', I18n::t('content.not_found'), 404);
            return;
        }

        if (!$this->canManageContent($content)) {
            $this->apiError('FORBIDDEN', I18n::t('admin.access_denied'), 403);
            return;
        }

        $page = max(1, (int)($_GET['page'] ?? 1));
        $defaultPerPage = PaginationConfig::perPage();
        $perPage = (int)($_GET['per_page'] ?? $defaultPerPage);
        $query = trim((string)($_GET['q'] ?? ''));
        $currentMediaId = (int)($_GET['current_media_id'] ?? 0);
        if (!in_array($perPage, PaginationConfig::allowed(), true)) {
            $perPage = $defaultPerPage;
        }

        $pagination = $this->media->paginate($page, $perPage, $query);
        $items = array_map(fn(array $item): array => $this->mapLibraryItem($item), (array)($pagination['data'] ?? []));
        if ($currentMediaId > 0) {
            $currentItem = $this->media->find($currentMediaId);
            if ($currentItem !== null && $this->matchesLibraryQuery($currentItem, $query)) {
                $items = array_values(array_filter($items, static fn(array $row): bool => (int)($row['id'] ?? 0) !== $currentMediaId));
                array_unshift($items, $this->mapLibraryItem($currentItem));
            }
        }

        $this->apiOk($items, [
            'page' => (int)($pagination['page'] ?? 1),
            'per_page' => (int)($pagination['per_page'] ?? $perPage),
            'total_pages' => (int)($pagination['total_pages'] ?? 1),
            'query' => $query,
        ]);
    }

    public function mediaLibraryDeleteApiV1(callable $redirect, int $contentId, int $mediaId): void
    {
        if (
            !$this->guardAdmin($redirect, false)
            || !$this->guardCsrf($redirect, 'admin/content', I18n::t('common.invalid_csrf'))
        ) {
            return;
        }

        $item = $this->content->find($contentId);
        if ($item === null) {
            $this->apiError('NOT_FOUND', I18n::t('content.not_found'), 404);
            return;
        }

        if (!$this->canDeleteContent($item)) {
            $this->apiError('FORBIDDEN', I18n::t('admin.access_denied'), 403);
            return;
        }

        if ($mediaId <= 0) {
            $this->apiError('INVALID_MEDIA_ID', I18n::t('media.not_found'));
            return;
        }

        $media = $this->media->find($mediaId);
        if ($media !== null && !$this->canDeleteMedia($media)) {
            $this->apiError('FORBIDDEN', I18n::t('admin.access_denied'), 403);
            return;
        }

        if ($media === null || !$this->media->delete($mediaId)) {
            $this->apiError('DELETE_FAILED', I18n::t('media.delete_failed'));
            return;
        }

        if ((int)($item['thumbnail'] ?? 0) === $mediaId) {
            $this->content->setThumbnail($contentId, null);
        }

        $this->upload->deleteMediaFiles($media);
        $this->apiOk(['id' => $mediaId, 'content_id' => $contentId]);
    }

    public function mediaLibraryUploadApiV1(callable $redirect, int $contentId): void
    {
        if (
            !$this->guardAdmin($redirect, false)
            || !$this->guardCsrf($redirect, 'admin/content', I18n::t('common.invalid_csrf'))
        ) {
            return;
        }

        $content = $this->content->find($contentId);
        if ($contentId <= 0 || $content === null) {
            $this->apiError('NOT_FOUND', I18n::t('content.not_found'), 404);
            return;
        }

        if (!$this->canManageContent($content)) {
            $this->apiError('FORBIDDEN', I18n::t('admin.access_denied'), 403);
            return;
        }

        $upload = $this->upload->uploadImage($_FILES['thumbnail'] ?? []);
        if (($upload['success'] ?? false) !== true) {
            $this->apiError('UPLOAD_FAILED', (string)($upload['error'] ?? I18n::t('upload.file_upload_failed')));
            return;
        }

        $author = (int)($this->authService->auth()->id() ?? 0);
        $data = (array)($upload['data'] ?? []);
        $mediaId = $this->media->create(
            $author > 0 ? $author : null,
            (string)($data['name'] ?? ''),
            (string)($data['path'] ?? ''),
            (string)($data['path_webp'] ?? '')
        );

        if ($mediaId <= 0) {
            $this->upload->deleteMediaFiles($data);
            $this->apiError('SAVE_FAILED', I18n::t('media.save_failed'));
            return;
        }

        $media = $this->media->find($mediaId);
        $previewPath = $media !== null ? $this->resolvePreviewPath($media) : (string)($data['path'] ?? '');
        $this->apiOk([
            'id' => $mediaId,
            'name' => (string)($media['name'] ?? ($data['name'] ?? '')),
            'preview_path' => $previewPath,
            'path' => (string)($media['path'] ?? ($data['path'] ?? '')),
            'webp_path' => (string)($media['path_webp'] ?? ($data['path_webp'] ?? '')),
            'created' => (string)($media['created'] ?? date('Y-m-d H:i:s')),
            'created_label' => $this->formatDateTime((string)($media['created'] ?? date('Y-m-d H:i:s'))),
        ]);
    }

    public function mediaLibraryRenameApiV1(callable $redirect, int $contentId, int $mediaId): void
    {
        if (
            !$this->guardAdmin($redirect, false)
            || !$this->guardCsrf($redirect, 'admin/content', I18n::t('common.invalid_csrf'))
        ) {
            return;
        }

        $name = trim((string)($_POST['name'] ?? ''));
        $content = $this->content->find($contentId);
        if ($contentId <= 0 || $content === null) {
            $this->apiError('NOT_FOUND', I18n::t('content.not_found'), 404);
            return;
        }

        if (!$this->canManageContent($content)) {
            $this->apiError('FORBIDDEN', I18n::t('admin.access_denied'), 403);
            return;
        }

        if ($mediaId <= 0 || $name === '') {
            $this->apiError('INVALID_DATA', I18n::t('common.invalid_data'));
            return;
        }

        $media = $this->media->find($mediaId);
        if ($media === null) {
            $this->apiError('MEDIA_NOT_FOUND', I18n::t('media.not_found'), 404);
            return;
        }

        if (!$this->canManageMedia($media)) {
            $this->apiError('FORBIDDEN', I18n::t('admin.access_denied'), 403);
            return;
        }

        $result = $this->media->save([
            'name' => $name,
            'path' => (string)($media['path'] ?? ''),
            'path_webp' => (string)($media['path_webp'] ?? ''),
            'author' => (string)($media['author'] ?? ''),
        ], $mediaId);

        if (($result['success'] ?? false) !== true) {
            $this->apiError('RENAME_FAILED', (string)($result['errors']['name'] ?? I18n::t('media.rename_failed')));
            return;
        }

        $this->apiOk(['id' => $mediaId, 'name' => $name]);
    }

    public function attachmentAttachApiV1(callable $redirect, int $contentId, int $mediaId): void
    {
        if (
            !$this->guardAdmin($redirect, false)
            || !$this->guardCsrf($redirect, 'admin/content', I18n::t('common.invalid_csrf'))
        ) {
            return;
        }

        if ($contentId <= 0 || $mediaId <= 0) {
            $this->apiError('INVALID_DATA', I18n::t('common.invalid_data'));
            return;
        }

        $content = $this->content->find($contentId);
        if ($content === null) {
            $this->apiError('NOT_FOUND', I18n::t('content.not_found'), 404);
            return;
        }

        if (!$this->canManageContent($content)) {
            $this->apiError('FORBIDDEN', I18n::t('admin.access_denied'), 403);
            return;
        }

        if (!$this->content->attachMedia($contentId, $mediaId)) {
            $this->apiError('ATTACH_FAILED', I18n::t('content.attachment_attach_failed'));
            return;
        }

        $this->apiOk(['content_id' => $contentId, 'media_id' => $mediaId]);
    }

    private function editPath(int $id): string
    {
        return 'admin/content/edit?id=' . $id;
    }

    private function isValidExternalUrl(string $url): bool
    {
        if (!preg_match('#^https?://#i', $url)) {
            return false;
        }

        $parts = parse_url($url);
        $host = strtolower((string)($parts['host'] ?? ''));
        if ($host === '' || $host === 'localhost') {
            return false;
        }

        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    private function fetchRemoteTitle(string $url): string
    {
        $html = $this->fetchRemoteHtml($url);
        if ($html === '') {
            return '';
        }

        if (preg_match('/<meta[^>]+property=["\']og:title["\'][^>]+content=["\']([^"\']+)["\']/i', $html, $match) === 1) {
            return $this->sanitizeRemoteTitle($match[1]);
        }

        if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $html, $match) === 1) {
            return $this->sanitizeRemoteTitle($match[1]);
        }

        return '';
    }

    private function fetchRemoteHtml(string $url): string
    {
        if (function_exists('curl_init')) {
            $curl = curl_init($url);
            if ($curl !== false) {
                curl_setopt_array($curl, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_MAXREDIRS => 3,
                    CURLOPT_TIMEOUT => 4,
                    CURLOPT_CONNECTTIMEOUT => 2,
                    CURLOPT_USERAGENT => 'TinyCMS/1.0',
                ]);
                $result = curl_exec($curl);
                curl_close($curl);
                if (is_string($result) && $result !== '') {
                    return mb_substr($result, 0, 120000);
                }
            }
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 4,
                'header' => "User-Agent: TinyCMS/1.0\r\n",
            ],
        ]);
        $result = @file_get_contents($url, false, $context);
        return is_string($result) ? mb_substr($result, 0, 120000) : '';
    }

    private function sanitizeRemoteTitle(string $value): string
    {
        $clean = trim(html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        return preg_replace('/\s+/', ' ', $clean) ?? '';
    }

    private function resolveAutosavePayload(array $input, int $authorId): array
    {
        $name = trim((string)($input['name'] ?? ''));
        if ($name === '' && trim((string)($input['body'] ?? '')) !== '') {
            $name = I18n::t('content.untitled');
        }

        $author = trim((string)($input['author'] ?? ''));
        if ($this->isEditor()) {
            $author = $authorId > 0 ? (string)$authorId : '';
        } elseif ($author === '' && $authorId > 0) {
            $author = (string)$authorId;
        }

        return [
            'name' => $name,
            'status' => trim((string)($input['status'] ?? 'draft')) ?: 'draft',
            'excerpt' => (string)($input['excerpt'] ?? ''),
            'body' => (string)($input['body'] ?? ''),
            'author' => $author,
            'created' => (string)($input['created'] ?? ''),
        ];
    }

    private function normalizeContentInput(array $input, int $authorId): array
    {
        if (!$this->isEditor()) {
            return $input;
        }

        $input['author'] = $authorId > 0 ? (string)$authorId : '';
        return $input;
    }

    private function resolveListQuery(): array
    {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $defaultPerPage = PaginationConfig::perPage();
        $perPage = (int)($_GET['per_page'] ?? $defaultPerPage);
        $status = trim((string)($_GET['status'] ?? 'all'));
        $query = trim((string)($_GET['q'] ?? ''));

        if (!in_array($perPage, PaginationConfig::allowed(), true)) {
            $perPage = $defaultPerPage;
        }

        $availableStatuses = $this->content->statuses();
        if ($status !== 'all' && !in_array($status, $availableStatuses, true)) {
            $status = 'all';
        }

        return [$page, $perPage, $status, $query, $availableStatuses];
    }

    private function resolvePreviewPath(array $item): string
    {
        $previewPath = trim((string)($item['path_webp'] ?? ''));
        if ($previewPath !== '') {
            return (string)(preg_replace('/\.webp$/i', $this->thumbnailSuffix(), $previewPath) ?? $previewPath);
        }
        return trim((string)($item['path'] ?? ''));
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

    private function canDeleteContent(array $item): bool
    {
        return $this->canManageContent($item);
    }

    private function canDeleteMedia(array $item): bool
    {
        if (!$this->isEditor()) {
            return true;
        }

        return (int)($item['author'] ?? 0) === $this->currentUserId();
    }

    private function canManageMedia(array $item): bool
    {
        return $this->canDeleteMedia($item);
    }

    private function canManageContent(array $item): bool
    {
        if (!$this->isEditor()) {
            return true;
        }

        return (int)($item['author'] ?? 0) === $this->currentUserId();
    }

    private function mapLibraryItem(array $item): array
    {
        $createdAt = (string)($item['created'] ?? '');
        return [
            'id' => (int)($item['id'] ?? 0),
            'name' => (string)($item['name'] ?? ''),
            'can_edit' => $this->canManageMedia($item),
            'can_delete' => $this->canDeleteMedia($item),
            'preview_path' => $this->resolvePreviewPath($item),
            'path' => (string)($item['path'] ?? ''),
            'webp_path' => (string)($item['path_webp'] ?? ''),
            'created' => $createdAt,
            'created_label' => $this->formatDateTime($createdAt),
        ];
    }

    private function mapListItem(array $row): array
    {
        $createdAt = (string)($row['created'] ?? '');
        $createdStamp = $createdAt !== '' ? strtotime($createdAt) : false;
        return [
            'id' => (int)($row['id'] ?? 0),
            'name' => (string)($row['name'] ?? ''),
            'can_edit' => $this->canManageContent($row),
            'can_delete' => $this->canDeleteContent($row),
            'author_name' => (string)($row['author_name'] ?? '—'),
            'status' => (string)($row['status'] ?? 'draft'),
            'created' => $createdAt,
            'created_label' => $this->formatDateTime($createdAt),
            'is_planned' => $createdStamp !== false && $createdStamp > time(),
        ];
    }

    private function formatDateTime(string $value): string
    {
        $stamp = $value !== '' ? strtotime($value) : false;
        if ($stamp === false) {
            return '';
        }

        return date(APP_DATETIME_FORMAT, $stamp);
    }

    private function matchesLibraryQuery(array $item, string $query): bool
    {
        $needle = mb_strtolower(trim($query));
        if ($needle === '') {
            return true;
        }

        $haystacks = [
            mb_strtolower((string)($item['name'] ?? '')),
            mb_strtolower((string)($item['path'] ?? '')),
            mb_strtolower((string)($item['path_webp'] ?? '')),
        ];

        foreach ($haystacks as $haystack) {
            if ($haystack !== '' && mb_strpos($haystack, $needle) !== false) {
                return true;
            }
        }

        return false;
    }

    private function resolveSelectedTerms(array $item, ?int $contentId): array
    {
        if (array_key_exists('terms', $item)) {
            return $this->normalizeTermNames((string)$item['terms']);
        }

        if ($contentId !== null && $contentId > 0) {
            return $this->terms->namesByContent($contentId);
        }

        return [];
    }

    private function normalizeTermNames(string $rawTerms): array
    {
        $parts = preg_split('/[\n,]+/', $rawTerms) ?: [];
        $terms = [];

        foreach ($parts as $part) {
            $value = trim((string)$part);
            if ($value === '') {
                continue;
            }
            $key = mb_strtolower($value);
            $terms[$key] = mb_substr($value, 0, 255);
        }

        return array_values($terms);
    }

}
