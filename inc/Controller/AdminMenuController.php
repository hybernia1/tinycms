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

        $this->pages->adminMenuList($this->menu->all());
    }

    public function addForm(callable $redirect): void
    {
        if (!$this->guardAdmin($redirect, false)) {
            return;
        }

        $fallback = ['id' => null, 'parent_id' => null, 'content_id' => null, 'name' => '', 'url' => '', 'position' => 0];
        $state = $this->consumeFormState(self::FORM_STATE_KEY, 'add', null);
        $this->pages->adminMenuForm('add', $state['data'] ?? $fallback, $state['errors'] ?? [], $this->menu->options(), $this->menu->contentOptions());
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
            $newId = (int)($result['id'] ?? 0);
            $this->flash->add('success', 'Položka navigace vytvořena.');
            $redirect($newId > 0 ? $this->editPath($newId) : 'admin/menu');
            return;
        }

        $this->flash->add('error', 'Nepodařilo se uložit položku navigace.');
        $this->storeFormState(self::FORM_STATE_KEY, 'add', null, $_POST, $result['errors'] ?? []);
        $redirect('admin/menu/add');
    }

    public function editForm(callable $redirect): void
    {
        if (!$this->guardAdmin($redirect, false)) {
            return;
        }

        $id = (int)($_GET['id'] ?? 0);
        $item = $this->menu->find($id);

        if ($item === null) {
            $this->flash->add('info', 'Položka navigace nenalezena.');
            $redirect('admin/menu');
            return;
        }

        $state = $this->consumeFormState(self::FORM_STATE_KEY, 'edit', $id);
        $this->pages->adminMenuForm('edit', $state['data'] ?? $item, $state['errors'] ?? [], $this->menu->options($id), $this->menu->contentOptions());
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
            $redirect($this->editPath($id));
            return;
        }

        $this->flash->add('error', 'Nepodařilo se upravit položku navigace.');
        $this->storeFormState(self::FORM_STATE_KEY, 'edit', $id, array_merge($_POST, ['id' => $id]), $result['errors'] ?? []);
        $redirect($this->editPath($id));
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

    private function editPath(int $id): string
    {
        return 'admin/menu/edit?id=' . $id;
    }
}
