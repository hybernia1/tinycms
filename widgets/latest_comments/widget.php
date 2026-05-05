<?php
declare(strict_types=1);

if (!defined('BASE_DIR')) {
    exit;
}

return [
    'name' => t('widgets.latest_comments.name'),
    'fields' => [
        [
            'name' => 'title',
            'type' => 'text',
            'label' => t('widgets.latest_comments.title'),
        ],
        [
            'name' => 'limit',
            'type' => 'number',
            'label' => t('widgets.latest_comments.limit'),
            'default' => '5',
            'min' => 1,
            'max' => 20,
        ],
        [
            'name' => 'show_content_link',
            'type' => 'checkbox',
            'label' => t('widgets.latest_comments.show_content_link'),
            'default' => '1',
        ],
        [
            'name' => 'show_avatar',
            'type' => 'checkbox',
            'label' => t('widgets.latest_comments.show_avatar'),
            'default' => '0',
        ],
    ],
    'render' => static function (array $data, array $widget): string {
        $title = trim((string)($data['title'] ?? ''));
        $limit = max(1, min(20, (int)($data['limit'] ?? 5)));
        $showContentLink = (string)($data['show_content_link'] ?? '1') === '1';
        $showAvatar = (string)($data['show_avatar'] ?? '0') === '1';

        $items = $widget['items']('comments', ['limit' => $limit]);
        if ($items === []) {
            return '';
        }

        $rows = array_map(static function (array $comment) use ($widget, $showContentLink, $showAvatar): string {
            $contentId = (int)($comment['content'] ?? 0);
            $contentName = trim((string)($comment['content_name'] ?? ''));
            if ($contentId <= 0 || $contentName === '') {
                return '';
            }

            $commentId = (int)($comment['id'] ?? 0);
            $author = trim((string)($comment['author_name'] ?? ''));
            $date = $widget['date']((string)($comment['created'] ?? ''));
            $excerpt = $widget['excerpt']((string)($comment['body'] ?? ''), 120);

            $meta = array_filter([
                $author !== '' ? $author : t('front.comments_no_author', 'No author'),
                $date,
            ], static fn(string $value): bool => $value !== '');
            $url = $widget['content_url'](['id' => $contentId, 'name' => $contentName]) . ($commentId > 0 ? '#comment-' . $commentId : '#comments');

            $comment['author_name'] = $author;
            $avatar = $showAvatar && function_exists('get_avatar') ? get_avatar($comment, 'latest-comments-avatar', 40) : '';
            $commentHtml = '<span class="latest-comments-body">' . esc_html($excerpt) . '</span><span class="latest-comments-meta">' . esc_html(implode(' - ', $meta)) . '</span>';
            $commentLink = !$showContentLink
                ? '<a class="latest-comments-comment" href="' . esc_url($url) . '">' . $commentHtml . '</a>'
                : $commentHtml;
            $content = $showContentLink
                ? '<a class="latest-comments-content" href="' . esc_url($url) . '">' . esc_html($contentName) . '</a>'
                : '';
            $itemClass = 'latest-comments-item' . ($avatar !== '' ? ' latest-comments-item-avatar' : '');

            return '<li><div class="' . esc_attr($itemClass) . '">' . $avatar . '<div class="latest-comments-text">' . $commentLink . $content . '</div></div></li>';
        }, $items);
        $rows = array_values(array_filter($rows));

        return $rows !== []
            ? $widget['title']($title, 'comments') . '<ul class="latest-comments-list">' . implode('', $rows) . '</ul>'
            : '';
    },
];
