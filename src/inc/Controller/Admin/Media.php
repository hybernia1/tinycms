<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\Application\Auth;
use App\Service\Application\Media as MediaService;
use App\Service\Application\User as UserService;
use App\Service\Support\Csrf;
use App\Service\Support\Flash;
use App\Service\Support\I18n;
use App\View\AdminView;

final class Media extends Admin
{
    public function __construct(
        private AdminView $pages,
        Auth $authService,
        private MediaService $media,
        private UserService $users,
        Flash $flash,
        Csrf $csrf
    ) {
        parent::__construct($authService, $flash, $csrf);
    }

    public function list(callable $redirect): void
    {
        if (!$this->guardAdmin($redirect, false)) {
            return;
        }

        [$page, $perPage, $status, $query] = $this->resolveSimpleListQuery(['all', 'unassigned']);
        $pagination = $this->media->paginate($page, $perPage, $query, $status);
        $statusCounts = $this->media->statusCounts();
        $this->pages->adminMediaList($pagination, $status, $query, $statusCounts);
    }

    public function addForm(callable $redirect): void
    {
        if (!$this->guardAdmin($redirect, false)) {
            return;
        }

        $fallback = ['id' => null, 'name' => '', 'path' => '', 'author' => (int)($this->authService->auth()->id() ?? 0)];
        $fallback['author_label'] = $this->users->authorLabel((int)($fallback['author'] ?? 0));
        $this->pages->adminMediaForm('add', $fallback, [], []);
    }

    public function editForm(callable $redirect): void
    {
        if (!$this->guardAdmin($redirect, false)) {
            return;
        }

        $id = (int)($_GET['id'] ?? 0);
        $item = $this->media->find($id);

        if ($item === null) {
            $this->flash->add('info', I18n::t('media.not_found'));
            $redirect('admin/media');
            return;
        }

        $navigation = $this->media->editNavigation($id);
        $item['author_label'] = $this->users->authorLabel((int)($item['author'] ?? 0));
        $this->pages->adminMediaForm('edit', $item, [], $this->media->thumbnailUsages($id), $navigation);
    }
}
