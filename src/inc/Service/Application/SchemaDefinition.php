<?php
declare(strict_types=1);

namespace App\Service\Application;

final class SchemaDefinition
{
    public static function columnRules(): array
    {
        return [
            'users' => [
                'email' => ['max' => 255, 'nullable' => true],
                'password' => ['max' => 255, 'nullable' => false],
                'name' => ['max' => 255, 'nullable' => true],
                'role' => ['max' => 50, 'nullable' => false, 'allowed' => User::ROLES],
                'reset_token' => ['max' => 100, 'nullable' => true],
            ],
            'media' => [
                'path' => ['max' => 500, 'nullable' => true],
                'name' => ['max' => 255, 'nullable' => true],
            ],
            'content' => [
                'status' => ['max' => 50, 'nullable' => false],
                'type' => ['max' => 100, 'nullable' => false, 'allowed' => Content::TYPES],
                'excerpt' => ['max' => 500, 'nullable' => true],
                'name' => ['max' => 255, 'nullable' => true],
            ],
            'comments' => [
                'author_name' => ['max' => 255, 'nullable' => true],
                'author_email' => ['max' => 255, 'nullable' => true],
                'ip_address' => ['max' => 45, 'nullable' => true],
                'status' => ['max' => 50, 'nullable' => false, 'allowed' => Comment::STATUSES],
            ],
            'content_stats' => [
                'ip_address' => ['max' => 45, 'nullable' => false],
            ],
            'terms' => [
                'name' => ['max' => 255, 'nullable' => false],
            ],
            'settings' => [
                'key_name' => ['max' => 100, 'nullable' => false],
                'value' => ['nullable' => true],
            ],
            'menu' => [
                'label' => ['max' => 255, 'nullable' => false],
                'url' => ['max' => 500, 'nullable' => false],
                'icon' => ['max' => 100, 'nullable' => true],
                'link_target' => ['max' => 20, 'nullable' => false, 'allowed' => ['_self', '_blank']],
            ],
            'widgets' => [
                'area' => ['max' => 100, 'nullable' => false],
                'widget' => ['max' => 100, 'nullable' => false],
            ],
        ];
    }

