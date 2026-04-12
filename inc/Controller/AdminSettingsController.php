<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\Feature\AuthService;
use App\Service\Support\CsrfService;
use App\Service\Support\FlashService;
use App\Service\Feature\SettingsService;
use App\Service\Feature\UploadService;
use App\Service\Support\I18n;
use App\View\AdminView;

final class AdminSettingsController extends BaseAdminController
{
    private AdminView $pages;
    private SettingsService $settings;
    private UploadService $upload;

    public function __construct(AdminView $pages, AuthService $authService, SettingsService $settings, UploadService $upload, FlashService $flash, CsrfService $csrf)
    {
        parent::__construct($authService, $flash, $csrf);
        $this->pages = $pages;
        $this->settings = $settings;
        $this->upload = $upload;
    }

    public function form(callable $redirect): void
    {
        if (!$this->guardAdmin($redirect)) {
            return;
        }

        $fields = $this->settings->fields();
        $values = array_replace($this->settings->defaults(), $this->settings->values());
        $this->pages->adminSettingsForm($fields, $values);
    }

    public function submitApiV1(callable $redirect): void
    {
        if (!$this->guardApiAdminCsrf(I18n::t('common.csrf_expired'))) {
            return;
        }

        $input = (array)($_POST['settings'] ?? []);
        $resolved = $this->settings->resolved();

        if (!$this->handleSiteImageUploadApi('favicon_file', 'favicon', $input, $resolved, fn(array $file): array => $this->upload->uploadFavicon($file))) {
            return;
        }
        if (!$this->handleSiteImageUploadApi('logo_file', 'logo', $input, $resolved, fn(array $file): array => $this->upload->uploadLogo($file))) {
            return;
        }

        $this->settings->save($input);
        I18n::setLocale((string)($input['app_lang'] ?? APP_LANG));
        I18n::setTheme((string)($this->settings->resolved()['theme'] ?? 'default'));
        $this->apiOk([
            'message' => I18n::t('settings.saved'),
        ]);
    }

    private function handleSiteImageUploadApi(string $field, string $settingKey, array &$input, array $resolved, callable $uploader): bool
    {
        if (!$this->hasUpload($field)) {
            return true;
        }

        $upload = $uploader((array)($_FILES[$field] ?? []));
        if (($upload['success'] ?? false) !== true) {
            $this->apiError('UPLOAD_FAILED', (string)($upload['error'] ?? I18n::t('upload.file_upload_failed')), 422);
            return false;
        }

        $path = trim((string)($upload['data']['path'] ?? ''));
        if ($path === '') {
            return true;
        }

        $input[$settingKey] = $path;
        $previous = trim((string)($resolved[$settingKey] ?? ''));
        if ($previous !== '' && $previous !== $path) {
            $this->upload->deleteRelativeFile($previous);
        }

        return true;
    }

}
