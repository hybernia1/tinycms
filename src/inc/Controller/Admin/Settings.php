<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\Application\Auth;
use App\Service\Application\Settings as SettingsService;
use App\Service\Support\Csrf;
use App\Service\Support\Flash;
use App\View\AdminView;

final class Settings extends Admin
{
    public function __construct(
        private AdminView $pages,
        Auth $authService,
        private SettingsService $settings,
        Flash $flash,
        Csrf $csrf
    ) {
        parent::__construct($authService, $flash, $csrf);
    }

    public function form(callable $redirect): void
    {
        $this->renderForm($redirect, 'general');
    }

    public function sectionForm(callable $redirect, string $section): void
    {
        $this->renderForm($redirect, $section);
    }

    private function renderForm(callable $redirect, string $section): void
    {
        if (!$this->guardAdmin($redirect, false)) {
            return;
        }

        $fields = $this->settings->fields();
        $normalizedSection = strtolower(trim($section));
        if ($normalizedSection === '') {
            $normalizedSection = 'general';
        }
        if (!$this->sectionExists($fields, $normalizedSection)) {
            $redirect('admin/settings');
            return;
        }

        $resolved = $this->settings->resolved();
        if (isset($fields['front_home_content'])) {
            $fields['front_home_content']['selected_label'] = $this->settings->publishedContentLabel((int)($resolved['front_home_content'] ?? 0));
        }
        $this->pages->adminSettingsForm($fields, $resolved, $normalizedSection);
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
