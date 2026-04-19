<?php
declare(strict_types=1);

namespace App\Service\Application;

use App\Service\Infrastructure\Db\SchemaConstraintValidator;
use App\Service\Support\I18n;
use PDO;
use PDOException;

final class Install
{
    private SchemaConstraintValidator $schemaValidator;

    public function __construct(?SchemaConstraintValidator $schemaValidator = null)
    {
        $this->schemaValidator = $schemaValidator ?? new SchemaConstraintValidator();
    }

    public function validateDatabaseInput(array $input): array
    {
        $host = trim((string)($input['db_host'] ?? ''));
        $name = trim((string)($input['db_name'] ?? ''));
        $user = trim((string)($input['db_user'] ?? ''));
        $pass = (string)($input['db_pass'] ?? '');
        $prefix = trim((string)($input['db_prefix'] ?? ''));

        $errors = [];

        if ($host === '') {
            $errors['db_host'] = I18n::t('install.db_host_required');
        }

        if ($name === '') {
            $errors['db_name'] = I18n::t('install.db_name_required');
        }

        if ($user === '') {
            $errors['db_user'] = I18n::t('install.db_user_required');
        }
        if ($prefix !== '' && preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $prefix) !== 1) {
            $errors['db_prefix'] = I18n::t('install.db_prefix_invalid');
        }

