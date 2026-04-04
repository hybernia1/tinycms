<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\Feature\AuthService;
use App\Service\Feature\MenuService;
use App\Service\Support\CsrfService;
use App\Service\Support\FlashService;
use App\View\PageView;

final class AdminMenuController extends BaseAdminController
{
    private const FORM_STATE_KEY = 'admin_menu_form_state';

    public function __construct(
        private PageView $pages,
        AuthService $authService,
        private MenuService $menu,
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

        $editId = (int)($_GET['edit'] ?? 0);
        $mode = $editId > 0 ? 'edit' : 'add';
        $fallback = ['id' => null, 'parent_id' => null, 'content_id' => null, 'name' => '', 'url' => '', 'position' => 0];
        $baseItem = $mode === 'edit' ? ($this->menu->find($editId) ?? $fallback) : $fallback;
        $state = $this->consumeFormState(self::FORM_STATE_KEY, $mode, $mode === 'edit' ? $editId : null);
        $item = $state['data'] ?? $baseItem;

        if ($mode === 'edit' && (int)($baseItem['id'] ?? 0) <= 0) {
            $this->flash->add('info', 'Položka navigace nenalezena.');
            $redirect('admin/menu');
            return;
        }

        $currentId = $mode === 'edit' ? (int)($item['id'] ?? 0) : null;
        $this->pages->adminMenuList(
            $this->menu->all(),
            $mode,
            $item,
            $state['errors'] ?? [],
            $this->menu->options($currentId),
            $this->menu->contentOptions(),
        );
    }

    public function addForm(callable $redirect): void
    {
        $redirect('admin/menu');
    }

    public function addSubmit(callable $redirect): void
    {
        if (
            !$this->guardAdmin($redirect, false)
            || !$this->guardCsrf($redirect, 'admin/menu', 'Neplatný CSRF token.')
        ) {
            return;
        }

        $result = $this->menu->save($_POST);
        if (($result['success'] ?? false) === true) {
            $this->flash->add('success', 'Položka navigace vytvořena.');
            $redirect('admin/menu');
            return;
        }

        $this->flash->add('error', 'Nepodařilo se uložit položku navigace.');
        $this->storeFormState(self::FORM_STATE_KEY, 'add', null, $_POST, $result['errors'] ?? []);
        $redirect('admin/menu');
    }

    public function editForm(callable $redirect): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $redirect($id > 0 ? 'admin/menu?edit=' . $id : 'admin/menu');
    }

    public function editSubmit(callable $redirect): void
    {
        if (
            !$this->guardAdmin($redirect, false)
            || !$this->guardCsrf($redirect, 'admin/menu', 'Neplatný CSRF token.')
        ) {
            return;
        }

        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            $this->flash->add('error', 'Neplatné ID položky navigace.');
            $redirect('admin/menu');
            return;
        }

        $result = $this->menu->save($_POST, $id);
        if (($result['success'] ?? false) === true) {
            $this->flash->add('success', 'Položka navigace upravena.');
            $redirect('admin/menu?edit=' . $id);
            return;
        }

        $this->flash->add('error', 'Nepodařilo se upravit položku navigace.');
        $this->storeFormState(self::FORM_STATE_KEY, 'edit', $id, array_merge($_POST, ['id' => $id]), $result['errors'] ?? []);
        $redirect('admin/menu?edit=' . $id);
    }

    public function reorderSubmit(callable $redirect): void
    {
        if (
            !$this->guardAdmin($redirect, false)
            || !$this->guardCsrf($redirect, 'admin/menu', 'Neplatný CSRF token.')
        ) {
            return;
        }

        $raw = trim((string)($_POST['tree'] ?? ''));
        $decoded = $raw !== '' ? json_decode($raw, true) : null;
        $items = is_array($decoded) ? $decoded : [];
        $ok = $this->menu->reorder($items);

        $this->flash->add($ok ? 'success' : 'error', $ok ? 'Pořadí navigace uloženo.' : 'Pořadí navigace se nepodařilo uložit.');
        $redirect('admin/menu');
    }

    public function deleteSubmit(callable $redirect): void
    {
        if (
            !$this->guardAdmin($redirect, false)
            || !$this->guardCsrf($redirect, 'admin/menu', 'Neplatný CSRF token.')
        ) {
            return;
        }

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            $this->flash->add('error', 'Neplatné ID položky navigace.');
            $redirect('admin/menu');
            return;
        }

        $ok = $this->menu->delete($id);
        $this->flash->add($ok ? 'success' : 'error', $ok ? 'Položka navigace smazána.' : 'Položku navigace se nepodařilo smazat.');
        $redirect('admin/menu');
    }
}
