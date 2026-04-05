<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\Feature\AuthService;
use App\Service\Support\CsrfService;
use App\Service\Support\FlashService;
use App\Service\Feature\MediaService;
use App\Service\Feature\SettingsService;
use App\Service\Feature\UploadService;
use App\Service\Support\I18n;
use App\View\PageView;

final class AdminSettingsController extends BaseAdminController
{
    private PageView $pages;
    private SettingsService $settings;
    private MediaService $media;
    private UploadService $upload;

    public function __construct(PageView $pages, AuthService $authService, SettingsService $settings, MediaService $media, UploadService $upload, FlashService $flash, CsrfService $csrf)
    {
        parent::__construct($authService, $flash, $csrf);
        $this->pages = $pages;
        $this->settings = $settings;
        $this->media = $media;
        $this->upload = $upload;
    }

    public function form(callable $redirect): void
    {
        if (!$this->guardSuperAdmin($redirect)) {
            return;
        }

        $fields = $this->settings->fields();
        $values = array_replace($this->settings->defaults(), $this->settings->values());
        $this->pages->adminSettingsForm($fields, $values);
    }

    public function submit(callable $redirect): void
    {
        if (
            !$this->guardSuperAdmin($redirect)
            || !$this->guardCsrf($redirect, 'admin/settings', I18n::t('common.csrf_expired'))
        ) {
            return;
        }

        $input = (array)($_POST['settings'] ?? []);
        $this->settings->save($input);
        I18n::setLocale((string)($input['app_lang'] ?? APP_LANG));
        I18n::setTheme((string)($this->settings->resolved()['theme'] ?? 'default'));
        $this->flash->add('success', I18n::t('settings.saved', 'Settings saved.'));
        $redirect('admin/settings');
    }

    public function mediaLibraryApiV1(callable $redirect): void
    {
        if (!$this->guardSuperAdmin($redirect, false)) {
            return;
        }

        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = (int)($_GET['per_page'] ?? 10);
        if ($perPage <= 0 || $perPage > 20) {
            $perPage = 10;
        }

        $query = trim((string)($_GET['q'] ?? ''));
        $pagination = $this->media->paginate($page, $perPage, $query);
        $items = array_map(fn(array $item): array => $this->mapLibraryItem($item), (array)($pagination['data'] ?? []));

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

    public function mediaLibraryUploadApiV1(callable $redirect): void
    {
        if (
            !$this->guardSuperAdmin($redirect, false)
            || !$this->guardCsrf($redirect, 'admin/settings', I18n::t('common.invalid_csrf', 'Invalid CSRF token.'))
        ) {
            return;
        }

        $upload = $this->upload->uploadImage($_FILES['file'] ?? []);
        if (($upload['success'] ?? false) !== true) {
            $this->respondJson(['ok' => false, 'error' => ['code' => 'UPLOAD_FAILED', 'message' => (string)($upload['error'] ?? I18n::t('upload.file_upload_failed', 'File upload failed.'))]], 422);
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
            $this->respondJson(['ok' => false, 'error' => ['code' => 'SAVE_FAILED', 'message' => I18n::t('media.save_failed', 'Could not save media.')]], 422);
            return;
        }

        $media = $this->media->find($mediaId);
        if ($media === null) {
            $this->respondJson(['ok' => false, 'error' => ['code' => 'NOT_FOUND', 'message' => I18n::t('media.not_found', 'Media not found.')]], 404);
            return;
        }

        $this->respondJson(['ok' => true, 'data' => $this->mapLibraryItem($media)]);
    }

    private function mapLibraryItem(array $item): array
    {
        $path = trim((string)($item['path'] ?? ''));
        $webpPath = trim((string)($item['path_webp'] ?? ''));

        $previewPath = $webpPath !== '' ? $webpPath : $path;

        return [
            'id' => (int)($item['id'] ?? 0),
            'name' => (string)($item['name'] ?? ''),
            'path' => $path,
            'webp_path' => $webpPath,
            'preview_path' => $previewPath,
            'created' => (string)($item['created'] ?? ''),
        ];
    }
}
