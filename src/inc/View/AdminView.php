<?php
declare(strict_types=1);

namespace App\View;

use App\Service\Application\Settings;
use App\Service\Application\Upload;
use App\Service\Support\I18n;

final class AdminView
{
    private View $view;
    private Settings $settings;

    public function __construct(View $view, Settings $settings)
    {
        $this->view = $view;
        $this->settings = $settings;
    }

    public function adminDashboard(?array $user): void
    {
        $this->renderAdmin('admin/dashboard', [
            'user' => $user,
            'pageTitle' => I18n::t('admin.menu.dashboard'),
        ]);
    }

    public function adminLoginForm(array $state): void
    {
        $settings = $this->settings->resolved();
        $this->renderAdminAuth('admin/auth/login', array_merge($state, [
            'pageTitle' => I18n::t('auth.login'),
            'allowRegistration' => (int)($settings['allow_registration'] ?? 0) === 1,
        ]));
    }

    public function adminRegisterForm(array $state): void
    {
        $this->renderAdminAuth('admin/auth/register', array_merge($state, [
            'pageTitle' => I18n::t('auth.register'),
        ]));
    }

    public function adminLostForm(array $state): void
    {
        $this->renderAdminAuth('admin/auth/lost', array_merge($state, [
            'pageTitle' => I18n::t('auth.lost_password'),
        ]));
    }

    public function adminUsersList(array $pagination, string $status, string $query, array $statusCounts): void
    {
        $this->renderAdmin('admin/users/list', [
            'listBase' => $this->adminListBase('users', $pagination, $status, $query, $statusCounts),
            'pageTitle' => I18n::t('admin.menu.users'),
            'headerAction' => $this->linkHeaderAction('admin/users/add', I18n::t('admin.add_user')),
        ]);
    }

    public function adminSettingsForm(array $fields, array $values): void
    {
        $this->renderAdmin('admin/settings/form', [
            'fields' => $fields,
            'values' => $values,
            'pageTitle' => I18n::t('admin.menu.settings'),
            'headerAction' => $this->submitHeaderAction('#settings-form'),
        ]);
    }

    public function adminUsersForm(string $mode, array $user, array $errors): void
    {
        $this->renderAdmin('admin/users/form', [
            'mode' => $mode,
            'user' => $user,
            'errors' => $errors,
            'pageTitle' => $mode === 'add' ? I18n::t('admin.add_user') : I18n::t('admin.edit_user'),
            'headerAction' => $this->submitHeaderAction('#users-form'),
        ]);
    }

    public function adminContentList(array $pagination, string $status, string $query, array $availableStatuses, array $statusCounts): void
    {
        $this->renderAdmin('admin/content/list', [
            'listBase' => $this->adminListBase('content', $pagination, $status, $query, $statusCounts),
            'availableStatuses' => $availableStatuses,
            'pageTitle' => I18n::t('admin.menu.content'),
            'headerAction' => $this->linkHeaderAction('admin/content/add', I18n::t('admin.add_content')),
        ]);
    }

    public function adminCommentList(array $pagination, string $status, string $query, array $statusCounts): void
    {
        $this->renderAdmin('admin/comments/list', [
            'listBase' => $this->adminListBase('comments', $pagination, $status, $query, $statusCounts),
            'pageTitle' => I18n::t('admin.menu.comments'),
        ]);
    }

    public function adminCommentForm(array $item, array $errors, array $publishedIn): void
    {
        $this->renderAdmin('admin/comments/form', [
            'item' => $item,
            'errors' => $errors,
            'publishedIn' => $publishedIn,
            'pageTitle' => I18n::t('admin.edit_comment'),
            'headerAction' => $this->submitHeaderAction('#comments-form'),
        ]);
    }

    public function adminContentForm(string $mode, array $item, array $errors, array $availableStatuses, array $contentTypes, array $authors, array $selectedTerms = []): void
    {
        $this->renderAdmin('admin/content/form', [
            'mode' => $mode,
            'item' => $item,
            'errors' => $errors,
            'availableStatuses' => $availableStatuses,
            'contentTypes' => $contentTypes,
            'authors' => $authors,
            'selectedTerms' => $selectedTerms,
            'pageTitle' => $mode === 'add' ? I18n::t('admin.add_content') : I18n::t('admin.edit_content'),
            'headerAction' => $this->contentMenuHeaderAction($mode === 'edit'),
        ]);
    }

    public function adminTermList(array $pagination, string $status, string $query, array $statusCounts): void
    {
        $this->renderAdmin('admin/terms/list', [
            'listBase' => $this->adminListBase('terms', $pagination, $status, $query, $statusCounts),
            'pageTitle' => I18n::t('admin.menu.terms'),
            'headerAction' => $this->linkHeaderAction('admin/terms/add', I18n::t('admin.add_term')),
        ]);
    }

