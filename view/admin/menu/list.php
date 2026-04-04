<?php
$items = $items ?? [];
$byParent = [];

foreach ($items as $item) {
    $parentId = (int)($item['parent_id'] ?? 0);
    $byParent[$parentId][] = $item;
}

$renderTree = static function (int $parentId, callable $renderTree) use (&$byParent, $url, $icon, $csrfField): string {
    $children = $byParent[$parentId] ?? [];
    if ($children === []) {
        return '';
    }

    usort($children, static fn(array $a, array $b): int => [(int)($a['position'] ?? 0), (int)($a['id'] ?? 0)] <=> [(int)($b['position'] ?? 0), (int)($b['id'] ?? 0)]);

    $html = '<ul class="menu-tree-level" data-menu-level="' . $parentId . '">';
    foreach ($children as $row) {
        $id = (int)($row['id'] ?? 0);
        $name = htmlspecialchars((string)($row['name'] ?? ''), ENT_QUOTES, 'UTF-8');
        $urlText = htmlspecialchars((string)($row['url'] ?? '—'), ENT_QUOTES, 'UTF-8');
        $contentText = htmlspecialchars((string)($row['content_name'] ?? '—'), ENT_QUOTES, 'UTF-8');
        $editUrl = htmlspecialchars($url('admin/menu/edit?id=' . $id), ENT_QUOTES, 'UTF-8');
        $deleteAction = htmlspecialchars($url('admin/menu/delete'), ENT_QUOTES, 'UTF-8');

        $html .= '<li class="menu-tree-item" data-menu-item data-menu-id="' . $id . '">';
        $html .= '<div class="menu-tree-card" draggable="true" data-menu-drag>';
        $html .= '<div class="menu-tree-main">';
        $html .= '<span class="menu-tree-handle" aria-hidden="true">⋮⋮</span>';
        $html .= '<a href="' . $editUrl . '"><strong>' . $name . '</strong></a>';
        $html .= '<small class="text-muted">URL: ' . $urlText . '</small>';
        $html .= '<small class="text-muted">Obsah: ' . $contentText . '</small>';
        $html .= '</div>';
        $html .= '<div class="menu-tree-actions">';
        $html .= '<form method="post" action="' . $deleteAction . '" id="menu-delete-form-' . $id . '" class="d-none">';
        $html .= $csrfField();
        $html .= '<input type="hidden" name="id" value="' . $id . '">';
        $html .= '</form>';
        $html .= '<button class="btn btn-light btn-icon" type="button" data-modal-open data-modal-target="#menu-delete-modal" data-type="položku navigace" data-form-id="menu-delete-form-' . $id . '" aria-label="Smazat položku navigace" title="Smazat položku navigace">';
        $html .= $icon('delete');
        $html .= '<span class="sr-only">Smazat položku navigace</span>';
        $html .= '</button>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= $renderTree($id, $renderTree);
        $html .= '</li>';
    }

    $html .= '</ul>';
    return $html;
};
?>
<div class="menu-tree-layout" data-menu-tree>
    <div class="card p-3">
        <div class="d-flex justify-between align-center mb-3">
            <strong>Struktura navigace</strong>
            <span class="text-muted">Táhni položku pod jinou nebo mezi ostatní.</span>
        </div>
        <form method="post" action="<?= htmlspecialchars($url('admin/menu/reorder'), ENT_QUOTES, 'UTF-8') ?>" data-menu-tree-form>
            <?= $csrfField() ?>
            <input type="hidden" name="tree" value="" data-menu-tree-input>
            <div class="menu-tree-root" data-menu-root>
                <?= $renderTree(0, $renderTree) ?>
            </div>
            <div class="d-flex gap-2 mt-3">
                <button class="btn btn-primary" type="submit">Uložit pořadí</button>
                <a class="btn btn-light" href="<?= htmlspecialchars($url('admin/menu/add'), ENT_QUOTES, 'UTF-8') ?>">Přidat položku</a>
            </div>
        </form>
    </div>
</div>

<div class="modal-overlay" data-modal id="menu-delete-modal">
    <div class="modal">
        <p data-modal-text>Skutečně smazat tuto položku navigace?</p>
        <div class="modal-actions">
            <button class="btn btn-light" type="button" data-modal-close>Zrušit</button>
            <button class="btn btn-primary" type="button" data-modal-confirm>Potvrdit</button>
        </div>
    </div>
</div>
