<?php
$users = $pagination['data'] ?? [];
$page = (int)($pagination['page'] ?? 1);
$perPage = (int)($pagination['per_page'] ?? 10);
$totalPages = (int)($pagination['total_pages'] ?? 1);
$total = (int)($pagination['total'] ?? 0);
?>
<div class="d-flex justify-between align-center mb-4">
    <h1 class="m-0">Uživatelé</h1>
    <a class="btn btn-primary" href="<?= htmlspecialchars($url('admin/users/add'), ENT_QUOTES, 'UTF-8') ?>">Přidat uživatele</a>
</div>

<div class="d-flex justify-between align-center mb-3">
    <div class="d-flex gap-2 align-center">
        <select name="action" id="bulk-action-select" disabled>
            <option value="">Hromadné akce</option>
            <option value="suspend">Suspendovat</option>
            <option value="delete">Smazat</option>
        </select>
        <button class="btn btn-light" id="bulk-apply" type="button" disabled>Použít</button>
    </div>

    <form method="get" class="d-flex gap-2 align-center">
        <select name="per_page">
            <?php foreach ($allowedPerPage as $option): ?>
                <option value="<?= (int)$option ?>" <?= $perPage === (int)$option ? 'selected' : '' ?>><?= (int)$option ?></option>
            <?php endforeach; ?>
        </select>
        <input type="hidden" name="page" value="1">
        <button class="btn btn-light" type="submit">Použít</button>
    </form>
</div>

<form id="bulk-action-form" method="post" action="<?= htmlspecialchars($url('admin/users/bulk-action'), ENT_QUOTES, 'UTF-8') ?>">
    <input type="hidden" name="ids" value="">
    <input type="hidden" name="action" id="bulk-action-value" value="">

    <div class="card p-4">
        <div class="text-muted mb-3">Celkem: <?= $total ?></div>
        <table class="w-100">
            <thead>
                <tr>
                    <th><input type="checkbox" data-bulk-toggle></th>
                    <th>ID</th><th>Jméno</th><th>Email</th><th>Role</th><th>Suspend</th><th>Akce</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($users as $row):
                $id = (int)($row['ID'] ?? 0);
                $isAdmin = (string)($row['role'] ?? '') === 'admin';
            ?>
                <tr>
                    <td><?php if (!$isAdmin): ?><input type="checkbox" value="<?= $id ?>" data-bulk-item><?php endif; ?></td>
                    <td><?= $id ?></td>
                    <td><?= htmlspecialchars((string)($row['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string)($row['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string)($row['role'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= (int)($row['suspend'] ?? 0) ?></td>
                    <td>
                        <a class="btn btn-light" href="<?= htmlspecialchars($url('admin/users/edit?id=' . $id), ENT_QUOTES, 'UTF-8') ?>">Edit</a>
                        <?php if (!$isAdmin): ?>
                        <form id="delete-user-<?= $id ?>" method="post" action="<?= htmlspecialchars($url('admin/users/delete'), ENT_QUOTES, 'UTF-8') ?>" class="inline-form">
                            <input type="hidden" name="id" value="<?= $id ?>">
                            <button class="btn btn-light" type="button" data-modal-open data-modal-mode="single" data-type="uživatele" data-form-id="delete-user-<?= $id ?>">Delete</button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <?php if ($totalPages > 1): ?>
        <div class="pagination mt-4 justify-end">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a class="pagination-link<?= $i === $page ? ' active' : '' ?>" href="<?= htmlspecialchars($url('admin/users?page=' . $i . '&per_page=' . $perPage), ENT_QUOTES, 'UTF-8') ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    </div>
</form>

<div class="modal-overlay" data-modal>
    <div class="modal">
        <p data-modal-text>Skutečně smazat?</p>
        <div class="modal-actions">
            <button class="btn btn-light" type="button" data-modal-close>Zrušit</button>
            <button class="btn btn-primary" type="button" data-modal-confirm data-form-id="">Potvrdit</button>
        </div>
    </div>
</div>
