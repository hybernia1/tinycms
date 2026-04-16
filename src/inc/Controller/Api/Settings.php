<?php
declare(strict_types=1);

namespace App\Controller\Api;

use App\Controller\Admin\BaseAdmin;
use App\Service\Application\Auth;
use App\Service\Application\Settings as SettingsService;
use App\Service\Application\Upload as UploadService;
use App\Service\Support\Csrf;
use App\Service\Support\Flash;
use App\Service\Support\I18n;

final class Settings extends BaseAdmin
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

        $payload = [
            'app_lang' => (string)($_POST['settings']['app_lang'] ?? ''),
            'theme' => (string)($_POST['settings']['theme'] ?? ''),
            'sitename' => (string)($_POST['settings']['sitename'] ?? ''),
            'siteauthor' => (string)($_POST['settings']['siteauthor'] ?? ''),
            'meta_title' => (string)($_POST['settings']['meta_title'] ?? ''),
            'meta_description' => (string)($_POST['settings']['meta_description'] ?? ''),
            'favicon' => $faviconPath,
            'logo' => $logoPath,
        ];

        $this->settings->save($payload);
        $this->apiOk([
            'message' => I18n::t('settings.saved'),
        ]);
    }
}
