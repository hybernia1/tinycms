<div class="card p-5">
    <h1 class="m-0 mb-4">Nastavení webu</h1>

    <nav class="filter-nav mb-4">
        <?php foreach ($groups as $groupKey => $group): ?>
            <a class="filter-link<?= $activeGroup === $groupKey ? ' active' : '' ?>" href="<?= htmlspecialchars($url('admin/settings?group=' . urlencode((string)$groupKey)), ENT_QUOTES, 'UTF-8') ?>">
                <?= htmlspecialchars((string)($group['label'] ?? $groupKey), ENT_QUOTES, 'UTF-8') ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <form method="post" action="<?= htmlspecialchars($url('admin/settings'), ENT_QUOTES, 'UTF-8') ?>">
        <?= $csrfField() ?>
        <input type="hidden" name="group" value="<?= htmlspecialchars((string)$activeGroup, ENT_QUOTES, 'UTF-8') ?>">

        <?php $active = $groups[$activeGroup] ?? null; ?>
        <?php if (is_array($active)): ?>
            <?php foreach (($active['fields'] ?? []) as $fieldKey => $field):
                $fieldType = (string)($field['type'] ?? 'text');
                $fieldValue = (string)($values[$activeGroup][$fieldKey] ?? '');
            ?>
                <div class="mb-3">
                    <label><?= htmlspecialchars((string)($field['label'] ?? $fieldKey), ENT_QUOTES, 'UTF-8') ?></label>
                    <?php if ($fieldType === 'textarea'): ?>
                        <textarea name="settings[<?= htmlspecialchars((string)$activeGroup, ENT_QUOTES, 'UTF-8') ?>][<?= htmlspecialchars((string)$fieldKey, ENT_QUOTES, 'UTF-8') ?>]" rows="4"><?= htmlspecialchars($fieldValue, ENT_QUOTES, 'UTF-8') ?></textarea>
                    <?php else: ?>
                        <input type="text" name="settings[<?= htmlspecialchars((string)$activeGroup, ENT_QUOTES, 'UTF-8') ?>][<?= htmlspecialchars((string)$fieldKey, ENT_QUOTES, 'UTF-8') ?>]" value="<?= htmlspecialchars($fieldValue, ENT_QUOTES, 'UTF-8') ?>">
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <button class="btn btn-primary" type="submit">Uložit nastavení</button>
    </form>
</div>
