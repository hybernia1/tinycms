<?php
declare(strict_types=1);

namespace App\Controller\Api;

use App\Controller\Admin\Admin;
use App\Service\Application\Auth;
use App\Service\Application\Widgets as WidgetsService;
use App\Service\Support\Csrf;
use App\Service\Support\Flash;
use App\Service\Support\I18n;

final class Widgets extends Admin
{
    public function __construct(
        Auth $authService,
        private WidgetsService $widgets,
        Flash $flash,
        Csrf $csrf
    ) {
        parent::__construct($authService, $flash, $csrf);
    }

    public function submitApiV1(): void
    {
        if (!$this->guardApiAdminCsrf()) {
            return;
        }

        $this->widgets->boot(BASE_DIR);
        $this->widgets->save($this->layoutFromInput((array)($_POST['widgets'] ?? [])));
        $this->apiOk([
            'message' => I18n::t('widgets.saved'),
            'redirect' => $this->buildPath('admin/widgets'),
        ]);
    }

    private function layoutFromInput(array $input): array
    {
        $layout = [];
        foreach ($input as $sidebar => $widgets) {
            if (!is_array($widgets)) {
                continue;
            }

            foreach ($widgets as $row) {
                if (!is_array($row)) {
                    continue;
                }

                $layout[(string)$sidebar][] = [
                    'id' => (string)($row['id'] ?? ''),
                    'type' => (string)($row['type'] ?? ''),
                    'enabled' => (int)((int)($row['enabled'] ?? 0) === 1),
                    'settings' => (array)($row['settings'] ?? []),
                ];
            }
        }

        return $layout;
    }
}
