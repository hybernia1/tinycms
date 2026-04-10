<?php
$modalConfig = is_array($modal ?? null) ? $modal : [];
$modalId = (string)($modalConfig['id'] ?? '');
$modalAttrs = is_array($modalConfig['attrs'] ?? null) ? $modalConfig['attrs'] : [];
$modalText = (string)($modalConfig['text'] ?? '');
$modalTextAttrs = is_array($modalConfig['text_attrs'] ?? null) ? $modalConfig['text_attrs'] : [];
$modalActions = is_array($modalConfig['actions'] ?? null) ? $modalConfig['actions'] : [];
$modalDialogAttrs = is_array($modalConfig['dialog_attrs'] ?? null) ? $modalConfig['dialog_attrs'] : [];
$dialogAttrs = ['role' => 'dialog', 'aria-modal' => 'true'];
foreach ($modalDialogAttrs as $attr => $value) {
    $dialogAttrs[(string)$attr] = $value;
}
?>
<div class="modal-overlay" data-modal<?= $modalId !== '' ? ' id="' . htmlspecialchars($modalId, ENT_QUOTES, 'UTF-8') . '"' : '' ?>
<?php foreach ($modalAttrs as $attr => $value): ?>
 <?= htmlspecialchars((string)$attr, ENT_QUOTES, 'UTF-8') ?><?= $value === null ? '' : '="' . htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8') . '"' ?>
<?php endforeach; ?>
>
    <div class="modal"<?php foreach ($dialogAttrs as $attr => $value): ?> <?= htmlspecialchars((string)$attr, ENT_QUOTES, 'UTF-8') ?>="<?= htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8') ?>"<?php endforeach; ?>>
        <?php if ($modalText !== ''): ?>
            <p<?php foreach ($modalTextAttrs as $attr => $value): ?> <?= htmlspecialchars((string)$attr, ENT_QUOTES, 'UTF-8') ?><?= $value === null ? '' : '="' . htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8') . '"' ?><?php endforeach; ?>><?= htmlspecialchars($modalText, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
        <div class="modal-actions">
            <?php foreach ($modalActions as $action): ?>
                <?php
                $actionClass = trim((string)($action['class'] ?? 'btn btn-light'));
                $actionLabel = (string)($action['label'] ?? '');
                $actionAttrs = is_array($action['attrs'] ?? null) ? $action['attrs'] : [];
                ?>
                <button class="<?= htmlspecialchars($actionClass, ENT_QUOTES, 'UTF-8') ?>" type="button"<?php foreach ($actionAttrs as $attr => $value): ?> <?= htmlspecialchars((string)$attr, ENT_QUOTES, 'UTF-8') ?><?= $value === null ? '' : '="' . htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8') . '"' ?><?php endforeach; ?>><?= htmlspecialchars($actionLabel, ENT_QUOTES, 'UTF-8') ?></button>
            <?php endforeach; ?>
        </div>
    </div>
</div>
