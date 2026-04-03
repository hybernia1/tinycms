<div class="card p-5">
    <nav class="filter-nav mb-4">
        <?php foreach ($groups as $groupKey => $group): ?>
            <a class="filter-link<?= $activeGroup === $groupKey ? ' active' : '' ?>" href="<?= $escape($url('admin/settings?group=' . urlencode((string)$groupKey))) ?>">
                <?= $escape((string)($group['label'] ?? $groupKey)) ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <form method="post" action="<?= $escape($url('admin/settings')) ?>">
        <?= $csrfField() ?>
        <input type="hidden" name="group" value="<?= $escape((string)$activeGroup) ?>">

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
                            <?= $escape((string)($field['label'] ?? $fieldKey)) ?>
                        <?php endif; ?>
                    </label>
                    <?php if ($fieldType === 'textarea'): ?>
                        <textarea name="settings[<?= $escape((string)$activeGroup) ?>][<?= $escape((string)$fieldKey) ?>]" rows="4"><?= $escape($fieldValue) ?></textarea>
                    <?php elseif ($fieldType === 'select'): ?>
                        <select name="settings[<?= $escape((string)$activeGroup) ?>][<?= $escape((string)$fieldKey) ?>]">
                            <?php foreach (($field['options'] ?? []) as $optionValue => $optionLabel): ?>
                                <option value="<?= $escape((string)$optionValue) ?>" <?= $fieldValue === (string)$optionValue ? 'selected' : '' ?>><?= $escape((string)$optionLabel) ?></option>
                            <?php endforeach; ?>
                        </select>
                    <?php else: ?>
                        <input type="text" name="settings[<?= $escape((string)$activeGroup) ?>][<?= $escape((string)$fieldKey) ?>]" value="<?= $escape($fieldValue) ?>">
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <button class="btn btn-primary" type="submit">Uložit nastavení</button>
    </form>
</div>
