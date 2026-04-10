<?php
$modalId = (string)($modalId ?? '');
$modalAttrs = is_array($modalAttrs ?? null) ? $modalAttrs : [];
$modalText = (string)($modalText ?? '');
$modalHasTextData = (bool)($modalHasTextData ?? false);
$modalCloseAttrs = is_array($modalCloseAttrs ?? null) ? $modalCloseAttrs : ['data-modal-close' => null];
$modalConfirmAttrs = is_array($modalConfirmAttrs ?? null) ? $modalConfirmAttrs : ['data-modal-confirm' => null];
$modalCloseLabel = (string)($modalCloseLabel ?? $t('common.cancel'));
$modalConfirmLabel = (string)($modalConfirmLabel ?? $t('common.confirm'));

if ($modalId !== '') {
    $modalAttrs['id'] = $modalId;
}
if (!array_key_exists('data-modal', $modalAttrs)) {
    $modalAttrs['data-modal'] = null;
}
?>
<div class="modal-overlay"
<?php foreach ($modalAttrs as $attr => $value): ?>
    <?= htmlspecialchars((string)$attr, ENT_QUOTES, 'UTF-8') ?><?= $value === null ? '' : '="' . htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8') . '"' ?>
<?php endforeach; ?>
>
    <div class="modal">
        <p<?= $modalHasTextData ? ' data-modal-text' : '' ?>><?= htmlspecialchars($modalText, ENT_QUOTES, 'UTF-8') ?></p>
        <div class="modal-actions">
            <button class="btn btn-light" type="button"
            <?php foreach ($modalCloseAttrs as $attr => $value): ?>
                <?= htmlspecialchars((string)$attr, ENT_QUOTES, 'UTF-8') ?><?= $value === null ? '' : '="' . htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8') . '"' ?>
            <?php endforeach; ?>
            ><?= htmlspecialchars($modalCloseLabel, ENT_QUOTES, 'UTF-8') ?></button>
            <button class="btn btn-primary" type="button"
            <?php foreach ($modalConfirmAttrs as $attr => $value): ?>
                <?= htmlspecialchars((string)$attr, ENT_QUOTES, 'UTF-8') ?><?= $value === null ? '' : '="' . htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8') . '"' ?>
            <?php endforeach; ?>
            ><?= htmlspecialchars($modalConfirmLabel, ENT_QUOTES, 'UTF-8') ?></button>
        </div>
    </div>
</div>
