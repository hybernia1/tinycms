<?php $items = $items ?? []; ?>
<div class="card p-2">
    <div class="table-responsive">
        <table class="table">
            <thead>
            <tr>
                <th>Název</th>
                <th>URL</th>
                <th>Navázaný obsah</th>
                <th>Pozice</th>
                <th class="table-col-actions">Akce</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $row): ?>
                <?php $id = (int)($row['id'] ?? 0); ?>
                <tr>
                    <td>
                        <a href="<?= htmlspecialchars($url('admin/menu/edit?id=' . $id), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)($row['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></a>
                        <?php if (!empty($row['parent_name'])): ?>
                            <div class="text-muted">Rodič: <?= htmlspecialchars((string)$row['parent_name'], ENT_QUOTES, 'UTF-8') ?></div>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars((string)($row['url'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string)($row['content_name'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= (int)($row['position'] ?? 0) ?></td>
                    <td class="table-col-actions">
                        <form method="post" action="<?= htmlspecialchars($url('admin/menu/delete'), ENT_QUOTES, 'UTF-8') ?>" id="menu-delete-form-<?= $id ?>" class="d-none">
                            <?= $csrfField() ?>
                            <input type="hidden" name="id" value="<?= $id ?>">
                        </form>
                        <button
                            class="btn btn-light btn-icon"
                            type="button"
                            data-modal-open
                            data-modal-target="#menu-delete-modal"
                            data-type="položku navigace"
                            data-form-id="menu-delete-form-<?= $id ?>"
                            aria-label="Smazat položku navigace"
                            title="Smazat položku navigace"
                        >
                            <?= $icon('delete') ?>
                            <span class="sr-only">Smazat položku navigace</span>
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
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
