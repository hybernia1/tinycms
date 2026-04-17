<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\Application\Auth;
use App\Service\Application\Term as TermService;
use App\Service\Support\Csrf;
use App\Service\Support\Flash;
use App\Service\Support\I18n;
use App\View\AdminView;

final class Term extends Admin
{
    public function __construct(
        private AdminView $pages,
        Auth $authService,
        private TermService $terms,
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

        [$page, $perPage, $status, $query] = $this->resolveListQuery();
        $pagination = $this->terms->paginate($page, $perPage, $query, $status);
        $statusCounts = $this->terms->statusCounts();

        $this->pages->adminTermList($pagination, $status, $query, $statusCounts);
    }

    public function addForm(callable $redirect): void
    {
        if (!$this->guardAdmin($redirect, false)) {
            return;
        }

        $fallback = ['id' => null, 'name' => ''];
        $this->pages->adminTermForm('add', $fallback, [], []);
    }

    public function editForm(callable $redirect): void
    {
        if (!$this->guardAdmin($redirect, false)) {
            return;
        }

        $id = (int)($_GET['id'] ?? 0);
        $item = $this->terms->find($id);

        if ($item === null) {
            $this->flash->add('info', I18n::t('terms.not_found'));
            $redirect('admin/terms');
            return;
        }

        $this->pages->adminTermForm('edit', $item, [], $this->terms->contentUsages($id));
    }

    private function resolveListQuery(): array
    {
        [$page, $perPage, $query] = $this->resolvePaginationQuery();
        $status = $this->resolveStatusFilter(['all', 'unassigned']);

        return [$page, $perPage, $status, $query];
    }
}
