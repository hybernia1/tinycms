<?php
declare(strict_types=1);

namespace App\Service\Feature;

use PDO;
use PDOException;

final class InstallService
{
    public function validateDatabaseInput(array $input): array
    {
        $host = trim((string)($input['db_host'] ?? ''));
        $name = trim((string)($input['db_name'] ?? ''));
        $user = trim((string)($input['db_user'] ?? ''));
        $pass = (string)($input['db_pass'] ?? '');

        $errors = [];

        if ($host === '') {
            $errors['db_host'] = 'Host je povinný.';
        }

        if ($name === '') {
            $errors['db_name'] = 'Databáze je povinná.';
        }

        if ($user === '') {
            $errors['db_user'] = 'Uživatel je povinný.';
        }

        return [
            'values' => [
                'db_host' => $host,
                'db_name' => $name,
                'db_user' => $user,
                'db_pass' => $pass,
            ],
            'errors' => $errors,
        ];
    }

    public function canConnect(array $db): ?string
    {
        try {
            $pdo = $this->connect($db);
            $pdo->query('SELECT 1');
            return null;
        } catch (PDOException $e) {
            return 'Nelze se připojit k databázi.';
        }
    }

    public function validateAdminInput(array $input): array
    {
        $name = trim((string)($input['name'] ?? ''));
        $email = mb_strtolower(trim((string)($input['email'] ?? '')));
        $password = (string)($input['password'] ?? '');

        $errors = [];

        if ($name === '') {
            $errors['name'] = 'Jméno je povinné.';
        } elseif (mb_strlen($name) > 255) {
            $errors['name'] = 'Jméno je příliš dlouhé.';
        }

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'E-mail není validní.';
        } elseif (mb_strlen($email) > 255) {
            $errors['email'] = 'E-mail je příliš dlouhý.';
        }

        if (mb_strlen($password) < 8) {
            $errors['password'] = 'Heslo musí mít alespoň 8 znaků.';
        }

