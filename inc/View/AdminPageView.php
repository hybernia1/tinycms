<?php
declare(strict_types=1);

namespace App\View;

use App\Service\Feature\SettingsService;
use App\Service\Feature\UploadService;
use App\Service\Support\I18n;

final class AdminPageView
{
    public function __construct(
        private View $view,
        private SettingsService $settings
    ) {
    }

    public function dashboard(?array $user): void
    {
        $this->renderAdmin('admin/dashboard', [
            'user' => $user,
            'pageTitle' => I18n::t('admin.menu.dashboard'),
        ]);
    }

    public function usersList(array $pagination, array $allowedPerPage, string $status, string $query, array $statusCounts): void
    {
        $this->renderAdmin('admin/users/list', [
            'pagination' => $pagination,
            'allowedPerPage' => $allowedPerPage,
            'status' => $status,
            'query' => $query,
            'statusCounts' => $statusCounts,
            'pageTitle' => I18n::t('admin.menu.users'),
        ]);
    }

    public function settingsForm(array $fields, array $values): void
    {
        $this->renderAdmin('admin/settings/form', [
            'fields' => $fields,
            'values' => $values,
            'pageTitle' => I18n::t('admin.menu.settings'),
        ]);
    }

    public function usersForm(string $mode, array $user, array $errors): void
    {
        $this->renderAdmin('admin/users/form', [
            'mode' => $mode,
            'user' => $user,
            'errors' => $errors,
            'pageTitle' => $mode === 'add' ? I18n::t('admin.add_user') : I18n::t('admin.edit_user'),
        ]);
    }

    public function contentList(array $pagination, array $allowedPerPage, string $status, string $query, array $availableStatuses, array $statusCounts): void
    {
        $this->renderAdmin('admin/content/list', [
            'pagination' => $pagination,
            'allowedPerPage' => $allowedPerPage,
            'status' => $status,
            'query' => $query,
            'availableStatuses' => $availableStatuses,
            'statusCounts' => $statusCounts,
            'pageTitle' => I18n::t('admin.menu.content'),
        ]);
    }

    public function contentForm(string $mode, array $item, array $errors, array $availableStatuses, array $authors, array $selectedTerms = []): void
    {
        $this->renderAdmin('admin/content/form', [
            'mode' => $mode,
            'item' => $item,
            'errors' => $errors,
            'availableStatuses' => $availableStatuses,
            'authors' => $authors,
            'selectedTerms' => $selectedTerms,
            'pageTitle' => $mode === 'add' ? I18n::t('admin.add_content') : I18n::t('admin.edit_content'),
        ]);
    }

    public function termList(array $pagination, array $allowedPerPage, string $status, string $query, array $statusCounts): void
    {
        $this->renderAdmin('admin/terms/list', [
            'pagination' => $pagination,
            'allowedPerPage' => $allowedPerPage,
            'status' => $status,
            'query' => $query,
            'statusCounts' => $statusCounts,
            'pageTitle' => I18n::t('admin.menu.terms'),
        ]);
    }

    public function termForm(string $mode, array $item, array $errors, array $usages = []): void
    {
        $this->renderAdmin('admin/terms/form', [
            'mode' => $mode,
            'item' => $item,
            'errors' => $errors,
            'usages' => $usages,
            'pageTitle' => $mode === 'add' ? I18n::t('admin.add_term') : I18n::t('admin.edit_term'),
        ]);
    }

    public function mediaList(array $pagination, array $allowedPerPage, string $status, string $query, array $statusCounts): void
    {
        $this->renderAdmin('admin/media/list', [
            'pagination' => $pagination,
            'allowedPerPage' => $allowedPerPage,
            'status' => $status,
            'query' => $query,
            'statusCounts' => $statusCounts,
            'pageTitle' => I18n::t('admin.menu.media'),
        ]);
    }

    public function mediaForm(string $mode, array $item, array $errors, array $authors, array $usages = [], array $navigation = []): void
    {
        $this->renderAdmin('admin/media/form', [
            'mode' => $mode,
            'item' => $item,
            'errors' => $errors,
            'authors' => $authors,
            'usages' => $usages,
            'navigation' => $navigation,
            'pageTitle' => $mode === 'add' ? I18n::t('admin.add_media') : I18n::t('admin.edit_media'),
        ]);
    }

    private function renderAdmin(string $template, array $data): void
    {
        $this->view->render('admin/layout', $template, array_merge(
            $data,
            $this->adminBranding(),
            [
                'adminMenu' => $this->adminMenu(),
                'imageUploadAccept' => UploadService::imageAccept(),
                'siteImageUploadAccept' => UploadService::siteImageAccept(),
                'imageUploadTypesLabel' => UploadService::imageExtensionsLabel(),
                'siteImageUploadTypesLabel' => UploadService::siteImageExtensionsLabel(),
            ]
        ));
    }

    private function adminBranding(): array
    {
        $settings = $this->settings->resolved();
        return [
            'siteName' => (string)($settings['sitename'] ?? 'TinyCMS'),
            'siteFavicon' => (string)($settings['favicon'] ?? ''),
            'siteLogo' => (string)($settings['logo'] ?? ''),
        ];
    }

    private function adminMenu(): array
    {
        return [
            ['label' => I18n::t('admin.menu.dashboard'), 'url' => 'admin/dashboard', 'icon' => 'dashboard'],
            ['label' => I18n::t('admin.menu.users'), 'url' => 'admin/users', 'icon' => 'users'],
            ['label' => I18n::t('admin.menu.content'), 'url' => 'admin/content', 'icon' => 'content'],
            ['label' => I18n::t('admin.menu.media'), 'url' => 'admin/media', 'icon' => 'media'],
            ['label' => I18n::t('admin.menu.terms'), 'url' => 'admin/terms', 'icon' => 'terms'],
            ['label' => I18n::t('admin.menu.settings'), 'url' => 'admin/settings', 'icon' => 'settings'],
        ];
    }
}
