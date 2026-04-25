<?php
declare(strict_types=1);

namespace App\Controller\Api;

use App\Controller\Admin\Admin;
use App\Service\Application\Auth;
use App\Service\Application\Settings as SettingsService;
use App\Service\Support\Csrf;
use App\Service\Support\Flash;
use App\Service\Support\I18n;

final class Settings extends Admin
{
    public function __construct(
        Auth $authService,
        private SettingsService $settings,
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

        $fields = $this->settings->fields();
        $current = $this->settings->resolved();
        $input = (array)($_POST['settings'] ?? []);
        $payload = $current;
        foreach ($input as $key => $rawValue) {
            if (!isset($fields[$key])) {
                continue;
            }

            $payload[$key] = (string)$rawValue;
        }

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
