<?php
declare(strict_types=1);

namespace App\Controller\Api;

use App\Service\Application\Auth;
use App\Service\Support\Csrf;
use App\Service\Support\I18n;

final class Heartbeat
{
    public function __construct(
        private Auth $authService,
        private Csrf $csrf
    ) {
    }

    public function heartbeatApiV1(): void
    {
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
        if (!$this->csrf->verify((string)($_POST['_csrf'] ?? ''))) {
            $this->apiError('INVALID_CSRF', I18n::t('common.csrf_expired'), 419, [
                'errors' => [],
                'csrf' => $this->csrf->token(),
            ]);
            return;
        }

        $result = $this->authService->login($_POST);
        if (($result['success'] ?? false) !== true) {
            $this->apiError('LOGIN_FAILED', (string)($result['message'] ?? I18n::t('auth.login_failed')), 422, [
                'errors' => $result['errors'] ?? [],
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
        http_response_code($statusCode);
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
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
