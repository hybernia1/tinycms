<?php
declare(strict_types=1);

namespace App\Controller\Api;

use App\Controller\Admin\BaseAdmin;
use App\Service\Application\Auth;
use App\Service\Support\Csrf;
use App\Service\Support\Flash;
use App\Service\Support\I18n;
use App\Service\Support\RateLimiter;

final class Sessions extends BaseAdmin
{
    public function __construct(
        Auth $authService,
        Flash $flash,
        Csrf $csrf,
        private RateLimiter $rateLimiter
    ) {
        parent::__construct($authService, $flash, $csrf);
    }

    public function heartbeatApiV1(): void
    {
        if (!$this->guardRateLimit('heartbeat', 120, 60)) {
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
        if (!$this->guardRateLimit('login', 10, 300)) {
            return;
        }

        if (!$this->csrf->verify((string)($_POST['_csrf'] ?? ''))) {
            $this->apiError('INVALID_CSRF', I18n::t('common.invalid_csrf'), 419, [
                'errors' => [],
                ...$this->csrfPayload(),
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
                ...$this->csrfPayload(),
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
                ...$this->csrfPayload(),
            ]);
            return;
        }

        $this->apiOk([
            'authenticated' => true,
            'message' => I18n::t('auth.login_success'),
            'csrf' => $this->csrf->token(),
        ]);
    }

    private function guardRateLimit(string $scope, int $limit, int $windowSeconds): bool
    {
        $rateLimit = $this->rateLimiter->hit($this->rateKey($scope), $limit, $windowSeconds);
        if (($rateLimit['allowed'] ?? false) === true) {
            return true;
        }

        $this->apiError('RATE_LIMITED', I18n::t('auth.too_many_attempts'), 429, [
            'retry_after' => (int)($rateLimit['retry_after'] ?? $windowSeconds),
        ]);
        return false;
    }

    private function rateKey(string $scope): string
    {
        $ip = trim((string)($_SERVER['REMOTE_ADDR'] ?? ''));
        return $scope . '|' . $ip;
    }

    private function csrfPayload(): array
    {
        return ['csrf' => $this->csrf->token()];
    }
}
