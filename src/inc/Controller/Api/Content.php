<?php
declare(strict_types=1);

namespace App\Controller\Api;

use App\Controller\Admin\Admin;

use App\Service\Application\Auth;
use App\Service\Application\Content as ContentService;
use App\Service\Application\Term as TermService;
use App\Service\Support\Csrf;
use App\Service\Support\Flash;
use App\Service\Support\I18n;
use App\View\AdminView;

final class Content extends Admin
{
    public function __construct(
        Auth $authService,
        private ContentService $content,
        private TermService $terms,
        Flash $flash,
        Csrf $csrf,
        private AdminView $adminView
    ) {
        parent::__construct($authService, $flash, $csrf);
    }

    public function listApiV1(): void
    {
        if (!$this->guardApiAdmin()) {
            return;
        }

        [$page, $perPage, $status, $query, $availableStatuses] = $this->resolveContentListQuery($this->content->statuses());
        $publicOnly = (string)($_GET['public'] ?? '') === '1';
        $pagination = $publicOnly
            ? $this->content->paginatePublic($page, $perPage, $query)
            : $this->content->paginate($page, $perPage, $status, $query);
        $items = array_map([$this, 'mapListItem'], (array)($pagination['data'] ?? []));
        $statusCounts = $this->content->statusCounts($availableStatuses);

        if (!$publicOnly && $this->wantsHtmlResponse('list')) {
            $this->adminView->adminContentListFragment($pagination, $status, $query, $availableStatuses, $statusCounts);
            return;
        }

        $this->apiOk($items, $this->buildListMeta($pagination, $perPage, $status, $query, $statusCounts));
    }

    public function deleteApiV1(int $id): void
    {
        if (!$this->guardApiAdminCsrf()) {
            return;
        }

        if (!$this->requireEntity($this->content->find($id), 'NOT_FOUND', I18n::t('content.not_found'))) {
            return;
        }

        $action = $this->content->deleteByStatus($id);
        if ($action === null) {
            $this->apiError('DELETE_FAILED', I18n::t('content.delete_failed'));
            return;
        }

        $this->apiOk([
            'id' => $id,
            'action' => $action,
            'message' => $action === 'soft_deleted' ? I18n::t('content.moved_to_trash') : I18n::t('content.deleted'),
            'redirect' => $this->buildPath('admin/content'),
        ]);
    }

    public function restoreApiV1(int $id): void
    {
        if (!$this->guardApiAdminCsrf()) {
            return;
        }

        if (!$this->requireEntity($this->content->find($id), 'NOT_FOUND', I18n::t('content.not_found'))) {
            return;
        }

        if (!$this->content->restore($id)) {
            $this->apiError('RESTORE_FAILED', I18n::t('content.restore_failed'));
            return;
        }

        $this->apiOk([
            'id' => $id,
            'status' => ContentService::STATUS_DRAFT,
            'message' => I18n::t('content.restored'),
        ]);
    }

    public function addApiV1(): void
    {
        if (!$this->guardApiAdminCsrf()) {
            return;
        }

        $authorId = (int)($this->authService->auth()->id() ?? 0);
        $result = $this->content->save($_POST, $authorId);

        if (($result['success'] ?? false) === true) {
            $newId = (int)($result['id'] ?? 0);
            if ($newId > 0) {
                $this->terms->syncContentTerms($newId, (string)($_POST['terms'] ?? ''));
            }
            $this->apiOk([
                'redirect' => $newId > 0 ? $this->buildEditPath('admin/content', $newId) : $this->buildPath('admin/content'),
                'message' => I18n::t('content.created'),
            ]);
            return;
        }

        $this->apiError('SAVE_FAILED', I18n::t('content.save_failed'), 422, [
            'errors' => $result['errors'] ?? [],
        ]);
    }

    public function editApiV1(int $id): void
    {
        if (!$this->guardApiAdminCsrf()) {
            return;
        }

        if (!$this->requireEntity($this->content->find($id), 'NOT_FOUND', I18n::t('content.not_found'))) {
            return;
        }

        $authorId = (int)($this->authService->auth()->id() ?? 0);
        $result = $this->content->save($_POST, $authorId, $id);

        if (($result['success'] ?? false) === true) {
            $this->terms->syncContentTerms($id, (string)($_POST['terms'] ?? ''));
            $this->apiOk([
                'message' => I18n::t('content.updated'),
            ]);
            return;
        }

        $this->apiError('UPDATE_FAILED', I18n::t('content.update_failed'), 422, [
            'errors' => $result['errors'] ?? [],
        ]);
    }

