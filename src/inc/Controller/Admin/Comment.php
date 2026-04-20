<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\Application\Auth;
use App\Service\Application\Comment as CommentService;
use App\Service\Support\I18n;
use App\Service\Support\Csrf;
use App\Service\Support\Flash;
use App\Service\Support\Slugger;
use App\View\AdminView;

final class Comment extends Admin
{
    private Slugger $slugger;

    public function __construct(
        private AdminView $pages,
        Auth $authService,
        private CommentService $comments,
        Flash $flash,
        Csrf $csrf,
    ) {
        parent::__construct($authService, $flash, $csrf);
        $this->slugger = new Slugger();
    }

    public function list(callable $redirect): void
    {
        if (!$this->guardAdmin($redirect, false)) {
            return;
        }

        [$page, $perPage, $status, $query] = $this->resolveSimpleListQuery(['all', CommentService::STATUS_DRAFT, CommentService::STATUS_PUBLISHED]);
        $pagination = $this->comments->paginate($page, $perPage, $status, $query);
        $statusCounts = $this->comments->statusCounts();

        $this->pages->adminCommentList($pagination, $status, $query, $statusCounts);
    }

    public function editForm(callable $redirect): void
    {
        if (!$this->guardAdmin($redirect, false)) {
            return;
        }

        $id = (int)($_GET['id'] ?? 0);
        $item = $this->comments->findDetailed($id);
        if ($item === null) {
            $this->flash->add('info', I18n::t('comments.not_found'));
            $redirect('admin/comments');
            return;
        }

        $contentId = (int)($item['content'] ?? 0);
        $contentName = (string)($item['content_name'] ?? '');
        $contentSlug = $contentId > 0 ? $this->slugger->slug($contentName, $contentId) : '';
        $publishedIn = [
            'content_id' => $contentId,
            'content_name' => $contentName,
            'admin_edit_url' => $contentId > 0 ? 'admin/content/edit?id=' . $contentId : '',
            'front_url' => $contentSlug,
        ];

        $this->pages->adminCommentForm($item, [], $publishedIn);
    }
}
