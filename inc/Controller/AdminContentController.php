<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\Feature\AuthService;
use App\Service\Feature\ContentService;
use App\Service\Feature\MediaService;
use App\Service\Feature\UploadService;
use App\Service\Support\CsrfService;
use App\Service\Support\FlashService;
use App\Service\Feature\UserService;
use App\View\PageView;

final class AdminContentController extends BaseAdminController
{
    private const PER_PAGE_ALLOWED = [10, 20, 50];
    private const FORM_STATE_KEY = 'admin_content_form_state';

    public function __construct(
        private PageView $pages,
        AuthService $authService,
        private ContentService $content,
        private MediaService $media,
        private UploadService $upload,
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

    public function thumbnailUploadSubmit(callable $redirect): void
    {
        if (
            !$this->guardAdmin($redirect, false)
            || !$this->guardCsrf($redirect, 'admin/content', 'Neplatný CSRF token.')
        ) {
            return;
        }

        $id = (int)($_GET['id'] ?? 0);
        $item = $this->content->find($id);

        if ($item === null) {
            $this->flash->add('error', 'Obsah nenalezen.');
            $redirect('admin/content');
            return;
        }

        $upload = $this->upload->uploadImage($_FILES['thumbnail'] ?? []);
        if (($upload['success'] ?? false) !== true) {
            $this->flash->add('error', (string)($upload['error'] ?? 'Soubor se nepodařilo nahrát.'));
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
            $this->flash->add('error', 'Náhled se nepodařilo uložit.');
            $redirect($this->editPath($id));
            return;
        }

        $this->flash->add('success', 'Náhled byl nahrán.');
        $redirect($this->editPath($id));
    }

    public function thumbnailDetachSubmit(callable $redirect): void
    {
        if (
            !$this->guardAdmin($redirect, false)
            || !$this->guardCsrf($redirect, 'admin/content', 'Neplatný CSRF token.')
        ) {
            return;
        }

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0 || $this->content->find($id) === null) {
            $this->flash->add('error', 'Obsah nenalezen.');
            $redirect('admin/content');
            return;
        }

        $ok = $this->content->setThumbnail($id, null);
        $this->flash->add($ok ? 'success' : 'error', $ok ? 'Náhled byl odpojen.' : 'Náhled se nepodařilo odpojit.');
        $redirect($this->editPath($id));
    }

    public function thumbnailSelectSubmit(callable $redirect): void
    {
        if (
            !$this->guardAdmin($redirect, false)
            || !$this->guardCsrf($redirect, 'admin/content', 'Neplatný CSRF token.')
        ) {
            return;
        }

        $id = (int)($_POST['id'] ?? 0);
        $mediaId = (int)($_POST['media_id'] ?? 0);
        $item = $this->content->find($id);

        if ($item === null) {
            $this->flash->add('error', 'Obsah nenalezen.');
            $redirect('admin/content');
            return;
        }

        if ($mediaId <= 0 || $this->media->find($mediaId) === null) {
            $this->flash->add('error', 'Médium nenalezeno.');
            $redirect($this->editPath($id));
            return;
        }

        $ok = $this->content->setThumbnail($id, $mediaId);
        $this->flash->add($ok ? 'success' : 'error', $ok ? 'Náhled byl přiřazen.' : 'Náhled se nepodařilo přiřadit.');
        $redirect($this->editPath($id));
    }

    public function thumbnailDeleteSubmit(callable $redirect): void
    {
        if (
            !$this->guardAdmin($redirect, false)
            || !$this->guardCsrf($redirect, 'admin/content', 'Neplatný CSRF token.')
        ) {
            return;
        }

        $id = (int)($_POST['id'] ?? 0);
        $item = $this->content->find($id);

        if ($item === null) {
            $this->flash->add('error', 'Obsah nenalezen.');
            $redirect('admin/content');
            return;
        }

        $thumbnailId = (int)($item['thumbnail'] ?? 0);
        if ($thumbnailId <= 0) {
            $this->flash->add('info', 'Obsah nemá přiřazený náhled.');
            $redirect($this->editPath($id));
            return;
        }

        $media = $this->media->find($thumbnailId);
        if ($media === null || !$this->media->delete($thumbnailId)) {
            $this->flash->add('error', 'Náhled se nepodařilo smazat.');
            $redirect($this->editPath($id));
            return;
        }

        $this->upload->deleteMediaFiles($media);
        $this->flash->add('success', 'Náhled byl odstraněn z databáze i disku.');
        $redirect($this->editPath($id));
    }

