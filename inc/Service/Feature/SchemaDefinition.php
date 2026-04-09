<?php
declare(strict_types=1);

namespace App\Service\Feature;

final class SchemaDefinition
{
    public static function columnRules(): array
    {
        return [
            'users' => [
                'email' => ['max' => 255, 'nullable' => true],
                'password' => ['max' => 255, 'nullable' => false],
                'name' => ['max' => 255, 'nullable' => true],
                'role' => ['max' => 50, 'nullable' => false, 'allowed' => ['admin', 'editor']],
            ],
            'media' => [
                'path' => ['max' => 500, 'nullable' => true],
                'name' => ['max' => 255, 'nullable' => true],
                'path_webp' => ['max' => 500, 'nullable' => true],
            ],
            'content' => [
                'status' => ['max' => 50, 'nullable' => false],
                'excerpt' => ['max' => 500, 'nullable' => true],
                'name' => ['max' => 255, 'nullable' => true],
            ],
            'terms' => [
                'name' => ['max' => 255, 'nullable' => false],
            ],
            'settings' => [
                'key_name' => ['max' => 100, 'nullable' => false],
            ],
        ];
    }

    public static function ddl(string $prefix = ''): array
    {
        $users = $prefix . 'users';
        $media = $prefix . 'media';
        $content = $prefix . 'content';
        $terms = $prefix . 'terms';
        $contentTerms = $prefix . 'content_terms';
        $attachments = $prefix . 'attachments';
        $settings = $prefix . 'settings';
        $fkMediaAuthorUser = self::constraintName($prefix, 'fk_media_author_user');
        $fkContentAuthorUser = self::constraintName($prefix, 'fk_content_author_user');
        $fkContentThumbnailMedia = self::constraintName($prefix, 'fk_content_thumbnail_media');
        $fkContentTermsContent = self::constraintName($prefix, 'fk_content_terms_content');
        $fkContentTermsTerm = self::constraintName($prefix, 'fk_content_terms_term');
        $fkAttachmentsContent = self::constraintName($prefix, 'fk_attachments_content');
        $fkAttachmentsMedia = self::constraintName($prefix, 'fk_attachments_media');

        return [
            "CREATE TABLE IF NOT EXISTS $users (
                id INT NOT NULL AUTO_INCREMENT,
                email VARCHAR(255) DEFAULT NULL,
                password VARCHAR(255) NOT NULL,
                name VARCHAR(255) DEFAULT NULL,
                created DATETIME NOT NULL DEFAULT (NOW()),
                updated DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                role VARCHAR(50) NOT NULL DEFAULT 'editor',
                suspend TINYINT(1) NOT NULL DEFAULT 0,
                PRIMARY KEY (id),
                UNIQUE KEY uq_users_email (email)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",
            "CREATE TABLE IF NOT EXISTS $media (
                id INT NOT NULL AUTO_INCREMENT,
                author INT DEFAULT NULL,
                path VARCHAR(500) DEFAULT NULL,
                name VARCHAR(255) DEFAULT NULL,
                path_webp VARCHAR(500) DEFAULT NULL,
                created DATETIME NOT NULL DEFAULT (NOW()),
                updated DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_media_author (author),
                CONSTRAINT $fkMediaAuthorUser FOREIGN KEY (author) REFERENCES $users (id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",
            "CREATE TABLE IF NOT EXISTS $content (
                id INT NOT NULL AUTO_INCREMENT,
                author INT DEFAULT NULL,
                status VARCHAR(50) NOT NULL DEFAULT 'draft',
                created DATETIME NOT NULL DEFAULT (NOW()),
                updated DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                body LONGTEXT,
                excerpt VARCHAR(500) DEFAULT NULL,
                name VARCHAR(255) DEFAULT NULL,
                thumbnail INT DEFAULT NULL,
                PRIMARY KEY (id),
                KEY idx_content_status (status),
                KEY idx_content_created (created),
                KEY idx_content_author (author),
                KEY idx_content_thumbnail (thumbnail),
                CONSTRAINT $fkContentAuthorUser FOREIGN KEY (author) REFERENCES $users (id) ON DELETE SET NULL,
                CONSTRAINT $fkContentThumbnailMedia FOREIGN KEY (thumbnail) REFERENCES $media (id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",
            "CREATE TABLE IF NOT EXISTS $terms (
                id INT NOT NULL AUTO_INCREMENT,
                name VARCHAR(255) NOT NULL,
                created DATETIME DEFAULT (NOW()),
                updated DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",
            "CREATE TABLE IF NOT EXISTS $contentTerms (
                id INT NOT NULL AUTO_INCREMENT,
                content INT DEFAULT NULL,
                term INT DEFAULT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY uq_content_terms_content_term (content, term),
                KEY idx_content_terms_content (content),
                KEY idx_content_terms_term (term),
                CONSTRAINT $fkContentTermsContent FOREIGN KEY (content) REFERENCES $content (id) ON DELETE CASCADE,
                CONSTRAINT $fkContentTermsTerm FOREIGN KEY (term) REFERENCES $terms (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",
            "CREATE TABLE IF NOT EXISTS $attachments (
                id INT NOT NULL AUTO_INCREMENT,
                media INT NOT NULL,
                content INT NOT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY uq_attachments_content_media (content, media),
                KEY idx_attachments_media (media),
                KEY idx_attachments_content (content),
                CONSTRAINT $fkAttachmentsContent FOREIGN KEY (content) REFERENCES $content (id) ON DELETE CASCADE,
                CONSTRAINT $fkAttachmentsMedia FOREIGN KEY (media) REFERENCES $media (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",
            "CREATE TABLE IF NOT EXISTS $settings (
                key_name VARCHAR(100) NOT NULL,
                value JSON DEFAULT NULL,
                PRIMARY KEY (key_name)
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
