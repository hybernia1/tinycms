<?php
declare(strict_types=1);

namespace App\Controller\Api;

use App\Controller\Admin\Admin;
use App\Service\Application\Auth;
use App\Service\Application\Settings as SettingsService;
use App\Service\Application\Upload as UploadService;
use App\Service\Support\Csrf;
use App\Service\Support\Flash;
use App\Service\Support\I18n;

final class Settings extends Admin
{
    public function __construct(
        Auth $authService,
        private SettingsService $settings,
        private UploadService $upload,
        Flash $flash,
        Csrf $csrf
    ) {
        parent::__construct($authService, $flash, $csrf);
    }

    public function submitApiV1(callable $redirect): void
    {
        if (!$this->guardApiAdminCsrf()) {
            return;
        }

        $fields = $this->settings->fields();
        $current = $this->settings->resolved();
        $faviconPath = (string)($current['favicon'] ?? '');
        $logoPath = (string)($current['logo'] ?? '');

        if ($this->hasUpload('favicon_file')) {
            $upload = $this->upload->uploadFavicon((array)$_FILES['favicon_file']);
            if (($upload['success'] ?? false) !== true) {
                $this->apiError('UPLOAD_FAILED', (string)($upload['error'] ?? I18n::t('upload.file_upload_failed')), 422);
                return;
            }

            $newPath = (string)($upload['data']['path'] ?? '');
            if ($newPath !== '') {
                if ($faviconPath !== '' && $faviconPath !== $newPath) {
                    $this->upload->deleteRelativeFile($faviconPath);
                }
                $faviconPath = $newPath;
            }
        }

        if ($this->hasUpload('logo_file')) {
            $upload = $this->upload->uploadLogo((array)$_FILES['logo_file']);
            if (($upload['success'] ?? false) !== true) {
                $this->apiError('UPLOAD_FAILED', (string)($upload['error'] ?? I18n::t('upload.file_upload_failed')), 422);
                return;
            }

            $newPath = (string)($upload['data']['path'] ?? '');
            if ($newPath !== '') {
                if ($logoPath !== '' && $logoPath !== $newPath) {
                    $this->upload->deleteRelativeFile($logoPath);
                }
                $logoPath = $newPath;
            }
        }

        $input = (array)($_POST['settings'] ?? []);
        $payload = $current;
        foreach ($input as $key => $rawValue) {
            if (!isset($fields[$key])) {
                continue;
            }

            $payload[$key] = (string)$rawValue;
        }
        $payload['favicon'] = $faviconPath;
        $payload['logo'] = $logoPath;

        $section = strtolower(trim((string)($_POST['settings_section'] ?? 'general')));
        if (!$this->sectionExists($fields, $section)) {
            $section = 'general';
        }

        $this->settings->save($payload);
        $this->apiOk([
            'message' => I18n::t('settings.saved'),
            'redirect' => $this->buildPath('admin/settings/' . $section),
        ]);
    }

    private function sectionExists(array $fields, string $section): bool
    {
        foreach ($fields as $field) {
            if ((string)($field['section'] ?? 'general') === $section) {
                return true;
            }
        }

        return false;
    }
}
