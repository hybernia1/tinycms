<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\Application\Auth;
use App\Service\Application\Content as ContentService;
use App\Service\Application\Term as TermService;
use App\Service\Application\User as UserService;
use App\Service\Support\Csrf;
use App\Service\Support\Flash;
use App\Service\Support\I18n;
use App\View\AdminView;

final class Content extends Admin
{
    public function __construct(
        private AdminView $pages,
        Auth $authService,
        private ContentService $content,
        private UserService $users,
        private TermService $terms,
        Flash $flash,
        Csrf $csrf
    ) {
        parent::__construct($authService, $flash, $csrf);
    }

    public function list(callable $redirect): void
    {
        if (!$this->guardAdmin($redirect, false)) {
            return;
        }

        [$page, $perPage, $status, $query, $availableStatuses] = $this->resolveContentListQuery($this->content->statuses());

        $pagination = $this->content->paginate($page, $perPage, $status, $query);
        $statusCounts = $this->content->statusCounts($availableStatuses);
        $this->pages->adminContentList($pagination, $status, $query, $availableStatuses, $statusCounts);
    }

    public function addForm(callable $redirect): void
    {
        if (!$this->guardAdmin($redirect, false)) {
            return;
        }

        $fallback = ['id' => null, 'name' => '', 'status' => 'draft', 'type' => ContentService::TYPE_ARTICLE, 'excerpt' => '', 'body' => '', 'created' => date('Y-m-d H:i:s'), 'updated' => null];
        $fallback['author'] = (int)($this->authService->auth()->id() ?? 0);
        $statuses = $this->content->statuses();
        $types = $this->content->types();
        $item = $fallback;
        $selectedTerms = $this->resolveSelectedTerms($item, null);
        $this->pages->adminContentForm('add', $item, [], $statuses, $types, $this->users->authorLabelById((int)($item['author'] ?? 0)), $selectedTerms);
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

        $statuses = $this->content->statuses();
        $types = $this->content->types();
        $itemType = (string)($item['type'] ?? '');
        if ($itemType !== '' && !in_array($itemType, $types, true)) {
            $types[] = $itemType;
        }
        $formItem = $item;
        $selectedTerms = $this->resolveSelectedTerms($formItem, $id);
        $this->pages->adminContentForm('edit', $formItem, [], $statuses, $types, $this->users->authorLabelById((int)($formItem['author'] ?? 0)), $selectedTerms);
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