    public function adminTermForm(string $mode, array $item, array $errors, array $usages = []): void
    {
        $this->renderAdmin('admin/terms/form', [
            'mode' => $mode,
            'item' => $item,
            'errors' => $errors,
            'usages' => $usages,
            'pageTitle' => $mode === 'add' ? I18n::t('admin.add_term') : I18n::t('admin.edit_term'),
            'headerAction' => $this->submitHeaderAction('#terms-form'),
        ]);
    }

    public function adminMediaList(array $pagination, string $status, string $query, array $statusCounts): void
    {
        $this->renderAdmin('admin/media/list', [
            'listBase' => $this->adminListBase('media', $pagination, $status, $query, $statusCounts),
            'pageTitle' => I18n::t('admin.menu.media'),
            'headerAction' => $this->linkHeaderAction('admin/media/add', I18n::t('admin.add_media')),
        ]);
    }

    private function adminListBase(string $entity, array $pagination, string $status, string $query, array $statusCounts = []): array
    {
        return [
            'entity' => $entity,
            'items' => $pagination['data'] ?? [],
            'page' => (int)($pagination['page'] ?? 1),
            'totalPages' => (int)($pagination['total_pages'] ?? 1),
            'statusCurrent' => $status !== '' ? $status : 'all',
            'query' => $query,
            'statusCounts' => $statusCounts,
        ];
    }

    public function adminMediaForm(string $mode, array $item, array $errors, array $authors, array $usages = [], array $navigation = []): void
    {
        $this->renderAdmin('admin/media/form', [
            'mode' => $mode,
            'item' => $item,
            'errors' => $errors,
            'authors' => $authors,
            'usages' => $usages,
            'navigation' => $navigation,
            'pageTitle' => $mode === 'add' ? I18n::t('admin.add_media') : I18n::t('admin.edit_media'),
            'headerAction' => $mode === 'edit'
                ? $this->saveMenuHeaderAction('#media-form', '#media-delete-modal')
                : $this->submitHeaderAction('#media-form'),
        ]);
    }

    private function submitHeaderAction(string $formSelector): array
    {
        return ['type' => 'submit', 'form' => $formSelector, 'label' => I18n::t('common.save')];
    }

    private function linkHeaderAction(string $href, string $label): array
    {
        return ['type' => 'link', 'href' => $href, 'label' => $label, 'icon' => 'add'];
    }

    private function saveMenuHeaderAction(string $formSelector, string $deleteModalTarget): array
    {
        return ['type' => 'save-menu', 'form' => $formSelector, 'delete_modal_target' => $deleteModalTarget];
    }

    private function contentMenuHeaderAction(bool $canDelete): array
    {
        return [
            'type' => 'content-menu',
            'delete_modal_target' => $canDelete ? '#content-delete-modal' : '',
        ];
    }

    private function renderAdmin(string $template, array $data): void
    {
        $this->view->render('admin/layout', $template, array_merge(
            $data,
            $this->adminBranding(),
            [
                'adminMenu' => $this->adminMenu(),
                'adminI18n' => $this->adminI18n(),
                'imageUploadAccept' => Upload::imageAccept(),
                'siteImageUploadAccept' => Upload::siteImageAccept(),
                'imageUploadTypesLabel' => Upload::imageExtensionsLabel(),
                'siteImageUploadTypesLabel' => Upload::siteImageExtensionsLabel(),
            ]
        ));
    }

    private function renderAdminAuth(string $template, array $data): void
    {
        $this->view->render('admin/auth-layout', $template, array_merge(
            $data,
            $this->adminBranding(),
            [
                'adminI18n' => $this->adminI18n(),
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
            'appVersion' => $this->appVersion(),
        ];
    }

    private function appVersion(): string
    {
        return defined('APP_VERSION') ? (string)APP_VERSION : '0.9.0';
    }

    private function adminI18n(): array
    {
        $payload = I18n::subset([
            'common',
            'admin',
            'content',
            'terms',
            'media',
            'comments',
            'users',
            'editor',
            'datetime',
            'modal',
            'auth',
        ]);
        return $payload;
    }

    private function adminMenu(): array
    {
        return [
            ['label' => I18n::t('admin.menu.dashboard'), 'url' => 'admin/dashboard', 'icon' => 'dashboard'],
            ['label' => I18n::t('admin.menu.users'), 'url' => 'admin/users', 'icon' => 'users'],
            ['label' => I18n::t('admin.menu.content'), 'url' => 'admin/content', 'icon' => 'content'],
            ['label' => I18n::t('admin.menu.media'), 'url' => 'admin/media', 'icon' => 'media'],
            ['label' => I18n::t('admin.menu.comments'), 'url' => 'admin/comments', 'icon' => 'content'],
            ['label' => I18n::t('admin.menu.terms'), 'url' => 'admin/terms', 'icon' => 'terms'],
            ['label' => I18n::t('admin.menu.settings'), 'url' => 'admin/settings', 'icon' => 'settings'],
        ];
    }
}
