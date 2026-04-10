<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\Feature\AuthService;
use App\Service\Feature\ContentService;
use App\Service\Feature\TermService;
use App\Service\Support\CsrfService;
use App\Service\Support\FlashService;
use App\Service\Feature\UserService;
use App\Service\Support\I18n;
use App\Service\Support\PaginationConfig;
use App\View\PageView;

final class AdminContentController extends BaseAdminController
{
    private const FORM_STATE_KEY = 'admin_content_form_state';

    public function __construct(
        private PageView $pages,
        AuthService $authService,
        private ContentService $content,
        private UserService $users,
        private TermService $terms,
        FlashService $flash,
        CsrfService $csrf
    ) {
        parent::__construct($authService, $flash, $csrf);
    }

    public function list(callable $redirect): void
    {
        if (!$this->guardAdmin($redirect, false)) {
            return;
        }

        [$page, $perPage, $status, $query, $availableStatuses] = $this->resolveListQuery();

        $pagination = $this->content->paginate($page, $perPage, $status, $query);
        $statusCounts = $this->content->statusCounts($availableStatuses);
        $this->pages->adminContentList($pagination, PaginationConfig::allowed(), $status, $query, $availableStatuses, $statusCounts);
    }

    public function listApiV1(callable $redirect): void
    {
        if (!$this->guardAdmin($redirect, false)) {
            return;
        }

        [$page, $perPage, $status, $query, $availableStatuses] = $this->resolveListQuery();
        $pagination = $this->content->paginate($page, $perPage, $status, $query);
        $items = array_map([$this, 'mapListItem'], (array)($pagination['data'] ?? []));
        $statusCounts = $this->content->statusCounts($availableStatuses);

        $this->apiOk($items, $this->buildListMeta($pagination, $perPage, $status, $query, $statusCounts));
    }

    public function deleteApiV1(callable $redirect, int $id): void
    {
        if (!$this->guardAdminCsrf($redirect, 'admin/content', I18n::t('common.invalid_csrf'), false)) {
            return;
        }

        if ($id <= 0) {
            $this->apiError('INVALID_ID', I18n::t('content.invalid_id'));
            return;
        }

        $item = $this->content->find($id);
        if ($item === null) {
            $this->apiError('NOT_FOUND', I18n::t('content.not_found'), 404);
            return;
        }

        if (!$this->canManageByAuthor($item)) {
            $this->apiError('FORBIDDEN', I18n::t('admin.access_denied'), 403);
            return;
        }

        if (!$this->content->delete($id)) {
            $this->apiError('DELETE_FAILED', I18n::t('content.delete_failed'));
            return;
        }

        $this->apiOk(['id' => $id]);
    }

    public function deleteSubmit(callable $redirect): void
    {
        if (!$this->guardAdminCsrf($redirect, 'admin/content', I18n::t('common.invalid_csrf'), false)) {
            return;
        }

        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            $this->flash->add('error', I18n::t('content.invalid_id'));
            $redirect('admin/content');
            return;
        }

        $item = $this->content->find($id);
        if ($item === null) {
            $this->flash->add('error', I18n::t('content.not_found'));
            $redirect('admin/content');
            return;
        }

        if (!$this->canManageByAuthor($item)) {
            $this->flash->add('error', I18n::t('admin.access_denied'));
            $redirect('admin/content');
            return;
        }

        if (!$this->content->delete($id)) {
            $this->flash->add('error', I18n::t('content.delete_failed'));
            $redirect($this->buildEditPath('admin/content', $id));
            return;
        }

