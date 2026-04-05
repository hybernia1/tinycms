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
                    <?php $fieldLabel = (string)$t($labelKey, (string)$fieldKey); ?>
                    <?php $selectLabel = $t('settings.select_media', 'Výběr') . ' ' . strtolower($fieldLabel); ?>
                    <?php $changeLabel = $t('settings.change_media', 'Změnit') . ' ' . strtolower($fieldLabel); ?>
                    <?php $removeLabel = $t('settings.remove_media', 'Odstranit') . ' ' . strtolower($fieldLabel); ?>
                    <?php $fileName = $fieldValue !== '' ? basename($fieldValue) : ''; ?>
                    <div class="settings-media-field">
                        <div class="settings-media-preview<?= $previewUrl === '' ? ' d-none' : '' ?>" data-settings-media-preview="<?= htmlspecialchars((string)$fieldKey, ENT_QUOTES, 'UTF-8') ?>">
                            <div class="settings-media-preview-image">
                                <?php if ($previewUrl !== ''): ?>
                                    <img src="<?= htmlspecialchars($previewUrl, ENT_QUOTES, 'UTF-8') ?>" alt="">
                                <?php endif; ?>
                            </div>
                            <div class="settings-media-preview-name" data-settings-media-preview-name="<?= htmlspecialchars((string)$fieldKey, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($fileName, ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                        <button
                            class="btn btn-light settings-media-open"
                            type="button"
                            data-media-library-open
                            data-media-library-mode="settings"
                            data-settings-media-target="<?= htmlspecialchars((string)$fieldKey, ENT_QUOTES, 'UTF-8') ?>"
                            data-settings-media-input="[data-settings-media-input='<?= htmlspecialchars((string)$fieldKey, ENT_QUOTES, 'UTF-8') ?>']"
                            data-settings-label-select="<?= htmlspecialchars($selectLabel, ENT_QUOTES, 'UTF-8') ?>"
                            data-settings-label-change="<?= htmlspecialchars($changeLabel, ENT_QUOTES, 'UTF-8') ?>"
                            data-media-library-endpoint="<?= htmlspecialchars($url('admin/api/v1/settings/media'), ENT_QUOTES, 'UTF-8') ?>"
                            data-media-library-upload-endpoint="<?= htmlspecialchars($url('admin/api/v1/settings/media/upload'), ENT_QUOTES, 'UTF-8') ?>"
                            data-media-base-url="<?= htmlspecialchars($url(''), ENT_QUOTES, 'UTF-8') ?>"
                            data-current-media-id="0"
                        >
                            <?= htmlspecialchars($previewUrl === '' ? $selectLabel : $changeLabel, ENT_QUOTES, 'UTF-8') ?>
                        </button>
                        <button class="btn btn-light<?= $previewUrl === '' ? ' d-none' : '' ?>" type="button" data-settings-media-clear="<?= htmlspecialchars((string)$fieldKey, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($removeLabel, ENT_QUOTES, 'UTF-8') ?></button>
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
<div class="media-library-modal" data-media-library-modal>
    <div class="media-library-modal-dialog">
        <div class="media-library-modal-header">
            <strong>Media library</strong>
            <button class="btn btn-light btn-icon" type="button" data-media-library-close aria-label="<?= htmlspecialchars($t('common.close', 'Close'), ENT_QUOTES, 'UTF-8') ?>">
                <?= $icon('cancel') ?>
            </button>
        </div>
        <div class="media-library-modal-layout">
            <div class="media-library-detail">
                <div class="media-library-detail-preview" data-media-library-detail-preview></div>
                <div class="media-library-detail-meta">
                    <div>
                        <label><?= htmlspecialchars($t('common.name', 'Name'), ENT_QUOTES, 'UTF-8') ?></label>
                        <div class="d-flex gap-2">
                            <input type="text" value="" data-media-library-detail-name-input>
                            <button class="btn btn-light d-none" type="button" data-media-library-rename disabled><?= htmlspecialchars($t('common.save', 'Save'), ENT_QUOTES, 'UTF-8') ?></button>
                        </div>
                    </div>
                    <div><strong><?= htmlspecialchars($t('content.path', 'Path'), ENT_QUOTES, 'UTF-8') ?>:</strong> <span data-media-library-detail-path>—</span></div>
                    <div><strong><?= htmlspecialchars($t('common.created', 'Created'), ENT_QUOTES, 'UTF-8') ?>:</strong> <span data-media-library-detail-created>—</span></div>
                </div>
                <small class="text-muted" data-media-library-status></small>
                <div class="d-flex gap-2">
                    <button class="btn btn-primary" type="button" data-media-library-choose disabled><?= htmlspecialchars($t('content.choose', 'Choose'), ENT_QUOTES, 'UTF-8') ?></button>
                    <button class="btn btn-danger d-none" type="button" data-media-library-delete-open disabled><?= htmlspecialchars($t('common.delete', 'Delete'), ENT_QUOTES, 'UTF-8') ?></button>
                </div>
            </div>
            <div class="media-library-list">
                <form class="media-library-search" data-media-library-search>
                    <div class="search-field">
                        <input class="search-input" type="search" name="q" placeholder="<?= htmlspecialchars($t('content.search_image', 'Search image'), ENT_QUOTES, 'UTF-8') ?>">
                        <span class="search-field-icon" aria-hidden="true"><?= $icon('search') ?></span>
                    </div>
                </form>
                <form class="media-library-upload" method="post" enctype="multipart/form-data" action="<?= htmlspecialchars($url('admin/api/v1/settings/media/upload'), ENT_QUOTES, 'UTF-8') ?>" data-media-library-upload-form>
                    <?= $csrfField() ?>
                    <input type="file" name="file" accept=".jpg,.jpeg,.png,.webp,.gif,image/jpeg,image/png,image/webp,image/gif" required>
                    <button class="btn btn-primary" type="submit" data-media-library-upload-button>
                        <span data-media-library-upload-label><?= htmlspecialchars($t('content.upload_new', 'Upload new'), ENT_QUOTES, 'UTF-8') ?></span>
                    </button>
                </form>
                <div class="media-library-grid" data-media-library-grid></div>
                <div class="media-library-pagination">
                    <button class="btn btn-light" type="button" data-media-library-prev><?= htmlspecialchars($t('common.previous', 'Previous'), ENT_QUOTES, 'UTF-8') ?></button>
                    <span data-media-library-page>1 / 1</span>
                    <button class="btn btn-light" type="button" data-media-library-next><?= htmlspecialchars($t('common.next', 'Next'), ENT_QUOTES, 'UTF-8') ?></button>
                </div>
            </div>
        </div>
    </div>
</div>
