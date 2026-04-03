<div class="card p-5">
    <nav class="filter-nav mb-4">
        <?php foreach ($groups as $groupKey => $group): ?>
            <a class="filter-link<?= $activeGroup === $groupKey ? ' active' : '' ?>" href="<?= $e($url('admin/settings?group=' . urlencode((string)$groupKey))) ?>">
                <?= $e((string)($group['label'] ?? $groupKey)) ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <form method="post" action="<?= $e($url('admin/settings')) ?>">
        <?= $csrfField() ?>
        <input type="hidden" name="group" value="<?= $e((string)$activeGroup) ?>">

        <?php $active = $groups[$activeGroup] ?? null; ?>
        <?php if (is_array($active)): ?>
            <?php foreach (($active['fields'] ?? []) as $fieldKey => $field):
                $fieldType = (string)($field['type'] ?? 'text');
                $fieldValue = (string)($values[$activeGroup][$fieldKey] ?? '');
                $isDateCustomField = $fieldKey === 'dateformat_custom';
                $isTimeCustomField = $fieldKey === 'timeformat_custom';
                $dateMode = (string)($values[$activeGroup]['dateformat_mode'] ?? '');
                $timeMode = (string)($values[$activeGroup]['timeformat_mode'] ?? '');
                if (($isDateCustomField && $dateMode !== 'custom') || ($isTimeCustomField && $timeMode !== 'custom')) {
                    continue;
                }
            ?>
                <div class="mb-3">
                    <label>
                        <?php if ($fieldKey === 'dateformat_custom'): ?>
                            Vlastní formát data
                        <?php elseif ($fieldKey === 'timeformat_custom'): ?>
                            Vlastní formát času
                        <?php else: ?>
                            <?= $e((string)($field['label'] ?? $fieldKey)) ?>
                        <?php endif; ?>
                    </label>
                    <?php if ($fieldType === 'textarea'): ?>
                        <textarea name="settings[<?= $e((string)$activeGroup) ?>][<?= $e((string)$fieldKey) ?>]" rows="4"><?= $e($fieldValue) ?></textarea>
                    <?php elseif ($fieldType === 'select'): ?>
                        <select name="settings[<?= $e((string)$activeGroup) ?>][<?= $e((string)$fieldKey) ?>]">
                            <?php foreach (($field['options'] ?? []) as $optionValue => $optionLabel): ?>
                                <option value="<?= $e((string)$optionValue) ?>" <?= $fieldValue === (string)$optionValue ? 'selected' : '' ?>><?= $e((string)$optionLabel) ?></option>
                            <?php endforeach; ?>
                        </select>
                    <?php else: ?>
                        <input type="text" name="settings[<?= $e((string)$activeGroup) ?>][<?= $e((string)$fieldKey) ?>]" value="<?= $e($fieldValue) ?>">
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <button class="btn btn-primary" type="submit">Uložit nastavení</button>
    </form>
</div>
