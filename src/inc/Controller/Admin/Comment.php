<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\Application\Auth;
use App\Service\Application\Comment as CommentService;
use App\Service\Support\Csrf;
use App\Service\Support\Flash;
use App\View\AdminView;

final class Comment extends Admin
{
    public function __construct(
        private AdminView $pages,
        Auth $authService,
        private CommentService $comments,
        Flash $flash,
        Csrf $csrf,
    ) {
        parent::__construct($authService, $flash, $csrf);
    }

    public function list(callable $redirect): void
    {
        if (!$this->guardAdmin($redirect, false)) {
            return;
        }

        [$page, $perPage, $query] = $this->resolvePaginationQuery();
        $pagination = $this->comments->paginate($page, $perPage, $query);
        $statusCounts = $this->comments->statusCounts();

        $this->pages->adminCommentList($pagination, $query, $statusCounts);
    }
}