        return [
            'values' => [
                'db_host' => $host,
                'db_name' => $name,
                'db_user' => $user,
                'db_pass' => $pass,
                'db_prefix' => $prefix,
            ],
            'errors' => $errors,
        ];
    }

    public function canInstallOnPrefix(array $db): ?string
    {
        try {
            $pdo = $this->connect($db);
        } catch (PDOException $e) {
            return I18n::t('install.db_connect_failed');
        }

        try {
            $prefix = $this->normalizePrefix((string)($db['db_prefix'] ?? ''));
        } catch (\RuntimeException $e) {
            return I18n::t('install.db_prefix_invalid');
        }

        if ($this->hasExistingSchema($pdo, (string)($db['db_name'] ?? ''), $prefix)) {
            return I18n::t('install.prefix_in_use');
        }

        return null;
    }

    public function validateAdminInput(array $input): array
    {
        $name = trim((string)($input['name'] ?? ''));
        $email = mb_strtolower(trim((string)($input['email'] ?? '')));
        $password = (string)($input['password'] ?? '');

        $errors = [];

        if ($name === '') {
            $errors['name'] = I18n::t('install.admin_name_required');
        }

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = I18n::t('install.admin_email_invalid');
        }

        if (mb_strlen($password) < 8) {
            $errors['password'] = I18n::t('install.admin_password_min');
        }

        $lengthErrors = $this->schemaValidator->validate('users', [
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ], [
            'name' => 'name',
            'email' => 'email',
            'password' => 'password',
        ]);

        foreach ($lengthErrors as $field => $message) {
            if (!isset($errors[$field])) {
                $errors[$field] = $message;
            }
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

    public function install(array $db, array $admin, string $lang): array
    {
        try {
            $prefix = $this->normalizePrefix((string)($db['db_prefix'] ?? ''));
        } catch (\RuntimeException $e) {
            return ['success' => false, 'message' => I18n::t('install.db_prefix_invalid')];
        }

        try {
            $pdo = $this->connect($db);
        } catch (PDOException $e) {
            return ['success' => false, 'message' => I18n::t('install.db_connect_failed')];
        }

        if ($this->hasExistingSchema($pdo, (string)($db['db_name'] ?? ''), $prefix)) {
            return ['success' => false, 'message' => I18n::t('install.prefix_in_use')];
        }

        try {
            $this->createSchema($pdo, $prefix);
        } catch (PDOException $e) {
            return ['success' => false, 'message' => I18n::t('install.schema_failed')];
        }

        try {
            $db['db_prefix'] = $prefix;
            $this->writeConfig($db, $lang);
        } catch (\RuntimeException $e) {
            return ['success' => false, 'message' => I18n::t('install.config_failed')];
        }

        $adminResult = $this->createAdmin($pdo, $admin, $prefix);
        if ($adminResult !== null) {
            return ['success' => false, 'message' => $adminResult];
        }

        return ['success' => true, 'message' => I18n::t('install.success')];
    }

    private function connect(array $db): PDO
    {
        $dsn = 'mysql:host=' . $db['db_host'] . ';dbname=' . $db['db_name'] . ';charset=utf8mb4';
        $pdo = new PDO($dsn, (string)$db['db_user'], (string)$db['db_pass']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    }

    private function createAdmin(PDO $pdo, array $admin, string $prefix = ''): ?string
    {
        $prefix = $this->normalizePrefix($prefix);
        $users = $prefix . 'users';
        try {
            $exists = $pdo->prepare("SELECT id, role FROM $users WHERE email = :email LIMIT 1");
            $exists->execute(['email' => (string)$admin['email']]);
            $row = $exists->fetch(PDO::FETCH_ASSOC);

            if (is_array($row)) {
                if ((string)($row['role'] ?? '') === 'admin') {
                    return null;
                }

                return I18n::t('install.email_exists_other_role');
            }

            $insert = $pdo->prepare("INSERT INTO $users (name, email, password, role, suspend) VALUES (:name, :email, :password, :role, :suspend)");
            $insert->execute([
                'name' => (string)$admin['name'],
                'email' => (string)$admin['email'],
                'password' => password_hash((string)$admin['password'], PASSWORD_DEFAULT),
                'role' => 'admin',
                'suspend' => 0,
            ]);

            return null;
        } catch (PDOException $e) {
            return I18n::t('install.create_admin_failed');
        }
    }

    private function writeConfig(array $db, string $lang = APP_LANG): void
    {
        $normalizedLang = strtolower(trim($lang));
        $prefix = $this->normalizePrefix((string)($db['db_prefix'] ?? ''));
        $content = "<?php\ndeclare(strict_types=1);\n\ndefine('BASE_DIR', __DIR__);\ndefine('SRC_DIR', 'src/');\ndefine('INC_DIR', SRC_DIR . 'inc/');\ndefine('VIEW_DIR', SRC_DIR . 'view/');\ndefine('ASSETS_DIR', SRC_DIR . 'assets/');\ndefine('THEMES_DIR', 'themes/');\n\nconst APP_DEBUG = false;\nconst APP_VERSION = '0.9.0';\nconst APP_LANG = " . var_export($normalizedLang, true) . ";\nconst APP_DATE_FORMAT = 'd.m.Y';\nconst APP_DATETIME_FORMAT = 'd.m.Y H:i';\nconst APP_POSTS_PER_PAGE = 10;\n\n"
            . "const DB_HOST = " . var_export((string)$db['db_host'], true) . ";\n"
            . "const DB_NAME = " . var_export((string)$db['db_name'], true) . ";\n"
            . "const DB_USER = " . var_export((string)$db['db_user'], true) . ";\n"
            . "const DB_PASS = " . var_export((string)$db['db_pass'], true) . ";\n\n"
            . "const DB_PREFIX = " . var_export($prefix, true) . ";\n\n"
            . "const MEDIA_SMALL_WIDTH = 300;\n"
            . "const MEDIA_SMALL_HEIGHT = 300;\n"
            . "const MEDIA_MEDIUM_WIDTH = 768;\n";

        $written = file_put_contents(BASE_DIR . '/config.php', $content, LOCK_EX);

        if ($written === false) {
            throw new \RuntimeException('Config write failed.');
        }
    }

    private function createSchema(PDO $pdo, string $prefix = ''): void
    {
        $prefix = $this->normalizePrefix($prefix);
        foreach (SchemaDefinition::ddl($prefix) as $query) {
            $pdo->exec($query);
        }
    }

    private function normalizePrefix(string $prefix): string
    {
        $clean = trim($prefix);

        if ($clean === '') {
            return $clean;
        }

        if (preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $clean) === 1) {
            return str_ends_with($clean, '_') ? $clean : $clean . '_';
        }

        throw new \RuntimeException('Invalid database prefix.');
    }

    private function hasExistingSchema(PDO $pdo, string $dbName, string $prefix): bool
    {
        $schema = trim($dbName);
        if ($schema === '') {
            return false;
        }

        $tables = ['users', 'media', 'content', 'terms', 'content_terms', 'content_media', 'settings'];
        $check = $pdo->prepare('SELECT 1 FROM information_schema.tables WHERE table_schema = :schema AND table_name = :table LIMIT 1');

        foreach ($tables as $table) {
            $check->execute([
                'schema' => $schema,
                'table' => $prefix . $table,
            ]);

            if ($check->fetchColumn() !== false) {
                return true;
            }
        }

        return false;
    }
}
