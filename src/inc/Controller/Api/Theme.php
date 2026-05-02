<?php
declare(strict_types=1);

namespace App\Controller\Api;

use App\Controller\Admin\Admin;
use App\Service\Application\Auth;
use App\Service\Application\Theme as ThemeService;
use App\Service\Support\Csrf;
use App\Service\Support\Flash;
use App\Service\Support\I18n;

final class Theme extends Admin
{
    public function __construct(
        Auth $authService,
        private ThemeService $themes,
        Flash $flash,
        Csrf $csrf
    ) {
        parent::__construct($authService, $flash, $csrf);
    }

    public function saveApiV1(): void
    {
        if (!$this->guardApiAdminCsrf()) {
            return;
        }

        $result = $this->themes->save((array)($_POST['theme'] ?? []));
        if (($result['success'] ?? false) === true) {
            $this->apiOk([
                'message' => I18n::t('themes.saved'),
                'redirect' => $this->buildPath('admin/themes'),
            ]);
            return;
        }

        $this->apiError('SAVE_FAILED', I18n::t('themes.save_failed'), 422, [
            'errors' => $result['errors'] ?? [],
        ]);
    }
}