    public function statusApiV1(int $id): void
    {
        if (!$this->guardApiAdminCsrf()) {
            return;
        }

        $mode = (string)($_POST['mode'] ?? 'draft');

        $item = $this->content->find($id);
        if (!$this->requireEntity($item, 'NOT_FOUND', I18n::t('content.not_found'))) {
            return;
        }

        if ((string)($item['status'] ?? '') === ContentService::STATUS_TRASH) {
            $this->apiError('INVALID_STATUS', I18n::t('content.status_change_forbidden_in_trash'));
            return;
        }

        if ($mode === 'publish') {
            if (!$this->content->setStatus($id, ContentService::STATUS_PUBLISHED)) {
                $this->apiError('PUBLISH_FAILED', I18n::t('content.publish_failed'));
                return;
            }

            $this->apiOk([
                'id' => $id,
                'status' => ContentService::STATUS_PUBLISHED,
                'message' => I18n::t('content.published'),
            ]);
            return;
        }

        if (!$this->content->setStatus($id, 'draft')) {
            $this->apiError('DRAFT_FAILED', I18n::t('content.draft_failed'));
            return;
        }

        $this->apiOk([
            'id' => $id,
            'status' => 'draft',
            'message' => I18n::t('content.switched_to_draft'),
        ]);
    }

    public function draftInitApiV1(): void
    {
        if (!$this->guardApiAdminCsrf()) {
            return;
        }

        $authorId = (int)($this->authService->auth()->id() ?? 0);
        $payload = [
            'name' => $this->resolveAutosaveDraftName(),
            'status' => 'draft',
            'type' => ContentService::TYPE_ARTICLE,
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
            $this->apiError('INVALID_ID', I18n::t('content.draft_invalid_id'), 400);
            return;
        }

        $this->apiOk([
            'id' => $id,
            'created_new' => true,
            'message' => I18n::t('content.created'),
        ]);
    }

    public function autosaveApiV1(): void
    {
        if (!$this->guardApiAdminCsrf()) {
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
            if (!$this->requireEntity($this->content->find($id), 'NOT_FOUND', I18n::t('content.not_found'))) {
                return;
            }
        }

        $authorId = (int)($this->authService->auth()->id() ?? 0);
        $payload = $this->resolveAutosavePayload($_POST, $authorId);
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

    public function linkTitleApiV1(): void
    {
        if (!$this->guardApiAdmin()) {
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

        if (preg_match('/<meta[^>]*(?:property|name)=["\']og:title["\'][^>]*>/i', $html, $metaTag) === 1) {
            if (preg_match('/content=["\']([^"\']+)["\']/i', $metaTag[0], $metaContent) === 1) {
                return $this->sanitizeRemoteTitle($metaContent[1]);
            }
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
                    CURLOPT_USERAGENT => $this->userAgent(),
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
                'header' => "User-Agent: {$this->userAgent()}\r\n",
            ],
        ]);
        $result = @file_get_contents($url, false, $context);
        return is_string($result) ? mb_substr($result, 0, 120000) : '';
    }

    private function userAgent(): string
    {
        $version = defined('APP_VERSION') ? (string)APP_VERSION : '0.9.0';
        return 'TinyCMS/' . $version;
    }

    private function sanitizeRemoteTitle(string $value): string
    {
        $clean = trim(html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        return preg_replace('/\s+/', ' ', $clean) ?? '';
    }

    private function resolveAutosavePayload(array $input, int $authorId): array
    {
        $name = trim((string)($input['name'] ?? ''));
        if ($name === '') {
            $name = $this->resolveAutosaveDraftName();
        }

        $author = trim((string)($input['author'] ?? ''));
        if ($author === '' && $authorId > 0) {
            $author = (string)$authorId;
        }

        return [
            'name' => $name,
            'status' => trim((string)($input['status'] ?? 'draft')) ?: 'draft',
            'type' => trim((string)($input['type'] ?? ContentService::TYPE_ARTICLE)) ?: ContentService::TYPE_ARTICLE,
            'excerpt' => (string)($input['excerpt'] ?? ''),
            'body' => (string)($input['body'] ?? ''),
            'author' => $author,
            'created' => (string)($input['created'] ?? ''),
            'comments_enabled' => (string)($input['comments_enabled'] ?? '1'),
        ];
    }

    private function resolveAutosaveDraftName(): string
    {
        return I18n::t('content.autosave_draft_name');
    }

    private function mapListItem(array $row): array
    {
        $createdAt = (string)($row['created'] ?? '');
        $createdStamp = $createdAt !== '' ? strtotime($createdAt) : false;
        $status = (string)($row['status'] ?? 'draft');
        return [
            'id' => (int)($row['id'] ?? 0),
            'name' => (string)($row['name'] ?? ''),
            'can_edit' => true,
            'can_delete' => true,
            'can_restore' => $status === ContentService::STATUS_TRASH,
            'author_name' => (string)($row['author_name'] ?? '—'),
            'status' => $status,
            'created' => $createdAt,
            'created_label' => $this->formatDateTime($createdAt),
            'is_planned' => $status === ContentService::STATUS_PUBLISHED && $createdStamp !== false && $createdStamp > time(),
        ];
    }

}