    public static function ddl(string $prefix = ''): array
    {
        $users = $prefix . 'users';
        $media = $prefix . 'media';
        $content = $prefix . 'content';
        $comments = $prefix . 'comments';
        $contentStats = $prefix . 'content_stats';
        $terms = $prefix . 'terms';
        $contentTerms = $prefix . 'content_terms';
        $contentMedia = $prefix . 'content_media';
        $settings = $prefix . 'settings';
        $fkMediaAuthorUser = self::constraintName($prefix, 'fk_media_author_user');
        $fkContentAuthorUser = self::constraintName($prefix, 'fk_content_author_user');
        $fkContentThumbnailMedia = self::constraintName($prefix, 'fk_content_thumbnail_media');
        $fkCommentsContent = self::constraintName($prefix, 'fk_comments_content');
        $fkCommentsParent = self::constraintName($prefix, 'fk_comments_parent');
        $fkCommentsReplyTo = self::constraintName($prefix, 'fk_comments_reply_to');
        $fkCommentsAuthorUser = self::constraintName($prefix, 'fk_comments_author_user');
        $fkContentStatsContent = self::constraintName($prefix, 'fk_content_stats_content');
        $fkContentTermsContent = self::constraintName($prefix, 'fk_content_terms_content');
        $fkContentTermsTerm = self::constraintName($prefix, 'fk_content_terms_term');
        $fkContentMediaContent = self::constraintName($prefix, 'fk_content_media_content');
        $fkContentMediaMedia = self::constraintName($prefix, 'fk_content_media_media');
        $defaultUserRole = User::ROLE_USER;
        $defaultContentStatus = Content::STATUS_DRAFT;
        $defaultContentType = Content::TYPE_ARTICLE;
        $defaultCommentStatus = Comment::STATUS_PUBLISHED;

        return array_merge([
            "CREATE TABLE IF NOT EXISTS $users (
                id INT NOT NULL AUTO_INCREMENT,
                email VARCHAR(255) DEFAULT NULL,
                password VARCHAR(255) NOT NULL,
                name VARCHAR(255) DEFAULT NULL,
                created DATETIME NOT NULL DEFAULT (NOW()),
                updated DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                role VARCHAR(50) NOT NULL DEFAULT '$defaultUserRole',
                suspend TINYINT(1) NOT NULL DEFAULT 0,
                reset_token VARCHAR(100) DEFAULT NULL,
                reset_token_expiry DATETIME DEFAULT NULL,
                PRIMARY KEY (id),
                KEY idx_users_reset_token (reset_token),
                UNIQUE KEY uq_users_email (email)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",
            "CREATE TABLE IF NOT EXISTS $media (
                id INT NOT NULL AUTO_INCREMENT,
                author INT DEFAULT NULL,
                path VARCHAR(500) DEFAULT NULL,
                name VARCHAR(255) DEFAULT NULL,
                created DATETIME NOT NULL DEFAULT (NOW()),
                updated DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_media_author (author),
                CONSTRAINT $fkMediaAuthorUser FOREIGN KEY (author) REFERENCES $users (id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",
            "CREATE TABLE IF NOT EXISTS $content (
                id INT NOT NULL AUTO_INCREMENT,
                author INT DEFAULT NULL,
                status VARCHAR(50) NOT NULL DEFAULT '$defaultContentStatus',
                type VARCHAR(100) NOT NULL DEFAULT '$defaultContentType',
                created DATETIME NOT NULL DEFAULT (NOW()),
                updated DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                body LONGTEXT,
                excerpt VARCHAR(500) DEFAULT NULL,
                name VARCHAR(255) DEFAULT NULL,
                thumbnail INT DEFAULT NULL,
                comments_enabled TINYINT(1) NOT NULL DEFAULT 1,
                PRIMARY KEY (id),
                KEY idx_content_status_created_id (status, created, id),
                KEY idx_content_status (status),
                KEY idx_content_type (type),
                KEY idx_content_created (created),
                KEY idx_content_author (author),
                KEY idx_content_thumbnail (thumbnail),
                CONSTRAINT $fkContentAuthorUser FOREIGN KEY (author) REFERENCES $users (id) ON DELETE SET NULL,
                CONSTRAINT $fkContentThumbnailMedia FOREIGN KEY (thumbnail) REFERENCES $media (id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",
            "CREATE TABLE IF NOT EXISTS $comments (
                id INT NOT NULL AUTO_INCREMENT,
                content INT NOT NULL,
                parent INT DEFAULT NULL,
                reply_to INT DEFAULT NULL,
                author INT DEFAULT NULL,
                author_name VARCHAR(255) DEFAULT NULL,
                author_email VARCHAR(255) DEFAULT NULL,
                ip_address VARCHAR(45) DEFAULT NULL,
                status VARCHAR(50) NOT NULL DEFAULT '$defaultCommentStatus',
                body LONGTEXT NOT NULL,
                created DATETIME NOT NULL DEFAULT (NOW()),
                updated DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_comments_content_parent_created (content, parent, created),
                KEY idx_comments_status_created (status, created),
                KEY idx_comments_parent (parent),
                KEY idx_comments_reply_to (reply_to),
                KEY idx_comments_author (author),
                CONSTRAINT $fkCommentsContent FOREIGN KEY (content) REFERENCES $content (id) ON DELETE CASCADE,
                CONSTRAINT $fkCommentsParent FOREIGN KEY (parent) REFERENCES $comments (id) ON DELETE CASCADE,
                CONSTRAINT $fkCommentsReplyTo FOREIGN KEY (reply_to) REFERENCES $comments (id) ON DELETE SET NULL,
                CONSTRAINT $fkCommentsAuthorUser FOREIGN KEY (author) REFERENCES $users (id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",
            "CREATE TABLE IF NOT EXISTS $contentStats (
                content INT NOT NULL,
                ip_address VARCHAR(45) NOT NULL,
                last_visit DATETIME NOT NULL DEFAULT (NOW()),
                PRIMARY KEY (content, ip_address),
                KEY idx_content_stats_last_visit (last_visit),
                CONSTRAINT $fkContentStatsContent FOREIGN KEY (content) REFERENCES $content (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",
            "CREATE TABLE IF NOT EXISTS $terms (
                id INT NOT NULL AUTO_INCREMENT,
                name VARCHAR(255) NOT NULL,
                created DATETIME DEFAULT (NOW()),
                updated DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uq_terms_name (name)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",
            "CREATE TABLE IF NOT EXISTS $contentTerms (
                content INT NOT NULL,
                term INT NOT NULL,
                PRIMARY KEY (content, term),
                KEY idx_content_terms_term (term),
                CONSTRAINT $fkContentTermsContent FOREIGN KEY (content) REFERENCES $content (id) ON DELETE CASCADE,
                CONSTRAINT $fkContentTermsTerm FOREIGN KEY (term) REFERENCES $terms (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",
            "CREATE TABLE IF NOT EXISTS $contentMedia (
                media INT NOT NULL,
                content INT NOT NULL,
                PRIMARY KEY (content, media),
                KEY idx_content_media_media (media),
                CONSTRAINT $fkContentMediaContent FOREIGN KEY (content) REFERENCES $content (id) ON DELETE CASCADE,
                CONSTRAINT $fkContentMediaMedia FOREIGN KEY (media) REFERENCES $media (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",
            "CREATE TABLE IF NOT EXISTS $settings (
                key_name VARCHAR(100) NOT NULL,
                value LONGTEXT NULL,
                PRIMARY KEY (key_name)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",
        ], self::menuDdl($prefix), self::widgetsDdl($prefix));
    }

    public static function menuDdl(string $prefix = ''): array
    {
        $menu = $prefix . 'menu';

        return [
            "CREATE TABLE IF NOT EXISTS $menu (
                id INT NOT NULL AUTO_INCREMENT,
                label VARCHAR(255) NOT NULL,
                url VARCHAR(500) NOT NULL,
                icon VARCHAR(100) DEFAULT NULL,
                link_target VARCHAR(20) NOT NULL DEFAULT '_self',
                position INT NOT NULL DEFAULT 0,
                created DATETIME NOT NULL DEFAULT (NOW()),
                updated DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_menu_position (position)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",
        ];
    }

    public static function widgetsDdl(string $prefix = ''): array
    {
        $widgets = $prefix . 'widgets';

        return [
            "CREATE TABLE IF NOT EXISTS $widgets (
                id INT NOT NULL AUTO_INCREMENT,
                area VARCHAR(100) NOT NULL,
                widget VARCHAR(100) NOT NULL,
                data LONGTEXT,
                position INT NOT NULL DEFAULT 0,
                created DATETIME NOT NULL DEFAULT (NOW()),
                updated DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_widgets_area_position (area, position),
                KEY idx_widgets_widget (widget)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",
        ];
    }

    private static function constraintName(string $prefix, string $base): string
    {
        $name = $prefix === '' ? $base : $prefix . $base;
        if (strlen($name) <= 64) {
            return $name;
        }

        $hash = substr(sha1($name), 0, 8);
        return substr($name, 0, 55) . '_' . $hash;
    }
}
