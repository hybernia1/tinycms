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
            'sitename' => (string)($_POST['settings']['sitename'] ?? ''),
            'siteauthor' => (string)($_POST['settings']['siteauthor'] ?? ''),
            'meta_description' => (string)($_POST['settings']['meta_description'] ?? ''),
            'front_home_mode' => (string)($_POST['settings']['front_home_mode'] ?? 'latest'),
            'front_home_content' => (string)($_POST['settings']['front_home_content'] ?? ''),
            'front_posts_per_page' => (string)($_POST['settings']['front_posts_per_page'] ?? APP_POSTS_PER_PAGE),
            'front_theme' => (string)($_POST['settings']['front_theme'] ?? 'default'),
            'allow_registration' => (string)($_POST['settings']['allow_registration'] ?? '0'),
            'favicon' => $faviconPath,
            'logo' => $logoPath,
            'website_email' => (string)($_POST['settings']['website_email'] ?? ''),
        ];

        $this->settings->save($payload);
        $this->apiOk([
            'message' => I18n::t('settings.saved'),
            'redirect' => $this->buildPath('admin/settings'),
        ]);
    }
}
