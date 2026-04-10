<?php
$modal = is_array($modal ?? null) ? $modal : [];
$id = (string)($modal['id'] ?? '');
$attrs = is_array($modal['attrs'] ?? null) ? $modal['attrs'] : [];
$text = (string)($modal['text'] ?? '');
$hasTextData = (bool)($modal['has_text_data'] ?? false);
$closeAttrs = is_array($modal['close_attrs'] ?? null)
    ? array_merge(['data-modal-close' => null], $modal['close_attrs'])
    : ['data-modal-close' => null];
$confirmAttrs = is_array($modal['confirm_attrs'] ?? null)
    ? array_merge(['data-modal-confirm' => null], $modal['confirm_attrs'])
    : ['data-modal-confirm' => null];
$closeLabel = (string)($modal['close_label'] ?? $t('common.cancel'));
$confirmLabel = (string)($modal['confirm_label'] ?? $t('common.confirm'));

if ($id !== '') {
    $attrs['id'] = $id;
}
if (!array_key_exists('data-modal', $attrs)) {
    $attrs['data-modal'] = null;
}
$titleId = $id !== '' ? $id . '-title' : '';
?>
<div class="modal-overlay"
<?php foreach ($attrs as $attr => $value): ?>
    <?= htmlspecialchars((string)$attr, ENT_QUOTES, 'UTF-8') ?><?= $value === null ? '' : '="' . htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8') . '"' ?>
<?php endforeach; ?>
>
    <div class="modal" role="dialog" aria-modal="true"<?= $titleId !== '' ? ' aria-labelledby="' . htmlspecialchars($titleId, ENT_QUOTES, 'UTF-8') . '"' : '' ?>>
        <p<?= $hasTextData ? ' data-modal-text' : '' ?><?= $titleId !== '' ? ' id="' . htmlspecialchars($titleId, ENT_QUOTES, 'UTF-8') . '"' : '' ?>><?= htmlspecialchars($text, ENT_QUOTES, 'UTF-8') ?></p>
        <div class="modal-actions">
            <button class="btn btn-light" type="button"
            <?php foreach ($closeAttrs as $attr => $value): ?>
                <?= htmlspecialchars((string)$attr, ENT_QUOTES, 'UTF-8') ?><?= $value === null ? '' : '="' . htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8') . '"' ?>
            <?php endforeach; ?>
            ><?= htmlspecialchars($closeLabel, ENT_QUOTES, 'UTF-8') ?></button>
            <button class="btn btn-primary" type="button"
            <?php foreach ($confirmAttrs as $attr => $value): ?>
                <?= htmlspecialchars((string)$attr, ENT_QUOTES, 'UTF-8') ?><?= $value === null ? '' : '="' . htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8') . '"' ?>
            <?php endforeach; ?>
            ><?= htmlspecialchars($confirmLabel, ENT_QUOTES, 'UTF-8') ?></button>
        </div>
    </div>
</div>
