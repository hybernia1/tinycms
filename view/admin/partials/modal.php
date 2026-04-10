<?php
$modalId = (string)($modalId ?? '');
$modalAttrs = is_array($modalAttrs ?? null) ? $modalAttrs : [];
$modalText = (string)($modalText ?? '');
$modalTextAttrs = is_array($modalTextAttrs ?? null) ? $modalTextAttrs : [];
$modalActions = is_array($modalActions ?? null) ? $modalActions : [];
?>
<div class="modal-overlay" data-modal<?= $modalId !== '' ? ' id="' . htmlspecialchars($modalId, ENT_QUOTES, 'UTF-8') . '"' : '' ?>
<?php foreach ($modalAttrs as $attr => $value): ?>
 <?= htmlspecialchars((string)$attr, ENT_QUOTES, 'UTF-8') ?><?= $value === null ? '' : '="' . htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8') . '"' ?>
<?php endforeach; ?>
>
    <div class="modal">
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
