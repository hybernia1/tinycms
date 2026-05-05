<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\Application\Auth;
use App\Service\Application\Comment as CommentService;
use App\Service\Support\Csrf;
use App\Service\Support\Flash;
use App\Service\Support\I18n;
use App\View\AdminView;

final class Comment extends Admin
{
    public function __construct(
        private AdminView $pages,
        Auth $authService,
        private CommentService $comments,
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

        $statuses = CommentService::STATUSES;
        [$page, $perPage, $status, $query] = $this->resolveSimpleListQuery(array_merge(['all'], $statuses));
        $pagination = $this->comments->paginate($page, $perPage, $status, $query);
        $statusCounts = $this->comments->statusCounts($statuses);
        $this->pages->adminCommentList($pagination, $status, $query, $statusCounts);
    }

    public function editForm(callable $redirect): void
    {
        if (!$this->guardAdmin($redirect, false)) {
            return;
        }

        $id = (int)($_GET['id'] ?? 0);
        $item = $this->comments->find($id);

        if ($item === null) {
            $this->flash->add('info', I18n::t('comments.not_found'));
            $redirect('admin/comments');
            return;
        }

        if ((int)($item['parent'] ?? 0) > 0) {
            $parent = $this->comments->find((int)$item['parent']);
            if ($parent !== null) {
                $redirect('admin/comments/edit?id=' . (int)$parent['id']);
                return;
            }
        }

        $this->pages->adminCommentForm($item, [], $this->comments->childrenForParent((int)$item['id']));
    }
}
