<?php
declare(strict_types=1);

namespace App\Controller\Admin\Content;

use App\Controller\Admin\BaseController;
use App\Service\Feature\AuthService;
use App\Service\Feature\ContentService;
use App\Service\Feature\TermService;
use App\Service\Feature\UserService;
use App\Service\Support\CsrfService;
use App\Service\Support\FlashService;
use App\Service\Support\I18n;
use App\View\PageView;

abstract class BaseContentController extends BaseController
{
    protected const FORM_STATE_KEY = 'admin_content_form_state';

    public function __construct(
        protected PageView $pages,
        AuthService $authService,
        protected ContentService $content,
        protected UserService $users,
        protected TermService $terms,
        FlashService $flash,
        CsrfService $csrf
    ) {
        parent::__construct($authService, $flash, $csrf);
    }

    protected function resolveListQuery(): array
    {
        [$page, $perPage, $query] = $this->resolvePaginationQuery();
        $availableStatuses = $this->content->statuses();
        $status = $this->resolveStatusFilter($availableStatuses);

        return [$page, $perPage, $status, $query, $availableStatuses];
    }

    protected function mapListItem(array $row): array
    {
        $author = (int)($row['author'] ?? 0);

        return [
            'id' => (int)($row['id'] ?? 0),
            'name' => (string)($row['name'] ?? ''),
            'status' => (string)($row['status'] ?? 'draft'),
            'excerpt' => (string)($row['excerpt'] ?? ''),
            'author' => $author,
            'author_name' => (string)($row['author_name'] ?? ''),
            'can_edit' => $this->canManageByAuthor($row),
            'can_delete' => $this->canManageByAuthor($row),
            'created' => (string)($row['created'] ?? ''),
            'updated' => (string)($row['updated'] ?? ''),
            'created_label' => $this->formatDateTime((string)($row['created'] ?? '')),
            'updated_label' => $this->formatDateTime((string)($row['updated'] ?? '')),
            'thumbnail' => (int)($row['thumbnail'] ?? 0),
        ];
    }

    protected function resolveSelectedTerms(array $item, ?int $contentId): array
    {
        $raw = trim((string)($item['terms'] ?? ''));
        if ($raw !== '') {
            return $this->normalizeTermNames($raw);
        }

        if ($contentId === null || $contentId <= 0) {
            return [];
        }

        $existing = $this->terms->listForContent($contentId);
        if ($existing === []) {
            return [];
        }

        $terms = [];
        foreach ($existing as $term) {
            $name = trim((string)($term['name'] ?? ''));
            if ($name !== '') {
                $terms[] = $name;
            }
        }

        return array_values(array_unique($terms));
    }

    protected function normalizeTermNames(string $rawTerms): array
    {
        if (trim($rawTerms) === '') {
            return [];
        }

        $parts = array_map('trim', explode(',', $rawTerms));
        $parts = array_filter($parts, static fn(string $value): bool => $value !== '');

        return array_values(array_unique($parts));
    }

    protected function resolveAutosavePayload(array $input, int $authorId, int $id = 0): array
    {
        $payload = $this->applyEditorAuthor($input, $authorId);
        $status = trim((string)($payload['status'] ?? 'draft'));
        if (!in_array($status, $this->content->statuses(), true)) {
            $status = 'draft';
        }

        $payload['status'] = $status;
        if ($status === 'published') {
            $payload['created'] = trim((string)($payload['created'] ?? ''));
            if ($payload['created'] === '') {
                $payload['created'] = date('Y-m-d H:i:s');
            }
        }

        $payload['name'] = trim((string)($payload['name'] ?? ''));
        if ($payload['name'] === '') {
            $payload['name'] = $this->resolveAutosaveDraftName($id);
        }

        return $payload;
    }

    protected function resolveAutosaveDraftName(int $id): string
    {
        return $id > 0 ? I18n::t('content.draft_name_existing') . ' #' . $id : I18n::t('content.draft_name_new');
    }

    protected function isValidExternalUrl(string $url): bool
    {
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        $scheme = strtolower((string)parse_url($url, PHP_URL_SCHEME));
        return in_array($scheme, ['http', 'https'], true);
    }

    protected function fetchRemoteTitle(string $url): string
    {
        $html = $this->fetchRemoteHtml($url);
        if ($html === '') {
            return '';
        }

        if (!preg_match('/<title[^>]*>(.*?)<\/title>/is', $html, $matches)) {
            return '';
        }

        return $this->sanitizeRemoteTitle((string)($matches[1] ?? ''));
    }

    protected function fetchRemoteHtml(string $url): string
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 4,
                'user_agent' => 'TinyCMS/1.0',
                'ignore_errors' => true,
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
            ],
        ]);

        set_error_handler(static fn(): bool => true);
        $html = file_get_contents($url, false, $context, 0, 200000);
        restore_error_handler();

        return is_string($html) ? $html : '';
    }

    protected function sanitizeRemoteTitle(string $value): string
    {
        $decoded = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $normalized = preg_replace('/\s+/u', ' ', trim($decoded)) ?? '';

        return mb_substr($normalized, 0, 150, 'UTF-8');
    }
}