    public function mediaLibrary(callable $redirect): void
    {
        if (!$this->guardAdmin($redirect, false)) {
            return;
        }

        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = (int)($_GET['per_page'] ?? 10);
        $query = trim((string)($_GET['q'] ?? ''));
        $currentMediaId = (int)($_GET['current_media_id'] ?? 0);
        if ($perPage <= 0 || $perPage > 20) {
            $perPage = 10;
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

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'items' => $items,
            'page' => (int)($pagination['page'] ?? 1),
            'per_page' => (int)($pagination['per_page'] ?? $perPage),
            'total_pages' => (int)($pagination['total_pages'] ?? 1),
            'query' => $query,
        ], JSON_UNESCAPED_UNICODE);
    }

    public function mediaLibraryDeleteSubmit(callable $redirect): void
    {
        if (
            !$this->guardAdmin($redirect, false)
            || !$this->guardCsrf($redirect, 'admin/content', 'Neplatný CSRF token.')
        ) {
            return;
        }

        $contentId = (int)($_POST['content_id'] ?? 0);
        $mediaId = (int)($_POST['media_id'] ?? 0);
        $item = $this->content->find($contentId);

        if ($item === null) {
            $this->flash->add('error', 'Obsah nenalezen.');
            $redirect('admin/content');
            return;
        }

        if ($mediaId <= 0) {
            $this->flash->add('error', 'Médium nenalezeno.');
            $redirect($this->editPath($contentId));
            return;
        }

        $media = $this->media->find($mediaId);
        if ($media === null || !$this->media->delete($mediaId)) {
            $this->flash->add('error', 'Médium se nepodařilo smazat.');
            $redirect($this->editPath($contentId));
            return;
        }

        if ((int)($item['thumbnail'] ?? 0) === $mediaId) {
            $this->content->setThumbnail($contentId, null);
        }

        $this->upload->deleteMediaFiles($media);
        $this->flash->add('success', 'Médium bylo smazáno.');
        $redirect($this->editPath($contentId));
    }

    public function mediaLibraryUploadSubmit(callable $redirect): void
    {
        if (
            !$this->guardAdmin($redirect, false)
            || !$this->guardCsrf($redirect, 'admin/content', 'Neplatný CSRF token.')
        ) {
            return;
        }

        $contentId = (int)($_POST['content_id'] ?? 0);
        if ($contentId <= 0 || $this->content->find($contentId) === null) {
            $this->jsonError('Obsah nenalezen.');
            return;
        }

        $upload = $this->upload->uploadImage($_FILES['thumbnail'] ?? []);
        if (($upload['success'] ?? false) !== true) {
            $this->jsonError((string)($upload['error'] ?? 'Soubor se nepodařilo nahrát.'));
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
            $this->jsonError('Médium se nepodařilo uložit.');
            return;
        }

        $media = $this->media->find($mediaId);
        $previewPath = $media !== null ? $this->resolvePreviewPath($media) : (string)($data['path'] ?? '');
        $this->jsonSuccess([
            'id' => $mediaId,
            'name' => (string)($media['name'] ?? ($data['name'] ?? '')),
            'preview_path' => $previewPath,
            'path' => (string)($media['path'] ?? ($data['path'] ?? '')),
            'created' => (string)($media['created'] ?? date('Y-m-d H:i:s')),
        ]);
    }

    public function mediaLibraryRenameSubmit(callable $redirect): void
    {
        if (
            !$this->guardAdmin($redirect, false)
            || !$this->guardCsrf($redirect, 'admin/content', 'Neplatný CSRF token.')
        ) {
            return;
        }

        $contentId = (int)($_POST['content_id'] ?? 0);
        $mediaId = (int)($_POST['media_id'] ?? 0);
        $name = trim((string)($_POST['name'] ?? ''));

        if ($contentId <= 0 || $this->content->find($contentId) === null) {
            $this->jsonError('Obsah nenalezen.');
            return;
        }

        if ($mediaId <= 0 || $name === '') {
            $this->jsonError('Neplatná data.');
            return;
        }

        $media = $this->media->find($mediaId);
        if ($media === null) {
            $this->jsonError('Médium nenalezeno.');
            return;
        }

        $result = $this->media->save([
            'name' => $name,
            'path' => (string)($media['path'] ?? ''),
            'path_webp' => (string)($media['path_webp'] ?? ''),
            'author' => (string)($media['author'] ?? ''),
        ], $mediaId);

        if (($result['success'] ?? false) !== true) {
            $this->jsonError((string)($result['errors']['name'] ?? 'Název se nepodařilo uložit.'));
            return;
        }

        $this->jsonSuccess(['id' => $mediaId, 'name' => $name]);
    }

    private function editPath(int $id): string
    {
        return 'admin/content/edit?id=' . $id;
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

    private function mapLibraryItem(array $item): array
    {
        return [
            'id' => (int)($item['id'] ?? 0),
            'name' => (string)($item['name'] ?? ''),
            'preview_path' => $this->resolvePreviewPath($item),
            'path' => (string)($item['path'] ?? ''),
            'created' => (string)($item['created'] ?? ''),
        ];
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
