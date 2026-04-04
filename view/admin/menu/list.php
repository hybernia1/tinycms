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
                        <form method="post" action="<?= htmlspecialchars($url('admin/menu/delete'), ENT_QUOTES, 'UTF-8') ?>" onsubmit="return confirm('Opravdu smazat položku navigace?');">
                            <?= $csrfField() ?>
                            <input type="hidden" name="id" value="<?= $id ?>">
                            <button class="btn btn-light btn-icon" type="submit" aria-label="Smazat položku navigace" title="Smazat položku navigace">
                                <?= $icon('delete') ?>
                                <span class="sr-only">Smazat položku navigace</span>
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
