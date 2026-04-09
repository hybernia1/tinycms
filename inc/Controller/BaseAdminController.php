<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\Feature\AuthService;
use App\Service\Support\CsrfService;
use App\Service\Support\FlashService;
use App\Service\Support\I18n;
use App\Service\Support\PaginationConfig;

abstract class BaseAdminController
{
    public function __construct(
        protected AuthService $authService,
        protected FlashService $flash,
        protected CsrfService $csrf
    ) {
    }

    protected function guardAdmin(callable $redirect, bool $flashDenied = true): bool
    {
        if (!$this->authService->auth()->check()) {
            $redirect('login');
            return false;
        }

        if (!$this->authService->canAccessAdmin()) {
            if ($flashDenied) {
                $this->flash->add('info', I18n::t('admin.access_denied'));
            }

            $redirect('');
            return false;
        }

        return true;
    }

    protected function guardSuperAdmin(callable $redirect, bool $flashDenied = true): bool
    {
        if (!$this->authService->auth()->check()) {
            $redirect('login');
            return false;
        }

        if (!$this->authService->auth()->isAdmin()) {
            if ($flashDenied) {
                $this->flash->add('info', I18n::t('admin.access_denied'));
            }

            $redirect('admin/dashboard');
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

    protected function guardSuperAdminCsrf(callable $redirect, string $redirectPath, string $message, bool $flashDenied = true): bool
    {
        return $this->guardSuperAdmin($redirect, $flashDenied)
            && $this->guardCsrf($redirect, $redirectPath, $message);
    }

    protected function storeFormState(string $sessionKey, string $mode, ?int $id, array $data, array $errors): void
    {
        $this->ensureSession();
        $_SESSION[$sessionKey] = [
            'mode' => $mode,
            'id' => $id,
            'data' => $data,
            'errors' => $errors,
        ];
    }

    protected function consumeFormState(string $sessionKey, string $mode, ?int $id = null): ?array
    {
        $this->ensureSession();
        $state = $_SESSION[$sessionKey] ?? null;
        unset($_SESSION[$sessionKey]);

        if (!is_array($state)) {
            return null;
        }

        if (($state['mode'] ?? null) !== $mode || ($state['id'] ?? null) !== $id) {
            return null;
        }

        return $state;
    }

    protected function respondJson(array $payload, int $statusCode = 200): void
    {
        header('Content-Type: application/json; charset=utf-8');
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

    protected function currentUserId(): int
    {
        return (int)($this->authService->auth()->id() ?? 0);
    }

    protected function isEditor(): bool
    {
        return (string)($this->authService->auth()->role() ?? '') === 'editor';
    }

    protected function canManageByAuthor(array $item, string $authorKey = 'author'): bool
    {
        if (!$this->isEditor()) {
            return true;
        }

        return (int)($item[$authorKey] ?? 0) === $this->currentUserId();
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

    protected function applyEditorAuthor(array $input, int $authorId, string $key = 'author'): array
    {
        if (!$this->isEditor()) {
            return $input;
        }

        $input[$key] = $authorId > 0 ? (string)$authorId : '';
        return $input;
    }

    protected function resolvePaginationQuery(): array
    {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $defaultPerPage = PaginationConfig::perPage();
        $perPage = (int)($_GET['per_page'] ?? $defaultPerPage);
        $query = trim((string)($_GET['q'] ?? ''));

        if (!in_array($perPage, PaginationConfig::allowed(), true)) {
            $perPage = $defaultPerPage;
        }

        return [$page, $perPage, $query];
    }

    protected function resolvePreviewPath(array $item): string
    {
        $pathWebp = trim((string)($item['path_webp'] ?? ''));
        if ($pathWebp !== '') {
            return (string)(preg_replace('/\\.webp$/i', $this->thumbnailSuffix(), $pathWebp) ?? $pathWebp);
        }

        return trim((string)($item['path'] ?? ''));
    }

    protected function thumbnailSuffix(): string
    {
        $suffix = '_100x100.webp';
        if (defined('MEDIA_THUMB_VARIANTS') && is_array(MEDIA_THUMB_VARIANTS)) {
            $firstVariant = MEDIA_THUMB_VARIANTS[0] ?? null;
            if (is_array($firstVariant) && !empty($firstVariant['suffix'])) {
                $suffix = (string)$firstVariant['suffix'];
            }
        }

        return $suffix;
    }

    private function ensureSession(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }
}
