<?php
declare(strict_types=1);

use App\Auth\Login;
use App\Auth\LoginLayer;
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

$router->get('', static function () use ($view, $auth): void {
    $view->render('front', 'front/index', [
        'user' => $auth->user(),
    ]);
});

$router->get('login', static function () use ($auth, $redirect, $view): void {
    if ($auth->check()) {
        $redirect($auth->isAdmin() ? 'admin/dashboard' : '');
    }

    $view->render('login', 'login/form', [
        'errors' => [],
        'message' => '',
        'old' => ['email' => ''],
    ]);
});

$router->post('login', static function () use ($auth, $redirect, $view): void {
    if ($auth->check()) {
        $redirect($auth->isAdmin() ? 'admin/dashboard' : '');
    }

    $old = ['email' => trim((string)($_POST['email'] ?? ''))];
    $layer = new LoginLayer(new Login(new Query(Connection::get())), $auth);
    $result = $layer->attempt([
        'email' => $old['email'],
        'password' => (string)($_POST['password'] ?? ''),
    ]);

    if (($result['success'] ?? false) === true) {
        $redirect((string)$result['redirect']);
    }

    $view->render('login', 'login/form', [
        'errors' => $result['errors'] ?? [],
        'message' => (string)($result['message'] ?? 'Přihlášení selhalo.'),
        'old' => $old,
    ]);
});


$router->get('front/login', static function () use ($redirect): void {
    $redirect('login');
});

$router->get('admin/login', static function () use ($redirect): void {
    $redirect('login');
});

$router->get('admin', static function () use ($auth, $redirect): void {
    if (!$auth->check()) {
        $redirect('login');
    }

    $redirect($auth->isAdmin() ? 'admin/dashboard' : '');
});

$router->get('admin/dashboard', static function () use ($auth, $redirect, $view): void {
    if (!$auth->check()) {
        $redirect('login');
    }

    if (!$auth->isAdmin()) {
        $redirect('');
    }

    $view->render('admin', 'admin/dashboard', [
        'user' => $auth->user(),
    ]);
});

$router->get('admin/logout', static function () use ($auth, $redirect): void {
    $auth->logout();
    $redirect('login');
});

if (!$router->dispatch($_SERVER['REQUEST_URI'] ?? '/', $_SERVER['REQUEST_METHOD'] ?? 'GET')) {
    $render404();
}
