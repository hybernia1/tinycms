<?php
declare(strict_types=1);

namespace App\Controller\Api;

use App\Service\Application\Auth;
use App\Service\Support\Csrf;
use App\Service\Support\I18n;
use App\Service\Support\RateLimiter;

final class Heartbeat
{
    public function __construct(
        private Auth $authService,
        private Csrf $csrf,
        private RateLimiter $rateLimiter
    ) {
    }

    public function heartbeatApiV1(): void
    {
        $rateLimit = $this->rateLimiter->hit($this->rateKey('heartbeat'), 120, 60);
        if (($rateLimit['allowed'] ?? false) !== true) {
            $this->apiError('RATE_LIMITED', I18n::t('auth.too_many_attempts'), 429, [
                'retry_after' => (int)($rateLimit['retry_after'] ?? 60),
            ]);
            return;
        }

        if ($this->authService->canAccessAdmin()) {
            $this->apiOk([
                'authenticated' => true,
                'csrf' => $this->csrf->token(),
            ]);
            return;
        }

        $this->apiError('UNAUTHENTICATED', I18n::t('auth.session_expired'), 401, [
            'authenticated' => false,
            'csrf' => $this->csrf->token(),
        ]);
    }

    public function loginApiV1(): void
    {
        $rateLimit = $this->rateLimiter->hit($this->rateKey('login'), 10, 300);
        if (($rateLimit['allowed'] ?? false) !== true) {
            $this->apiError('RATE_LIMITED', I18n::t('auth.too_many_attempts'), 429, [
                'retry_after' => (int)($rateLimit['retry_after'] ?? 300),
            ]);
            return;
        }

        if (!$this->csrf->verify((string)($_POST['_csrf'] ?? ''))) {
            $this->apiError('INVALID_CSRF', I18n::t('common.csrf_expired'), 419, [
                'errors' => [],
                'csrf' => $this->csrf->token(),
            ]);
            return;
        }

        $email = trim((string)($_POST['email'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $errors = [];

        if ($email === '') {
            $errors['email'] = I18n::t('auth.email_required');
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = I18n::t('auth.email_invalid_format');
        }

        if ($password === '') {
            $errors['password'] = I18n::t('auth.password_required');
        }

        if ($errors !== []) {
            $this->apiError('INVALID_DATA', I18n::t('auth.form_has_errors'), 422, [
                'errors' => $errors,
                'csrf' => $this->csrf->token(),
            ]);
            return;
        }

        $result = $this->authService->login([
            'email' => $email,
            'password' => $password,
            'remember' => (int)((int)($_POST['remember'] ?? 0) === 1),
        ]);
        if (($result['success'] ?? false) !== true) {
            $this->apiError('LOGIN_FAILED', I18n::t('auth.invalid_credentials'), 422, [
                'errors' => [],
                'csrf' => $this->csrf->token(),
            ]);
            return;
        }

        $this->apiOk([
            'authenticated' => true,
            'message' => I18n::t('auth.login_success'),
            'csrf' => $this->csrf->token(),
        ]);
    }

    private function respondJson(array $payload, int $statusCode = 200): void
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');
        http_response_code($statusCode);
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    }

    private function rateKey(string $scope): string
    {
        $ip = trim((string)($_SERVER['REMOTE_ADDR'] ?? ''));
        $userAgent = trim((string)($_SERVER['HTTP_USER_AGENT'] ?? ''));

        return $scope . '|' . $ip . '|' . $userAgent;
    }

    private function apiOk(array $data = [], int $statusCode = 200): void
    {
        $payload = ['ok' => true];
        if ($data !== []) {
            $payload['data'] = $data;
        }

        $this->respondJson($payload, $statusCode);
    }

    private function apiError(string $code, string $message, int $statusCode = 422, array $details = []): void
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
}
