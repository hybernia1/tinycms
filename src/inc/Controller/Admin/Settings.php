<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\Application\Auth;
use App\Service\Support\Csrf;
use App\Service\Support\Flash;
use App\Service\Application\Settings as SettingsService;
use App\Service\Application\Upload as UploadService;
use App\Service\Support\I18n;
use App\View\AdminView;

final class Settings extends BaseAdmin
{
    private AdminView $pages;
    private SettingsService $settings;
    private UploadService $upload;

    public function __construct(AdminView $pages, Auth $authService, SettingsService $settings, UploadService $upload, Flash $flash, Csrf $csrf)
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
        I18n::setLocale((string)($this->settings->resolved()['app_lang'] ?? APP_LANG));
        $this->apiOk([
            'redirect' => $this->buildPath('admin/settings'),
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
