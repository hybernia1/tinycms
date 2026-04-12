<?php
declare(strict_types=1);

namespace App\View;

use App\Service\Feature\SettingsService;
use App\Service\Feature\UploadService;
use App\Service\Support\I18n;
use App\Service\Support\PaginationConfig;

final class AdminView
{
    private View $view;
    private SettingsService $settings;

    public function __construct(View $view, SettingsService $settings)
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

    public function adminContentForm(string $mode, array $item, array $errors, array $availableStatuses, array $authors, array $selectedTerms = []): void
    {
        $this->renderAdmin('admin/content/form', [
            'mode' => $mode,
            'item' => $item,
            'errors' => $errors,
            'availableStatuses' => $availableStatuses,
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
        $perPage = (int)($pagination['per_page'] ?? PaginationConfig::perPage());
        return [
            'entity' => $entity,
            'items' => $pagination['data'] ?? [],
            'page' => (int)($pagination['page'] ?? 1),
            'perPage' => $perPage,
            'totalPages' => (int)($pagination['total_pages'] ?? 1),
            'statusCurrent' => $status !== '' ? $status : 'all',
            'query' => $query,
            'statusCounts' => $statusCounts,
            'allowedPerPage' => PaginationConfig::allowed(),
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

    private function adminI18n(): array
    {
        return [
            'common' => [
                'delete' => I18n::t('common.delete'),
                'close_notice' => I18n::t('admin.close_notice'),
                'invalid_data' => I18n::t('common.invalid_data'),
                'cancel' => I18n::t('common.cancel'),
                'save' => I18n::t('common.save'),
            ],
            'admin' => [
                'edit_content' => I18n::t('admin.edit_content'),
            ],
            'content' => [
                'planned' => I18n::t('content.planned'),
                'switch_to_draft' => I18n::t('content.switch_to_draft'),
                'publish' => I18n::t('content.publish'),
                'statuses' => [
                    'draft' => I18n::t('content.statuses.draft'),
                    'published' => I18n::t('content.statuses.published'),
                ],
                'choose_image' => I18n::t('content.choose_image'),
                'deleted' => I18n::t('content.deleted'),
                'published' => I18n::t('content.published'),
                'switched_to_draft' => I18n::t('content.switched_to_draft'),
            ],
            'terms' => [
                'deleted' => I18n::t('terms.deleted'),
                'delete' => I18n::t('terms.delete'),
            ],
            'media' => [
                'deleted' => I18n::t('media.deleted'),
                'delete' => I18n::t('media.delete'),
                'no_preview' => I18n::t('media.no_preview'),
                'no_results' => I18n::t('media.no_results'),
                'untitled' => I18n::t('media.untitled'),
                'name_required' => I18n::t('media.name_required'),
                'rename_saved' => I18n::t('media.rename_saved'),
                'rename_failed' => I18n::t('media.rename_failed'),
                'library_load_failed' => I18n::t('media.library_load_failed'),
                'assign_failed' => I18n::t('media.assign_failed'),
                'delete_failed' => I18n::t('media.delete_failed'),
                'upload_failed' => I18n::t('media.upload_failed'),
                'uploaded' => I18n::t('media.uploaded'),
                'detach_failed' => I18n::t('media.detach_failed'),
                'detached' => I18n::t('media.detached'),
                'invalid_url' => I18n::t('media.invalid_url'),
            ],
            'users' => [
                'deleted' => I18n::t('users.deleted'),
                'suspended' => I18n::t('users.suspended'),
                'unsuspended' => I18n::t('users.unsuspended'),
                'status_suspended_single' => I18n::t('users.status.suspended_single'),
                'roles' => [
                    'user' => I18n::t('users.roles.user'),
                    'admin' => I18n::t('users.roles.admin'),
                ],
                'delete' => I18n::t('users.delete'),
                'suspend' => I18n::t('users.suspend'),
                'unsuspend' => I18n::t('users.unsuspend'),
            ],
            'editor' => [
                'placeholder' => I18n::t('editor.placeholder'),
                'headings' => I18n::t('editor.headings'),
                'paragraph' => I18n::t('editor.paragraph'),
                'heading_1' => I18n::t('editor.heading_1'),
                'heading_2' => I18n::t('editor.heading_2'),
                'heading_3' => I18n::t('editor.heading_3'),
                'heading_4' => I18n::t('editor.heading_4'),
                'heading_5' => I18n::t('editor.heading_5'),
                'heading_6' => I18n::t('editor.heading_6'),
                'lists' => I18n::t('editor.lists'),
                'quote' => I18n::t('editor.quote'),
                'align_left' => I18n::t('editor.align_left'),
                'align_center' => I18n::t('editor.align_center'),
                'align_right' => I18n::t('editor.align_right'),
                'align_justify' => I18n::t('editor.align_justify'),
                'list_bulleted' => I18n::t('editor.list_bulleted'),
                'list_numbered' => I18n::t('editor.list_numbered'),
                'alignment' => I18n::t('editor.alignment'),
                'insert_link' => I18n::t('editor.insert_link'),
                'open_new_window' => I18n::t('editor.open_new_window'),
                'add_nofollow' => I18n::t('editor.add_nofollow'),
                'clear' => I18n::t('editor.clear'),
                'text_color' => I18n::t('editor.text_color'),
                'insert_image' => I18n::t('editor.insert_image'),
                'page_break' => I18n::t('editor.page_break'),
                'background_color' => I18n::t('editor.background_color'),
                'focus_mode' => I18n::t('editor.focus_mode'),
                'focus_mode_exit' => I18n::t('editor.focus_mode_exit'),
                'unlink' => I18n::t('editor.unlink'),
                'remove_link' => I18n::t('editor.remove_link'),
                'link_title' => I18n::t('editor.link_title'),
                'bold' => I18n::t('editor.bold'),
                'italic' => I18n::t('editor.italic'),
            ],
            'datetime' => [
                'pick_date_time' => I18n::t('datetime.pick_date_time'),
                'today' => I18n::t('datetime.today'),
                'clear' => I18n::t('datetime.clear'),
                'prev_month' => I18n::t('datetime.prev_month'),
                'next_month' => I18n::t('datetime.next_month'),
                'weekdays_short' => [
                    I18n::t('datetime.weekdays_short.mon'),
                    I18n::t('datetime.weekdays_short.tue'),
                    I18n::t('datetime.weekdays_short.wed'),
                    I18n::t('datetime.weekdays_short.thu'),
                    I18n::t('datetime.weekdays_short.fri'),
                    I18n::t('datetime.weekdays_short.sat'),
                    I18n::t('datetime.weekdays_short.sun'),
                ],
                'months' => [
                    I18n::t('datetime.months.jan'),
                    I18n::t('datetime.months.feb'),
                    I18n::t('datetime.months.mar'),
                    I18n::t('datetime.months.apr'),
                    I18n::t('datetime.months.may'),
                    I18n::t('datetime.months.jun'),
                    I18n::t('datetime.months.jul'),
                    I18n::t('datetime.months.aug'),
                    I18n::t('datetime.months.sep'),
                    I18n::t('datetime.months.oct'),
                    I18n::t('datetime.months.nov'),
                    I18n::t('datetime.months.dec'),
                ],
            ],
            'modal' => [
                'confirm_delete_type' => I18n::t('modal.confirm_delete_type'),
                'default_type' => I18n::t('modal.default_type'),
            ],
            'auth' => [
                'show_password' => I18n::t('front.login.show_password'),
                'hide_password' => I18n::t('auth.hide_password'),
            ],
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
