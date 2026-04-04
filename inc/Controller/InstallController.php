<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\Feature\InstallService;
use App\Service\Support\CsrfService;
use App\View\View;
use PDO;
use PDOException;

final class InstallController
{
    private View $view;
    private CsrfService $csrf;
    private InstallService $installService;

    public function __construct(View $view, CsrfService $csrf, InstallService $installService)
    {
        $this->view = $view;
        $this->csrf = $csrf;
        $this->installService = $installService;

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

    public function formDb(): void
    {
        $state = $_SESSION['install'] ?? [];

        $this->view->render('front/layout', 'front/install/step-db', [
            'pageTitle' => 'Instalace - databáze',
            'errors' => (array)($state['errors_db'] ?? []),
            'old' => (array)($state['db'] ?? ['db_host' => '127.0.0.1', 'db_name' => '', 'db_user' => '', 'db_pass' => '']),
            'message' => (string)($state['message'] ?? ''),
        ]);

        unset($_SESSION['install']['errors_db'], $_SESSION['install']['message']);
    }

    public function submitDb(callable $redirect): void
    {
        if (!$this->csrf->verify((string)($_POST['_csrf'] ?? ''))) {
            $_SESSION['install']['message'] = 'Token vypršel, odešlete formulář znovu.';
            $redirect('install');
        }

        $result = $this->installService->validateDatabaseInput($_POST);

        if ($result['errors'] !== []) {
            $_SESSION['install']['errors_db'] = $result['errors'];
            $_SESSION['install']['db'] = $result['values'];
            $redirect('install');
        }

        $error = $this->installService->canConnect($result['values']);

        if ($error !== null) {
            $_SESSION['install']['errors_db'] = ['db' => $error];
            $_SESSION['install']['db'] = $result['values'];
            $redirect('install');
        }

        $_SESSION['install']['db'] = $result['values'];
        $_SESSION['install']['errors_admin'] = [];
        $_SESSION['install']['admin'] = ['name' => '', 'email' => '', 'password' => ''];

        $redirect('install/admin');
    }

    public function formAdmin(callable $redirect): void
    {
        if (!isset($_SESSION['install']['db'])) {
            $redirect('install');
        }

        $state = $_SESSION['install'] ?? [];

        $this->view->render('front/layout', 'front/install/step-admin', [
            'pageTitle' => 'Instalace - admin',
            'errors' => (array)($state['errors_admin'] ?? []),
            'old' => (array)($state['admin'] ?? ['name' => '', 'email' => '', 'password' => '']),
            'message' => (string)($state['message'] ?? ''),
        ]);

        unset($_SESSION['install']['errors_admin'], $_SESSION['install']['message']);
    }

    public function submitAdmin(callable $redirect): void
    {
        if (!isset($_SESSION['install']['db'])) {
            $redirect('install');
        }

        if (!$this->csrf->verify((string)($_POST['_csrf'] ?? ''))) {
            $_SESSION['install']['message'] = 'Token vypršel, odešlete formulář znovu.';
            $redirect('install/admin');
        }

        $result = $this->installService->validateAdminInput($_POST);

        if ($result['errors'] !== []) {
            $_SESSION['install']['errors_admin'] = $result['errors'];
            $_SESSION['install']['admin'] = $result['values'];
            $redirect('install/admin');
        }

        $_SESSION['install']['admin'] = $result['values'];

        $install = $this->installService->install((array)$_SESSION['install']['db']);

        if (($install['success'] ?? false) !== true) {
            $_SESSION['install']['message'] = (string)($install['message'] ?? 'Instalace selhala.');
            $redirect('install/admin');
        }

        $adminCreated = $this->createAdmin((array)$_SESSION['install']['db'], (array)$_SESSION['install']['admin']);

        if ($adminCreated !== null) {
            $_SESSION['install']['message'] = $adminCreated;
            $redirect('install/admin');
        }

        $_SESSION['install']['done'] = true;
        $redirect('install/done');
    }

    public function done(callable $redirect): void
    {
        if (!isset($_SESSION['install']['done']) || $_SESSION['install']['done'] !== true) {
            $redirect('install');
        }

        unset($_SESSION['install']);

        $this->view->render('front/layout', 'front/install/done', [
            'pageTitle' => 'Instalace dokončena',
        ]);
    }


    private function createAdmin(array $db, array $admin): ?string
    {
        try {
            $dsn = 'mysql:host=' . $db['db_host'] . ';dbname=' . $db['db_name'] . ';charset=utf8mb4';
            $pdo = new PDO($dsn, (string)$db['db_user'], (string)$db['db_pass']);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $exists = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
            $exists->execute(['email' => $admin['email']]);

            if ($exists->fetchColumn() !== false) {
                return 'Tento e-mail už existuje.';
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
}
