<?php
declare(strict_types=1);

use App\Auth\Login;
use App\Db\Connection;
use App\Db\Query;

$app = require __DIR__ . '/inc/bootstrap.php';
$router = $app['router'];
$view = $app['view'];
$auth = $app['auth'];

$redirect = function (string $path = '') use ($router): void {
    header('Location: ' . $router->url($path));
    exit;
};

$render404 = static function (): void {
    http_response_code(404);
    echo '404';
};

$router->get('', static function () use ($view): void {
    $view->render('front', 'front/index');
});

$router->get('login', static function () use ($view): void {
    $view->render('front', 'front/login');
});

$router->get('admin', static function () use ($auth, $redirect): void {
    $redirect($auth->check() ? 'admin/dashboard' : 'admin/login');
});

$router->get('admin/login', static function () use ($auth, $redirect, $view): void {
    if ($auth->check()) {
        $redirect('admin/dashboard');
    }

    $view->render('admin', 'admin/login', [
        'errors' => [],
        'message' => '',
        'old' => ['email' => ''],
    ]);
});

$router->post('admin/login', static function () use ($auth, $redirect, $view): void {
    if ($auth->check()) {
        $redirect('admin/dashboard');
    }

    $old = ['email' => trim((string)($_POST['email'] ?? ''))];
    $result = (new Login(new Query(Connection::get())))->attempt([
        'email' => $old['email'],
        'password' => (string)($_POST['password'] ?? ''),
    ]);

    if (($result['success'] ?? false) === true) {
        $redirect('admin/dashboard');
    }

    $view->render('admin', 'admin/login', [
        'errors' => $result['errors'] ?? [],
        'message' => (string)($result['message'] ?? 'Přihlášení selhalo.'),
        'old' => $old,
    ]);
});

$router->get('admin/dashboard', static function () use ($auth, $redirect, $view): void {
    if (!$auth->check()) {
        $redirect('admin/login');
    }

    $view->render('admin', 'admin/dashboard', [
        'user' => $auth->user(),
    ]);
});

$router->get('admin/logout', static function () use ($auth, $redirect): void {
    $auth->logout();
    $redirect('admin/login');
});

if (!$router->dispatch($_SERVER['REQUEST_URI'] ?? '/', $_SERVER['REQUEST_METHOD'] ?? 'GET')) {
    $render404();
}
