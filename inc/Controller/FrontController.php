<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\AuthService;
use App\Service\ContentService;
use App\Service\ContentTypeService;
use App\Service\CsrfService;
use App\Service\SettingsService;
use App\Service\SluggerService;
use App\View\PageView;

final class FrontController
{
    private PageView $pages;
    private AuthService $authService;
    private CsrfService $csrf;
    private SettingsService $settings;
    private ContentService $contentService;
    private ContentTypeService $contentTypes;
    private SluggerService $slugger;

    public function __construct(PageView $pages, AuthService $authService, CsrfService $csrf, SettingsService $settings, ContentService $contentService, ContentTypeService $contentTypes, SluggerService $slugger)
    {
        $this->pages = $pages;
        $this->authService = $authService;
        $this->csrf = $csrf;
        $this->settings = $settings;
        $this->contentService = $contentService;
        $this->contentTypes = $contentTypes;
        $this->slugger = $slugger;
    }

    public function home(): void
    {
        $settings = $this->settings->resolved();
        $site = [
            'name' => (string)($settings['main']['sitename'] ?? 'TinyCMS'),
            'footer' => (string)($settings['main']['sitefooter'] ?? '© TinyCMS'),
            'author' => (string)($settings['main']['siteauthor'] ?? 'Admin'),
        ];
        $posts = array_map(function (array $item): array {
            $id = (int)($item['id'] ?? 0);
            $type = $this->contentTypes->resolveBySlug((string)($item['type'] ?? ''));
            $typeSlug = (string)($type['slug'] ?? (string)($item['type'] ?? 'post'));
            return [
                'id' => $id,
                'type' => (string)($item['type'] ?? 'post'),
                'type_slug' => $typeSlug,
                'name' => (string)($item['name'] ?? ''),
                'excerpt' => (string)($item['excerpt'] ?? ''),
                'created' => (string)($item['created'] ?? ''),
                'slug' => $this->slugger->slug((string)($item['name'] ?? ''), $id),
            ];
        }, $this->contentService->listPublished('', 30));
        $posts = array_map(static function (array $item): array {
            $item['url'] = $item['type_slug'] . '/' . (string)($item['slug'] ?? '');
            return $item;
        }, $posts);

        $this->pages->home($this->authService->auth()->user(), $site, $posts);
    }

    public function contentDetail(array $params, callable $redirect): void
    {
        $type = $this->contentTypes->resolveBySlug((string)($params['typeSlug'] ?? ''));

        if ($type === null) {
            http_response_code(404);
            echo '404';
            return;
        }

        $id = $this->slugger->extractId((string)($params['slug'] ?? ''));
        $item = $id > 0 ? $this->contentService->findPublished($id, (string)$type['type']) : null;

        if ($item === null) {
            http_response_code(404);
            echo '404';
            return;
        }

        $slug = $this->slugger->slug((string)($item['name'] ?? ''), (int)($item['id'] ?? 0));
        $requestedSlug = trim((string)($params['slug'] ?? ''));

        if ($requestedSlug !== $slug) {
            $redirect((string)($type['slug'] ?? '') . '/' . $slug, true);
        }

        $this->pages->contentDetail([
            'type_slug' => (string)($type['slug'] ?? ''),
            'slug' => $slug,
            'id' => (int)($item['id'] ?? 0),
            'name' => (string)($item['name'] ?? ''),
            'excerpt' => (string)($item['excerpt'] ?? ''),
            'body' => (string)($item['body'] ?? ''),
            'created' => (string)($item['created'] ?? ''),
        ]);
    }

    public function loginForm(callable $redirect): void
    {
        if ($this->authService->auth()->check()) {
            $redirect($this->authService->redirectAfterLogin());
        }

        $this->pages->loginForm([
            'errors' => [],
            'message' => '',
            'old' => ['email' => '', 'remember' => 0],
        ]);
    }

    public function loginSubmit(callable $redirect): void
    {
        if ($this->authService->auth()->check()) {
            $redirect($this->authService->redirectAfterLogin());
        }

        if (!$this->csrf->verify((string)($_POST['_csrf'] ?? ''))) {
            $this->pages->loginForm([
                'errors' => [],
                'message' => 'Bezpečnostní token vypršel, odešlete formulář znovu.',
                'old' => ['email' => trim((string)($_POST['email'] ?? '')), 'remember' => (int)((int)($_POST['remember'] ?? 0) === 1)],
            ]);
            return;
        }

        $result = $this->authService->login($_POST);

        if (($result['success'] ?? false) === true) {
            $redirect((string)$result['redirect']);
        }

        $this->pages->loginForm([
            'errors' => $result['errors'] ?? [],
            'message' => (string)($result['message'] ?? 'Přihlášení selhalo.'),
            'old' => ['email' => trim((string)($_POST['email'] ?? '')), 'remember' => (int)((int)($_POST['remember'] ?? 0) === 1)],
        ]);
    }
}
