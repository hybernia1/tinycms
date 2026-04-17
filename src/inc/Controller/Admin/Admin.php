<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\Application\Auth;
use App\Service\Support\Csrf;
use App\Service\Support\Flash;
use App\Service\Support\I18n;
use App\Service\Support\Media;
use App\Service\Support\PaginationConfig;
use App\View\AdminView;

class Admin
{
    public function __construct(
        protected Auth $authService,
        protected Flash $flash,
        protected Csrf $csrf,
        private ?AdminView $pages = null
    ) {
    }

    public function index(callable $redirect): void
    {
        if (!$this->guardAdmin($redirect)) {
            return;
        }

        $redirect($this->authService->canAccessAdmin() ? 'admin/dashboard' : '');
    }

    public function dashboard(callable $redirect): void
    {
        if (!$this->guardAdmin($redirect)) {
            return;
        }

        $this->requirePages()->adminDashboard($this->authService->auth()->user());
    }

    public function loginForm(callable $redirect): void
    {
        if ($this->authService->auth()->check()) {
            $redirect($this->authService->redirectAfterLogin());
        }

        $this->requirePages()->adminLoginForm([
            'old' => ['email' => '', 'remember' => 0],
        ]);
    }

    public function logout(callable $redirect): void
    {
        $this->authService->auth()->logout();
        $redirect('admin/login');
    }

    protected function guardAdmin(callable $redirect, bool $flashDenied = true): bool
    {
        if (!$this->authService->auth()->check()) {
            $redirect('admin/login');
            return false;
        }

        if (!$this->authService->canAccessAdmin()) {
            if ($flashDenied) {
                $this->flash->add('info', I18n::t('admin.access_denied'));
            }

            $redirect('admin/login');
            return false;
        }

        return true;
    }

    protected function guardCsrf(callable $redirect, string $redirectPath, string $message): bool
    {
        if ($this->csrf->verify((string)($_POST['_csrf'] ?? ''))) {
            return true;
        }

        $this->flash->add('error', $message);
        $redirect($redirectPath);
        return false;
    }

    protected function guardAdminCsrf(callable $redirect, string $redirectPath, string $message, bool $flashDenied = false): bool
    {
        return $this->guardAdmin($redirect, $flashDenied)
            && $this->guardCsrf($redirect, $redirectPath, $message);
    }

    protected function guardApiAdmin(bool $flashDenied = false): bool
    {
        if (!$this->authService->auth()->check()) {
            $this->apiError('UNAUTHENTICATED', I18n::t('admin.access_denied'), 401);
            return false;
        }

        if (!$this->authService->canAccessAdmin()) {
            if ($flashDenied) {
                $this->flash->add('info', I18n::t('admin.access_denied'));
            }
            $this->apiError('FORBIDDEN', I18n::t('admin.access_denied'), 403);
            return false;
        }

        return true;
    }

    protected function guardApiAdminCsrf(?string $message = null, bool $flashDenied = false): bool
    {
        if (!$this->guardApiAdmin($flashDenied)) {
            return false;
        }

        if ($this->csrf->verify((string)($_POST['_csrf'] ?? ''))) {
            return true;
        }

        $this->apiError('INVALID_CSRF', $message ?? I18n::t('common.invalid_csrf'), 419);
        return false;
    }

    protected function respondJson(array $payload, int $statusCode = 200): void
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');
        if ($statusCode === 429 && isset($payload['error']['retry_after'])) {
            header('Retry-After: ' . (int)$payload['error']['retry_after']);
        }
        http_response_code($statusCode);
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    }

    protected function apiOk(array $data = [], array $meta = [], int $statusCode = 200): void
    {
        $payload = ['ok' => true];
        if ($data !== []) {
            $payload['data'] = $data;
        }
        if ($meta !== []) {
            $payload['meta'] = $meta;
        }
        $this->respondJson($payload, $statusCode);
    }

    protected function apiError(string $code, string $message, int $statusCode = 422, array $details = []): void
    {
        $error = [
            'code' => $code,
            'message' => $message,
        ];
        if ($details !== []) {
            $error = array_merge($error, $details);
        }

        $this->respondJson([
            'ok' => false,
            'error' => $error,
        ], $statusCode);
    }

    protected function requirePositiveId(int $id, string $code, string $message, int $statusCode = 400): bool
    {
        if ($id > 0) {
            return true;
        }

        $this->apiError($code, $message, $statusCode);
        return false;
    }

    protected function requireEntity(?array $entity, string $code, string $message, int $statusCode = 404): bool
    {
        if ($entity !== null) {
            return true;
        }

        $this->apiError($code, $message, $statusCode);
        return false;
    }

    protected function formatDateTime(string $value): string
    {
        $stamp = $value !== '' ? strtotime($value) : false;
        if ($stamp === false) {
            return '';
        }

        return date(APP_DATETIME_FORMAT, $stamp);
    }

    protected function hasUpload(string $field): bool
    {
        return isset($_FILES[$field]) && (int)($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
    }

    protected function resolvePaginationQuery(): array
    {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = PaginationConfig::perPage();
        $query = trim((string)($_GET['q'] ?? ''));

        return [$page, $perPage, $query];
    }

    protected function resolveStatusFilter(array $allowed, string $default = 'all', string $param = 'status'): string
    {
        $status = trim((string)($_GET[$param] ?? $default));
        return in_array($status, $allowed, true) ? $status : $default;
    }

    protected function buildListMeta(array $pagination, int $perPage, string $status, string $query, array $statusCounts): array
    {
        return [
            'page' => (int)($pagination['page'] ?? 1),
            'per_page' => (int)($pagination['per_page'] ?? $perPage),
            'total_pages' => (int)($pagination['total_pages'] ?? 1),
            'status' => $status,
            'query' => $query,
            'status_counts' => $statusCounts,
        ];
    }

    protected function buildEditPath(string $basePath, int $id): string
    {
        return $this->buildPath($basePath . '/edit?id=' . $id);
    }

    protected function buildPath(string $path): string
    {
        $scriptName = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));
        $baseDir = trim(dirname($scriptName), '/.');
        $basePath = $baseDir === '' ? '' : '/' . $baseDir;
        $normalized = '/' . ltrim($path, '/');

        return $basePath . $normalized;
    }

    protected function resolvePreviewPath(array $item): string
    {
        $path = trim((string)($item['path'] ?? ''));
        if ($path !== '') {
            return Media::bySize($path, 'small');
        }

        return '';
    }

    private function requirePages(): AdminView
    {
        if ($this->pages === null) {
            throw new \LogicException('AdminView is not configured.');
        }

        return $this->pages;
    }

}
