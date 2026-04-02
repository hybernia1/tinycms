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
<div class="card p-4">
    <form method="get" class="d-flex gap-2 align-center mb-3">
        <label class="m-0">Na stránku</label>
        <select name="per_page">
            <?php foreach ($allowedPerPage as $option): ?>
                <option value="<?= (int)$option ?>" <?= $perPage === (int)$option ? 'selected' : '' ?>><?= (int)$option ?></option>
            <?php endforeach; ?>
        </select>
        <input type="hidden" name="page" value="1">
        <button class="btn btn-light" type="submit">Použít</button>
        <span class="text-muted">Celkem: <?= $total ?></span>
    </form>

    <table class="w-100">
        <thead>
            <tr>
                <th>ID</th><th>Jméno</th><th>Email</th><th>Role</th><th>Suspend</th><th>Akce</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($users as $row): ?>
            <tr>
                <td><?= (int)($row['ID'] ?? 0) ?></td>
                <td><?= htmlspecialchars((string)($row['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars((string)($row['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars((string)($row['role'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= (int)($row['suspend'] ?? 0) ?></td>
                <td><a class="btn btn-light" href="<?= htmlspecialchars($url('admin/users/edit?id=' . (int)($row['ID'] ?? 0)), ENT_QUOTES, 'UTF-8') ?>">Edit</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <?php if ($totalPages > 1): ?>
    <div class="pagination mt-4">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a class="pagination-link<?= $i === $page ? ' active' : '' ?>" href="<?= htmlspecialchars($url('admin/users?page=' . $i . '&per_page=' . $perPage), ENT_QUOTES, 'UTF-8') ?>"><?= $i ?></a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
</div>
