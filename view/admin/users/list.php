<div class="container py-5">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-between align-center mb-4">
                <h1 class="m-0">Uživatelé</h1>
                <a class="btn btn-primary" href="<?= htmlspecialchars($url('admin/users/add'), ENT_QUOTES, 'UTF-8') ?>">Přidat uživatele</a>
            </div>
            <?php if ($status === 'created'): ?><p class="text-success">Uživatel vytvořen.</p><?php endif; ?>
            <?php if ($status === 'updated'): ?><p class="text-success">Uživatel upraven.</p><?php endif; ?>
            <div class="card p-4">
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
            </div>
        </div>
    </div>
</div>
