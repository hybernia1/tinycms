<?php
declare(strict_types=1);

namespace App\Service\Application;

final class Dashboard
{
    public function __construct(
        private Content $content,
        private Comment $comment,
        private Media $media,
        private User $user
    ) {
    }

    public function overview(): array
    {
        $contentCounts = $this->content->statusCounts([
            Content::STATUS_DRAFT,
            Content::STATUS_PUBLISHED,
            Content::STATUS_TRASH,
        ]);
        $mediaCounts = $this->media->statusCounts();
        $userCounts = $this->user->statusCounts();

        return [
            'stats' => [
                'content_all' => (int)($contentCounts['all'] ?? 0),
                'content_published' => (int)($contentCounts[Content::STATUS_PUBLISHED] ?? 0),
                'content_draft' => (int)($contentCounts[Content::STATUS_DRAFT] ?? 0),
                'comments_pending' => $this->comment->pendingCount(),
                'media_all' => (int)($mediaCounts['all'] ?? 0),
                'users_all' => (int)($userCounts['all'] ?? 0),
            ],
            'recent_content' => $this->recentContent(),
            'recent_comments' => $this->recentComments(),
        ];
    }

    private function recentContent(): array
    {
        $pagination = $this->content->paginate(1, 5);
        return array_values((array)($pagination['data'] ?? []));
    }

    private function recentComments(): array
    {
        $pagination = $this->comment->paginate(1, 5);
        return array_values((array)($pagination['data'] ?? []));
    }
}
