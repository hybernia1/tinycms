<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\Feature\AuthService;
use App\Service\Support\CsrfService;
use App\Service\Support\FlashService;
use App\Service\Feature\SettingsService;
use App\Service\Feature\UploadService;
use App\Service\Support\I18n;
use App\View\PageView;

final class AdminSettingsController extends BaseAdminController
{
    private PageView $pages;
    private SettingsService $settings;
    private UploadService $upload;

    public function __construct(PageView $pages, AuthService $authService, SettingsService $settings, UploadService $upload, FlashService $flash, CsrfService $csrf)
    {
        parent::__construct($authService, $flash, $csrf);
        $this->pages = $pages;
        $this->settings = $settings;
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
        $resolved = $this->settings->resolved();

        if ($this->hasUpload('favicon_file')) {
            $upload = $this->upload->uploadFavicon($_FILES['favicon_file'] ?? []);

            if (($upload['success'] ?? false) !== true) {
                $this->flash->add('error', (string)($upload['error'] ?? I18n::t('upload.file_upload_failed')));
                $redirect('admin/settings');
                return;
            }

            $faviconPath = trim((string)($upload['data']['path'] ?? ''));
            if ($faviconPath !== '') {
                $input['favicon'] = $faviconPath;
                $previous = trim((string)($resolved['favicon'] ?? ''));
                if ($previous !== '' && $previous !== $faviconPath) {
                    $this->upload->deleteRelativeFile($previous);
                }
            }
        }

        $this->settings->save($input);
        I18n::setLocale((string)($input['app_lang'] ?? APP_LANG));
        I18n::setTheme((string)($this->settings->resolved()['theme'] ?? 'default'));
        $this->flash->add('success', I18n::t('settings.saved', 'Settings saved.'));
        $redirect('admin/settings');
    }

    private function hasUpload(string $field): bool
    {
        return isset($_FILES[$field]) && (int)($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
    }
}