        return [
            'values' => [
                'name' => $name,
                'email' => $email,
                'password' => $password,
            ],
            'errors' => $errors,
        ];
    }

    public function install(array $db, array $admin): array
    {
        try {
            $pdo = $this->connect($db);
            $pdo->beginTransaction();
            $this->createSchema($pdo);
            $this->createAdmin($pdo, $admin);
            $pdo->commit();
            $this->writeConfig($db);
            return ['success' => true, 'message' => 'Instalace proběhla úspěšně.'];
        } catch (\Throwable $e) {
            if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
                $pdo->rollBack();
            }

            return ['success' => false, 'message' => 'Instalace selhala. Zkontrolujte údaje a oprávnění.'];
        }
    }

    private function connect(array $db): PDO
    {
        $dsn = 'mysql:host=' . $db['db_host'] . ';dbname=' . $db['db_name'] . ';charset=utf8mb4';
        $pdo = new PDO($dsn, (string)$db['db_user'], (string)$db['db_pass']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    }

    private function writeConfig(array $db): void
    {
        $content = "<?php\ndeclare(strict_types=1);\n\ndefine('INC_DIR', 'inc/');\n\n"
            . "const DB_HOST = " . var_export((string)$db['db_host'], true) . ";\n"
            . "const DB_NAME = " . var_export((string)$db['db_name'], true) . ";\n"
            . "const DB_USER = " . var_export((string)$db['db_user'], true) . ";\n"
            . "const DB_PASS = " . var_export((string)$db['db_pass'], true) . ";\n\n"
            . "const MEDIA_THUMB_VARIANTS = [\n"
            . "    ['suffix' => '_100x100.webp', 'mode' => 'crop', 'width' => 100, 'height' => 100],\n"
            . "    ['suffix' => '_w768.webp', 'mode' => 'fit', 'width' => 768],\n"
            . "];\n";

        file_put_contents(dirname(__DIR__, 3) . '/config.php', $content, LOCK_EX);
    }

    private function createSchema(PDO $pdo): void
    {
        $sql = [
            "CREATE TABLE IF NOT EXISTS users (
                id INT NOT NULL AUTO_INCREMENT,
                email VARCHAR(255) DEFAULT NULL,
                password VARCHAR(255) NOT NULL,
                name VARCHAR(255) DEFAULT NULL,
                created DATETIME NOT NULL DEFAULT (NOW()),
                updated DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                role VARCHAR(50) NOT NULL DEFAULT 'user',
                suspend TINYINT(1) NOT NULL DEFAULT 0,
                PRIMARY KEY (id),
                UNIQUE KEY uq_users_email (email)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",
            "CREATE TABLE IF NOT EXISTS media (
                id INT NOT NULL AUTO_INCREMENT,
                author INT DEFAULT NULL,
                path VARCHAR(500) DEFAULT NULL,
                name VARCHAR(255) DEFAULT NULL,
                path_webp VARCHAR(500) DEFAULT NULL,
                created DATETIME NOT NULL DEFAULT (NOW()),
                updated DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_media_author (author),
                CONSTRAINT fk_media_author_user FOREIGN KEY (author) REFERENCES users (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",
            "CREATE TABLE IF NOT EXISTS content (
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
                CONSTRAINT fk_content_author_user FOREIGN KEY (author) REFERENCES users (id) ON DELETE CASCADE,
                CONSTRAINT fk_content_thumbnail_media FOREIGN KEY (thumbnail) REFERENCES media (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",
            "CREATE TABLE IF NOT EXISTS terms (
                id INT NOT NULL AUTO_INCREMENT,
                name VARCHAR(255) NOT NULL,
                body TEXT,
                created DATETIME DEFAULT (NOW()),
                updated DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",
            "CREATE TABLE IF NOT EXISTS content_terms (
                id INT NOT NULL AUTO_INCREMENT,
                content INT DEFAULT NULL,
                term INT DEFAULT NULL,
                PRIMARY KEY (id),
                KEY idx_content_terms_content (content),
                KEY idx_content_terms_term (term),
                CONSTRAINT fk_content_terms_content FOREIGN KEY (content) REFERENCES content (id) ON DELETE CASCADE,
                CONSTRAINT fk_content_terms_term FOREIGN KEY (term) REFERENCES terms (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",
            "CREATE TABLE IF NOT EXISTS attachments (
                id INT NOT NULL AUTO_INCREMENT,
                media INT NOT NULL,
                content INT NOT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY uq_attachments_content_media (content, media),
                KEY idx_attachments_media (media),
                KEY idx_attachments_content (content),
                CONSTRAINT fk_attachments_content FOREIGN KEY (content) REFERENCES content (id) ON DELETE CASCADE,
                CONSTRAINT fk_attachments_media FOREIGN KEY (media) REFERENCES media (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",
            "CREATE TABLE IF NOT EXISTS settings (
                key_name VARCHAR(100) NOT NULL,
                value JSON DEFAULT NULL,
                PRIMARY KEY (key_name)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",
        ];

        foreach ($sql as $query) {
            $pdo->exec($query);
        }
    }

    private function createAdmin(PDO $pdo, array $admin): void
    {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $admin['email']]);

        if ($stmt->fetchColumn() !== false) {
            throw new \RuntimeException('Admin exists.');
        }

        $insert = $pdo->prepare('INSERT INTO users (name, email, password, role, suspend, created, updated) VALUES (:name, :email, :password, :role, :suspend, :created, :updated)');
        $now = date('Y-m-d H:i:s');
        $insert->execute([
            'name' => $admin['name'],
            'email' => $admin['email'],
            'password' => password_hash((string)$admin['password'], PASSWORD_DEFAULT),
            'role' => 'admin',
            'suspend' => 0,
            'created' => $now,
            'updated' => $now,
        ]);
    }
}
