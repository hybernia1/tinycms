<?php
declare(strict_types=1);

namespace App\Service\Feature;

use App\Service\Infra\Db\SchemaConstraintValidator;
use App\Service\Support\I18n;
use App\Service\Support\MailService;
use PDO;
use PDOException;

final class InstallService
{
    private SchemaConstraintValidator $schemaValidator;
    private MailService $mail;

    public function __construct(?SchemaConstraintValidator $schemaValidator = null)
    {
        $this->schemaValidator = $schemaValidator ?? new SchemaConstraintValidator();
        $this->mail = new MailService();
    }

    public function validateDatabaseInput(array $input): array
    {
        $host = trim((string)($input['db_host'] ?? ''));
        $name = trim((string)($input['db_name'] ?? ''));
        $user = trim((string)($input['db_user'] ?? ''));
        $pass = (string)($input['db_pass'] ?? '');

        $errors = [];

        if ($host === '') {
            $errors['db_host'] = I18n::t('install.db_host_required', 'Host is required.');
        }

        if ($name === '') {
            $errors['db_name'] = I18n::t('install.db_name_required', 'Database name is required.');
        }

        if ($user === '') {
            $errors['db_user'] = I18n::t('install.db_user_required', 'Database user is required.');
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
            return I18n::t('install.db_connect_failed', 'Cannot connect to database.');
        }
    }

    public function validateAdminInput(array $input): array
    {
        $name = trim((string)($input['name'] ?? ''));
        $email = mb_strtolower(trim((string)($input['email'] ?? '')));
        $password = (string)($input['password'] ?? '');

        $errors = [];

        if ($name === '') {
            $errors['name'] = I18n::t('install.admin_name_required', 'Name is required.');
        }

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = I18n::t('install.admin_email_invalid', 'Email is not valid.');
        }

        if (mb_strlen($password) < 8) {
            $errors['password'] = I18n::t('install.admin_password_min', 'Password must have at least 8 characters.');
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
            $pdo = $this->connect($db);
        } catch (PDOException $e) {
            return ['success' => false, 'message' => I18n::t('install.db_connect_failed', 'Cannot connect to database.')];
        }

        try {
            $this->createSchema($pdo);
        } catch (PDOException $e) {
            return ['success' => false, 'message' => I18n::t('install.schema_failed', 'Could not create database schema.')];
        }

        $adminResult = $this->createAdmin($pdo, $admin);
        if ($adminResult !== null) {
            return ['success' => false, 'message' => $adminResult];
        }

        $this->seedDefaultSettings($pdo, $lang);

        try {
            $this->writeConfig($db, $lang);
        } catch (\RuntimeException $e) {
            return ['success' => false, 'message' => I18n::t('install.config_failed', 'Could not create config.php. Check write permissions.')];
        }

        $this->mail->send(
            ['mail_driver' => 'php'],
            (string)$admin['email'],
            I18n::t('email.install_success.subject', 'Installation completed'),
            str_replace('{site}', 'TinyCMS', I18n::t('email.install_success.body', 'Installation for {site} was completed successfully.'))
        );

        return ['success' => true, 'message' => I18n::t('install.success', 'Installation completed successfully.')];
    }

    private function connect(array $db): PDO
    {
        $dsn = 'mysql:host=' . $db['db_host'] . ';dbname=' . $db['db_name'] . ';charset=utf8mb4';
        $pdo = new PDO($dsn, (string)$db['db_user'], (string)$db['db_pass']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    }

    private function createAdmin(PDO $pdo, array $admin): ?string
    {
        try {
            $exists = $pdo->prepare('SELECT id, role FROM users WHERE email = :email LIMIT 1');
            $exists->execute(['email' => (string)$admin['email']]);
            $row = $exists->fetch(PDO::FETCH_ASSOC);

            if (is_array($row)) {
                if ((string)($row['role'] ?? '') === 'admin') {
                    return null;
                }

                return I18n::t('install.email_exists_other_role', 'This email already exists with a different role.');
            }

            $insert = $pdo->prepare('INSERT INTO users (name, email, password, role, suspend, created, updated) VALUES (:name, :email, :password, :role, :suspend, :created, :updated)');
            $now = date('Y-m-d H:i:s');
            $insert->execute([
                'name' => (string)$admin['name'],
                'email' => (string)$admin['email'],
                'password' => password_hash((string)$admin['password'], PASSWORD_DEFAULT),
                'role' => 'admin',
                'suspend' => 0,
                'created' => $now,
                'updated' => $now,
            ]);

            return null;
        } catch (PDOException $e) {
            return I18n::t('install.create_admin_failed', 'Could not create admin account.');
        }
    }

    private function seedDefaultSettings(PDO $pdo, string $lang): void
    {
        $rows = [
            'app_lang' => strtolower(trim($lang)) !== '' ? strtolower(trim($lang)) : (string)APP_LANG,
            'allow_registration' => '1',
            'mail_driver' => 'php',
        ];

        $stmt = $pdo->prepare('INSERT INTO settings (key_name, value) VALUES (:key_name, :value) ON DUPLICATE KEY UPDATE value = VALUES(value)');
        foreach ($rows as $key => $value) {
            $stmt->execute([
                'key_name' => $key,
                'value' => json_encode($value, JSON_UNESCAPED_UNICODE),
            ]);
        }
    }

    private function writeConfig(array $db, string $lang = APP_LANG): void
    {
        $normalizedLang = strtolower(trim($lang));
        $content = "<?php\ndeclare(strict_types=1);\n\ndefine('INC_DIR', 'inc/');\n\nconst APP_DEBUG = false;\nconst APP_LANG = " . var_export($normalizedLang, true) . ";\nconst APP_DATE_FORMAT = 'd.m.Y';\nconst APP_DATETIME_FORMAT = 'd.m.Y H:i';\n\n"
            . "const DB_HOST = " . var_export((string)$db['db_host'], true) . ";\n"
            . "const DB_NAME = " . var_export((string)$db['db_name'], true) . ";\n"
            . "const DB_USER = " . var_export((string)$db['db_user'], true) . ";\n"
            . "const DB_PASS = " . var_export((string)$db['db_pass'], true) . ";\n\n"
            . "const MEDIA_THUMB_VARIANTS = [\n"
            . "    ['suffix' => '_100x100.webp', 'mode' => 'crop', 'width' => 100, 'height' => 100],\n"
            . "    ['suffix' => '_w768.webp', 'mode' => 'fit', 'width' => 768],\n"
            . "];\n";

        $written = file_put_contents(dirname(__DIR__, 3) . '/config.php', $content, LOCK_EX);

        if ($written === false) {
            throw new \RuntimeException('Config write failed.');
        }
    }

    private function createSchema(PDO $pdo): void
    {
        foreach (SchemaDefinition::ddl() as $query) {
            $pdo->exec($query);
        }
    }
}
