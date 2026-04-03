<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\Feature\AuthService;
use App\Service\Feature\ContentService;
use App\Service\Support\CsrfService;
use App\Service\Feature\SettingsService;
use App\Service\Support\SluggerService;
use App\View\PageView;

final class FrontController
{
    private PageView $pages;
    private AuthService $authService;
    private CsrfService $csrf;
    private SettingsService $settings;
    private ContentService $contentService;
    private SluggerService $slugger;

    public function __construct(PageView $pages, AuthService $authService, CsrfService $csrf, SettingsService $settings, ContentService $contentService, SluggerService $slugger)
    {
        $this->pages = $pages;
        $this->authService = $authService;
        $this->csrf = $csrf;
        $this->settings = $settings;
        $this->contentService = $contentService;
        $this->slugger = $slugger;
    }

    public function home(): void
    {
        $settings = $this->settings->resolved();
        $site = [
            'name' => (string)($settings['sitename'] ?? 'TinyCMS'),
            'footer' => (string)($settings['sitefooter'] ?? '© TinyCMS'),
            'author' => (string)($settings['siteauthor'] ?? 'Admin'),
        ];
        $posts = array_map(fn(array $item): array => $this->toPublicListItem($item), $this->contentService->listPublished(30));

        $this->pages->home($this->authService->auth()->user(), $site, $posts);
    }

    public function contentDetail(array $params, callable $redirect): void
    {
        $id = $this->slugger->extractId((string)($params['slug'] ?? ''));
        $item = $id > 0 ? $this->contentService->findPublished($id) : null;

        if ($item === null) {
            $this->notFound();
        }

        $slug = $this->slugger->slug((string)($item['name'] ?? ''), (int)($item['id'] ?? 0));
        $requestedSlug = trim((string)($params['slug'] ?? ''));

        if ($requestedSlug !== $slug) {
            $redirect($slug, true);
        }

        $this->pages->contentDetail($this->toDetailItem($item, $slug));
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

    private function notFound(): void
    {
        http_response_code(404);
        echo '404';
        exit;
    }

    private function toPublicListItem(array $item): array
    {
        $id = (int)($item['id'] ?? 0);
        $slug = $this->slugger->slug((string)($item['name'] ?? ''), $id);

        return [
            'id' => $id,
            'name' => (string)($item['name'] ?? ''),
            'excerpt' => (string)($item['excerpt'] ?? ''),
            'created' => (string)($item['created'] ?? ''),
            'slug' => $slug,
            'url' => $slug,
        ];
    }

    private function toDetailItem(array $item, string $slug): array
    {
        return [
            'slug' => $slug,
            'id' => (int)($item['id'] ?? 0),
            'name' => (string)($item['name'] ?? ''),
            'excerpt' => (string)($item['excerpt'] ?? ''),
            'body' => (string)($item['body'] ?? ''),
            'created' => (string)($item['created'] ?? ''),
        ];
    }
}
