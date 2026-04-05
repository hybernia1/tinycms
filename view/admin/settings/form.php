<div class="card p-5">
    <form method="post" action="<?= htmlspecialchars($url('admin/settings'), ENT_QUOTES, 'UTF-8') ?>">
        <?= $csrfField() ?>

        <?php foreach ($fields as $fieldKey => $field):
            $fieldType = (string)($field['type'] ?? 'text');
            $fieldValue = (string)($values[$fieldKey] ?? '');
            $labelKey = (string)($field['label_key'] ?? ('settings.fields.' . $fieldKey));
        ?>
            <div class="mb-3">
                <label><?= htmlspecialchars($t($labelKey, (string)$fieldKey), ENT_QUOTES, 'UTF-8') ?></label>
                <?php if ($fieldType === 'textarea'): ?>
                    <textarea name="settings[<?= htmlspecialchars((string)$fieldKey, ENT_QUOTES, 'UTF-8') ?>]" rows="4"><?= htmlspecialchars($fieldValue, ENT_QUOTES, 'UTF-8') ?></textarea>
                <?php elseif ($fieldType === 'media'): ?>
                    <?php $previewUrl = $fieldValue !== '' ? $url($fieldValue) : ''; ?>
                    <div class="d-flex align-center gap-2">
                        <button
                            class="content-thumbnail-trigger<?= $previewUrl === '' ? ' empty' : '' ?>"
                            type="button"
                            data-settings-media-open
                            data-settings-media-target="<?= htmlspecialchars((string)$fieldKey, ENT_QUOTES, 'UTF-8') ?>"
                        >
                            <?php if ($previewUrl !== ''): ?>
                                <div class="content-thumbnail-preview">
                                    <img src="<?= htmlspecialchars($previewUrl, ENT_QUOTES, 'UTF-8') ?>" alt="">
                                </div>
                            <?php else: ?>
                                <span><?= htmlspecialchars($t('content.choose_image', 'Choose image'), ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endif; ?>
                        </button>
                        <button class="btn btn-light" type="button" data-settings-media-clear data-settings-media-target="<?= htmlspecialchars((string)$fieldKey, ENT_QUOTES, 'UTF-8') ?>">×</button>
                    </div>
                    <input type="hidden" name="settings[<?= htmlspecialchars((string)$fieldKey, ENT_QUOTES, 'UTF-8') ?>]" value="<?= htmlspecialchars($fieldValue, ENT_QUOTES, 'UTF-8') ?>" data-settings-media-input="<?= htmlspecialchars((string)$fieldKey, ENT_QUOTES, 'UTF-8') ?>">
                <?php elseif ($fieldType === 'select'): ?>
                    <?php $options = (array)($field['options'] ?? []); ?>
                    <select name="settings[<?= htmlspecialchars((string)$fieldKey, ENT_QUOTES, 'UTF-8') ?>]">
                        <?php foreach ($options as $optionValue => $optionLabel): ?>
                            <?php $value = is_string($optionValue) ? trim($optionValue) : trim((string)$optionLabel); ?>
                            <?php if ($value === '') { continue; } ?>
                            <option value="<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?>" <?= $fieldValue === $value ? 'selected' : '' ?>>
                                <?= htmlspecialchars((string)$optionLabel, ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php else: ?>
                    <input type="text" name="settings[<?= htmlspecialchars((string)$fieldKey, ENT_QUOTES, 'UTF-8') ?>]" value="<?= htmlspecialchars($fieldValue, ENT_QUOTES, 'UTF-8') ?>">
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

        <button class="btn btn-primary" type="submit"><?= htmlspecialchars($t('settings.save', 'Save settings'), ENT_QUOTES, 'UTF-8') ?></button>
    </form>
</div>
<div class="media-library-modal" data-settings-media-modal data-endpoint="<?= htmlspecialchars($url('admin/api/v1/settings/media'), ENT_QUOTES, 'UTF-8') ?>" data-upload-endpoint="<?= htmlspecialchars($url('admin/api/v1/settings/media/upload'), ENT_QUOTES, 'UTF-8') ?>" data-base-url="<?= htmlspecialchars($url(''), ENT_QUOTES, 'UTF-8') ?>">
    <div class="media-library-modal-dialog">
        <div class="media-library-modal-header">
            <strong>Media library</strong>
            <button class="btn btn-light btn-icon" type="button" data-settings-media-close aria-label="<?= htmlspecialchars($t('common.close', 'Close'), ENT_QUOTES, 'UTF-8') ?>"><?= $icon('cancel') ?></button>
        </div>
        <div class="p-3 d-flex gap-2">
            <input type="search" class="search-input" placeholder="<?= htmlspecialchars($t('content.search_image', 'Search image'), ENT_QUOTES, 'UTF-8') ?>" data-settings-media-search>
            <form method="post" enctype="multipart/form-data" data-settings-media-upload>
                <?= $csrfField() ?>
                <input type="file" name="file" accept=".jpg,.jpeg,.png,.webp,.gif,image/jpeg,image/png,image/webp,image/gif" required>
                <button class="btn btn-primary" type="submit"><?= htmlspecialchars($t('content.upload_new', 'Upload new'), ENT_QUOTES, 'UTF-8') ?></button>
            </form>
        </div>
        <div class="media-library-grid p-3 pt-0" data-settings-media-grid></div>
    </div>
</div>
