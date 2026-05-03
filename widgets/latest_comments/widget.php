<?php
declare(strict_types=1);

use App\Service\Infrastructure\Db\Connection;
use App\Service\Infrastructure\Db\Table;
use App\Service\Support\Date;
use App\Service\Support\Slugger;

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
    'render' => static function (array $data): string {
        $title = trim((string)($data['title'] ?? ''));
        $limit = max(1, min(20, (int)($data['limit'] ?? 5)));
        $showContentLink = (string)($data['show_content_link'] ?? '1') === '1';
        $showAvatar = (string)($data['show_avatar'] ?? '0') === '1';

        $commentsTable = Table::name('comments');
        $contentTable = Table::name('content');
        $usersTable = Table::name('users');
        $stmt = Connection::get()->prepare(implode("\n", [
            'SELECT c.id, c.content, c.author, c.body, c.created, content.name AS content_name, u.name AS author_name, u.email AS author_email',
            "FROM $commentsTable c",
            "INNER JOIN $contentTable content ON content.id = c.content",
            "LEFT JOIN $commentsTable parent_comment ON parent_comment.id = c.parent",
            "LEFT JOIN $usersTable u ON u.id = c.author",
            'WHERE c.status = :comment_status AND content.status = :content_status AND content.comments_enabled = 1 AND content.created <= :now',
            'AND (c.parent IS NULL OR parent_comment.status = :comment_status)',
            'ORDER BY c.created DESC, c.id DESC',
            'LIMIT :limit',
        ]));
        $stmt->bindValue(':comment_status', 'published');
        $stmt->bindValue(':content_status', 'published');
        $stmt->bindValue(':now', date('Y-m-d H:i:s'));
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        $items = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        if ($items === []) {
            return '';
        }

        $slugger = new Slugger();
        $rows = array_map(static function (array $comment) use ($slugger, $showContentLink, $showAvatar): string {
            $contentId = (int)($comment['content'] ?? 0);
            $contentName = trim((string)($comment['content_name'] ?? ''));
            if ($contentId <= 0 || $contentName === '') {
                return '';
            }

            $commentId = (int)($comment['id'] ?? 0);
            $author = trim((string)($comment['author_name'] ?? ''));
            $date = Date::formatDateTimeValue((string)($comment['created'] ?? ''));
            $excerpt = trim(preg_replace('/\s+/u', ' ', strip_tags(html_entity_decode((string)($comment['body'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8'))) ?? '');
            if (mb_strlen($excerpt) > 120) {
                $excerpt = mb_substr($excerpt, 0, 117) . '...';
            }

            $meta = array_filter([
                $author !== '' ? $author : t('front.comments_no_author', 'No author'),
                $date,
            ], static fn(string $value): bool => $value !== '');
            $url = site_url($slugger->slug($contentName, $contentId)) . ($commentId > 0 ? '#comment-' . $commentId : '#comments');

            $comment['author_name'] = $author;
            $avatar = $showAvatar && function_exists('get_avatar') ? get_avatar($comment, 'latest-comments-avatar', 40) : '';
            $content = $showContentLink
                ? '<a class="latest-comments-content" href="' . esc_url($url) . '">' . esc_html($contentName) . '</a>'
                : '';
            $itemClass = 'latest-comments-item' . ($avatar !== '' ? ' latest-comments-item-avatar' : '');

            return '<li><div class="' . esc_attr($itemClass) . '">' . $avatar . '<div class="latest-comments-text"><span class="latest-comments-body">' . esc_html($excerpt) . '</span><span class="latest-comments-meta">' . esc_html(implode(' - ', $meta)) . '</span>' . $content . '</div></div></li>';
        }, $items);
        $rows = array_values(array_filter($rows));

        return $rows !== []
            ? widget_title($title, 'comments') . '<ul class="latest-comments-list">' . implode('', $rows) . '</ul>'
            : '';
    },
];
