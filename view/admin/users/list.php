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

<div class="d-flex gap-2 align-center mb-3">
    <select name="action" id="bulk-action-select" disabled>
        <option value="">Hromadné akce</option>
        <option value="suspend">Suspendovat</option>
        <option value="unsuspend">Odsuspendovat</option>
        <option value="delete">Smazat</option>
    </select>
    <button class="btn btn-light" id="bulk-apply" type="button" disabled>Použít</button>
</div>

<form id="bulk-action-form" method="post" action="<?= htmlspecialchars($url('admin/users/bulk-action'), ENT_QUOTES, 'UTF-8') ?>">
    <input type="hidden" name="ids" value="">
    <input type="hidden" name="action" id="bulk-action-value" value="">
</form>

<div class="card p-4">
        <div class="text-muted mb-3">Celkem: <?= $total ?></div>
        <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th><input type="checkbox" data-bulk-toggle></th>
                    <th>Uživatel</th><th>Role</th><th>Akce</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($users as $row):
                $id = (int)($row['ID'] ?? 0);
                $isAdmin = (string)($row['role'] ?? '') === 'admin';
                $isSuspended = (int)($row['suspend'] ?? 0) === 1;
            ?>
                <tr>
                    <td><?php if (!$isAdmin): ?><input type="checkbox" value="<?= $id ?>" data-bulk-item><?php endif; ?></td>
                    <td>
                        <a href="<?= htmlspecialchars($url('admin/users/edit?id=' . $id), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)($row['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></a>
                        <div class="text-muted"><?= htmlspecialchars((string)($row['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                    </td>
                    <td><?= htmlspecialchars((string)($row['role'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                        <?php if (!$isAdmin): ?>
                        <form method="post" action="<?= htmlspecialchars($url('admin/users/suspend-toggle'), ENT_QUOTES, 'UTF-8') ?>" class="inline-form">
                            <input type="hidden" name="id" value="<?= $id ?>">
                            <input type="hidden" name="mode" value="<?= $isSuspended ? 'unsuspend' : 'suspend' ?>">
                            <button class="btn btn-light" type="submit"><?= $isSuspended ? 'Odsuspendovat' : 'Suspendovat' ?></button>
                        </form>
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
        </div>

        <div class="d-flex justify-between align-center mt-4">
            <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a class="pagination-link<?= $i === $page ? ' active' : '' ?>" href="<?= htmlspecialchars($url('admin/users?page=' . $i . '&per_page=' . $perPage), ENT_QUOTES, 'UTF-8') ?>"><?= $i ?></a>
                <?php endfor; ?>
            </div>
            <?php else: ?>
            <div></div>
            <?php endif; ?>

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
</div>

<div class="modal-overlay" data-modal>
    <div class="modal">
        <p data-modal-text>Skutečně smazat?</p>
        <div class="modal-actions">
            <button class="btn btn-light" type="button" data-modal-close>Zrušit</button>
            <button class="btn btn-primary" type="button" data-modal-confirm data-form-id="">Potvrdit</button>
        </div>
    </div>
</div>
