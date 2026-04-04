<?php
declare(strict_types=1);

namespace App\Service\Feature;

use App\Service\Infra\Db\SchemaConstraintValidator;
use PDO;
use PDOException;

final class InstallService
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
        }

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'E-mail není validní.';
        }

        if (mb_strlen($password) < 8) {
            $errors['password'] = 'Heslo musí mít alespoň 8 znaků.';
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

    public function install(array $db, array $admin): array
    {
        try {
            $pdo = $this->connect($db);
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Nelze se připojit k databázi.'];
        }

        try {
            $this->createSchema($pdo);
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Nepodařilo se vytvořit databázové tabulky.'];
        }

        $adminResult = $this->createAdmin($pdo, $admin);
        if ($adminResult !== null) {
            return ['success' => false, 'message' => $adminResult];
        }

        try {
            $this->writeConfig($db);
        } catch (\RuntimeException $e) {
            return ['success' => false, 'message' => 'Nepodařilo se vytvořit config.php. Zkontrolujte oprávnění zápisu.'];
        }

        return ['success' => true, 'message' => 'Instalace proběhla úspěšně.'];
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

                return 'Tento e-mail už existuje pod jinou rolí.';
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
            return 'Nepodařilo se vytvořit admin účet.';
        }
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