        $this->flash->add('success', I18n::t('content.deleted'));
        $redirect('admin/content');
    }

    public function addForm(callable $redirect): void
    {
        if (!$this->guardAdmin($redirect, false)) {
            return;
        }

        $fallback = ['id' => null, 'name' => '', 'status' => 'draft', 'excerpt' => '', 'body' => '', 'created' => date('Y-m-d H:i:s'), 'updated' => null];
        $fallback['author'] = (int)($this->authService->auth()->id() ?? 0);
        $state = $this->consumeFormState(self::FORM_STATE_KEY, 'add', null);
        $statuses = $this->content->statuses();
        $item = $state['data'] ?? $fallback;
        $selectedTerms = $this->resolveSelectedTerms($item, null);
        $this->pages->adminContentForm('add', $item, $state['errors'] ?? [], $statuses, $this->users->authorOptions(), $selectedTerms);
    }

    public function addSubmit(callable $redirect): void
    {
        if (!$this->guardAdminCsrf($redirect, 'admin/content', I18n::t('common.invalid_csrf'), false)) {
            return;
        }

        $authorId = (int)($this->authService->auth()->id() ?? 0);
        $result = $this->content->save($this->applyEditorAuthor($_POST, $authorId), $authorId);

        if (($result['success'] ?? false) === true) {
            $newId = (int)($result['id'] ?? 0);
            if ($newId > 0) {
                $this->terms->syncContentTerms($newId, (string)($_POST['terms'] ?? ''));
            }
            $this->flash->add('success', I18n::t('content.created'));
            $redirect($newId > 0 ? $this->buildEditPath('admin/content', $newId) : 'admin/content');
            return;
        }

        $this->flash->add('error', I18n::t('content.save_failed'));
        $this->storeFormState(self::FORM_STATE_KEY, 'add', null, $_POST, $result['errors'] ?? []);
        $redirect('admin/content/add');
    }

    public function editForm(callable $redirect): void
    {
        if (!$this->guardAdmin($redirect, false)) {
            return;
        }

        $id = (int)($_GET['id'] ?? 0);
        $item = $this->content->find($id);

        if ($item === null) {
            $this->flash->add('info', I18n::t('content.not_found'));
            $redirect('admin/content');
            return;
        }

        if (!$this->canManageByAuthor($item)) {
            $this->flash->add('error', I18n::t('admin.access_denied'));
            $redirect('admin/content');
            return;
        }

        $state = $this->consumeFormState(self::FORM_STATE_KEY, 'edit', $id);
        $statuses = $this->content->statuses();
        $formItem = $state['data'] ?? $item;
        $selectedTerms = $this->resolveSelectedTerms($formItem, $id);
        $this->pages->adminContentForm('edit', $formItem, $state['errors'] ?? [], $statuses, $this->users->authorOptions(), $selectedTerms);
    }

    public function editSubmit(callable $redirect): void
    {
        if (!$this->guardAdminCsrf($redirect, 'admin/content', I18n::t('common.invalid_csrf'), false)) {
            return;
        }

        $id = (int)($_GET['id'] ?? 0);

        if ($id <= 0) {
            $this->flash->add('error', I18n::t('content.invalid_id'));
            $redirect('admin/content');
            return;
        }

        $item = $this->content->find($id);
        if ($item === null) {
            $this->flash->add('error', I18n::t('content.not_found'));
            $redirect('admin/content');
            return;
        }

        if (!$this->canManageByAuthor($item)) {
            $this->flash->add('error', I18n::t('admin.access_denied'));
            $redirect('admin/content');
            return;
        }

        $authorId = (int)($this->authService->auth()->id() ?? 0);
        $result = $this->content->save($this->applyEditorAuthor($_POST, $authorId), $authorId, $id);

        if (($result['success'] ?? false) === true) {
            $this->terms->syncContentTerms($id, (string)($_POST['terms'] ?? ''));
            $this->flash->add('success', I18n::t('content.updated'));
            $redirect($this->buildEditPath('admin/content', $id));
            return;
        }

        $this->flash->add('error', I18n::t('content.update_failed'));
        $this->storeFormState(self::FORM_STATE_KEY, 'edit', $id, array_merge($_POST, ['id' => $id]), $result['errors'] ?? []);
        $redirect('admin/content/edit?id=' . $id);
    }

    public function statusApiV1(callable $redirect, int $id): void
    {
        if (!$this->guardAdminCsrf($redirect, 'admin/content', I18n::t('common.invalid_csrf'), false)) {
            return;
        }

        $mode = (string)($_POST['mode'] ?? 'draft');
        if ($id <= 0) {
            $this->apiError('INVALID_ID', I18n::t('content.invalid_id'));
            return;
        }

        $item = $this->content->find($id);
        if ($item === null) {
            $this->apiError('NOT_FOUND', I18n::t('content.not_found'), 404);
            return;
        }

        if (!$this->canManageByAuthor($item)) {
            $this->apiError('FORBIDDEN', I18n::t('admin.access_denied'), 403);
            return;
        }

        if ($mode === 'publish') {
            if (!$this->content->setStatus($id, 'published')) {
                $this->apiError('PUBLISH_FAILED', I18n::t('content.publish_failed'));
                return;
            }

            $this->apiOk(['id' => $id, 'status' => 'published']);
            return;
        }

        if (!$this->content->setStatus($id, 'draft')) {
            $this->apiError('DRAFT_FAILED', I18n::t('content.draft_failed'));
            return;
        }

        $this->apiOk(['id' => $id, 'status' => 'draft']);
    }

    public function draftInitApiV1(callable $redirect): void
    {
        if (!$this->guardAdminCsrf($redirect, 'admin/content', I18n::t('common.invalid_csrf'), false)) {
            return;
        }

        $authorId = (int)($this->authService->auth()->id() ?? 0);
        $payload = [
            'name' => I18n::t('content.untitled'),
            'status' => 'draft',
            'excerpt' => '',
            'body' => '',
            'author' => $authorId > 0 ? (string)$authorId : '',
            'created' => '',
        ];
        $result = $this->content->save($payload, $authorId);
        if (($result['success'] ?? false) !== true) {
            $this->apiError('CREATE_FAILED', I18n::t('content.draft_create_failed'));
            return;
        }

        $id = (int)($result['id'] ?? 0);
        if ($id <= 0) {
            $this->apiError('INVALID_ID', I18n::t('content.draft_invalid_id'));
            return;
        }

        $payload['name'] = $this->resolveAutosaveDraftName($id);
        $renameResult = $this->content->save($payload, $authorId, $id);
        if (($renameResult['success'] ?? false) !== true) {
            $this->apiError('CREATE_FAILED', I18n::t('content.draft_create_failed'));
            return;
        }

        $this->apiOk(['id' => $id, 'created_new' => true]);
    }

    public function autosaveApiV1(callable $redirect): void
    {
        if (!$this->guardAdminCsrf($redirect, 'admin/content', I18n::t('common.invalid_csrf'), false)) {
            return;
        }

        $id = (int)($_POST['id'] ?? 0);
        $name = trim((string)($_POST['name'] ?? ''));
        $body = trim((string)($_POST['body'] ?? ''));
        if ($id <= 0 && $name === '' && $body === '') {
            $this->apiOk(['id' => 0, 'skipped' => true, 'reason' => 'empty']);
            return;
        }

        if ($id > 0) {
            $item = $this->content->find($id);
            if ($item === null) {
                $this->apiError('NOT_FOUND', I18n::t('content.not_found'), 404);
                return;
            }

            if (!$this->canManageByAuthor($item)) {
                $this->apiError('FORBIDDEN', I18n::t('admin.access_denied'), 403);
                return;
            }
        }

        $authorId = (int)($this->authService->auth()->id() ?? 0);
        $payload = $this->resolveAutosavePayload($_POST, $authorId, $id);
        $isCreate = $id <= 0;
        $result = $this->content->save($payload, $authorId, $isCreate ? null : $id);

        if (($result['success'] ?? false) !== true) {
            $this->apiError('SAVE_FAILED', I18n::t('content.autosave_failed'), 422, ['errors' => $result['errors'] ?? []]);
            return;
        }

        $savedId = (int)($result['id'] ?? 0);
        if ($savedId > 0 && isset($_POST['terms'])) {
            $this->terms->syncContentTerms($savedId, (string)$_POST['terms']);
        }

        $this->apiOk([
            'id' => $savedId,
            'created_new' => $isCreate,
            'updated' => date('Y-m-d H:i:s'),
        ]);
    }

    public function linkTitleApiV1(callable $redirect): void
    {
        if (!$this->guardAdmin($redirect, false)) {
            return;
        }

        $url = trim((string)($_GET['url'] ?? ''));
        if ($url === '' || !$this->isValidExternalUrl($url)) {
            $this->apiError('INVALID_URL', I18n::t('common.invalid_data'));
            return;
        }

        $title = $this->fetchRemoteTitle($url);
        if ($title === '') {
            $this->apiError('TITLE_NOT_FOUND', I18n::t('content.link_title_not_found'), 404);
            return;
        }

        $this->apiOk(['title' => $title]);
    }

    private function isValidExternalUrl(string $url): bool
    {
        if (!preg_match('#^https?://#i', $url)) {
            return false;
        }

        $parts = parse_url($url);
        $host = strtolower((string)($parts['host'] ?? ''));
        if ($host === '' || $host === 'localhost') {
            return false;
        }

        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    private function fetchRemoteTitle(string $url): string
    {
        $html = $this->fetchRemoteHtml($url);
        if ($html === '') {
            return '';
        }

        if (preg_match('/<meta[^>]+property=["\']og:title["\'][^>]+content=["\']([^"\']+)["\']/i', $html, $match) === 1) {
            return $this->sanitizeRemoteTitle($match[1]);
        }

        if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $html, $match) === 1) {
            return $this->sanitizeRemoteTitle($match[1]);
        }

        return '';
    }

    private function fetchRemoteHtml(string $url): string
    {
        if (function_exists('curl_init')) {
            $curl = curl_init($url);
            if ($curl !== false) {
                curl_setopt_array($curl, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_MAXREDIRS => 3,
                    CURLOPT_TIMEOUT => 4,
                    CURLOPT_CONNECTTIMEOUT => 2,
                    CURLOPT_USERAGENT => 'TinyCMS/1.0',
                ]);
                $result = curl_exec($curl);
                curl_close($curl);
                if (is_string($result) && $result !== '') {
                    return mb_substr($result, 0, 120000);
                }
            }
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 4,
                'header' => "User-Agent: TinyCMS/1.0\r\n",
            ],
        ]);
        $result = @file_get_contents($url, false, $context);
        return is_string($result) ? mb_substr($result, 0, 120000) : '';
    }

    private function sanitizeRemoteTitle(string $value): string
    {
        $clean = trim(html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        return preg_replace('/\s+/', ' ', $clean) ?? '';
    }

    private function resolveAutosavePayload(array $input, int $authorId, int $id = 0): array
    {
        $name = trim((string)($input['name'] ?? ''));
        if ($name === '') {
            $name = $id > 0 ? $this->resolveAutosaveDraftName($id) : I18n::t('content.untitled');
        }

        $author = trim((string)($input['author'] ?? ''));
        if ($this->isEditor()) {
            $author = $authorId > 0 ? (string)$authorId : '';
        } elseif ($author === '' && $authorId > 0) {
            $author = (string)$authorId;
        }

        return [
            'name' => $name,
            'status' => trim((string)($input['status'] ?? 'draft')) ?: 'draft',
            'excerpt' => (string)($input['excerpt'] ?? ''),
            'body' => (string)($input['body'] ?? ''),
            'author' => $author,
            'created' => (string)($input['created'] ?? ''),
        ];
    }

    private function resolveAutosaveDraftName(int $id): string
    {
        return sprintf(I18n::t('content.autosave_draft_name'), $id);
    }

    private function resolveListQuery(): array
    {
        [$page, $perPage, $query] = $this->resolvePaginationQuery();
        $availableStatuses = $this->content->statuses();
        $status = $this->resolveStatusFilter(array_merge(['all'], $availableStatuses));

        return [$page, $perPage, $status, $query, $availableStatuses];
    }

    private function mapListItem(array $row): array
    {
        $createdAt = (string)($row['created'] ?? '');
        $createdStamp = $createdAt !== '' ? strtotime($createdAt) : false;
        return [
            'id' => (int)($row['id'] ?? 0),
            'name' => (string)($row['name'] ?? ''),
            'can_edit' => $this->canManageByAuthor($row),
            'can_delete' => $this->canManageByAuthor($row),
            'author_name' => (string)($row['author_name'] ?? '—'),
            'status' => (string)($row['status'] ?? 'draft'),
            'created' => $createdAt,
            'created_label' => $this->formatDateTime($createdAt),
            'is_planned' => $createdStamp !== false && $createdStamp > time(),
        ];
    }

    private function resolveSelectedTerms(array $item, ?int $contentId): array
    {
        if (array_key_exists('terms', $item)) {
            return $this->normalizeTermNames((string)$item['terms']);
        }

        if ($contentId !== null && $contentId > 0) {
            return $this->terms->namesByContent($contentId);
        }

        return [];
    }

    private function normalizeTermNames(string $rawTerms): array
    {
        $parts = preg_split('/[\n,]+/', $rawTerms) ?: [];
        $terms = [];

        foreach ($parts as $part) {
            $value = trim((string)$part);
            if ($value === '') {
                continue;
            }
            $key = mb_strtolower($value);
            $terms[$key] = mb_substr($value, 0, 255);
        }

        return array_values($terms);
    }

}
