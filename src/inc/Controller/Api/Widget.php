<?php
declare(strict_types=1);

namespace App\Controller\Api;

use App\Controller\Admin\Admin;
use App\Service\Application\Auth;
use App\Service\Application\Widget as WidgetService;
use App\Service\Support\Csrf;
use App\Service\Support\Flash;
use App\Service\Support\I18n;

final class Widget extends Admin
{
    public function __construct(
        Auth $authService,
        private WidgetService $widgets,
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

        $result = $this->widgets->save($_POST);
        if (($result['success'] ?? false) === true) {
            $this->apiOk([
                'message' => I18n::t('widgets.updated'),
            ]);
            return;
        }

        $this->apiError('UPDATE_FAILED', I18n::t('widgets.update_failed'), 422, [
            'errors' => $result['errors'] ?? [],
        ]);
    }
}
