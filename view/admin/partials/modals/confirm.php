<?php
$config = is_array($modal ?? null) ? $modal : [];
$__modalId = (string)($config['id'] ?? ($modalId ?? ''));
$__modalAttributes = is_array($config['attributes'] ?? null)
    ? $config['attributes']
    : (is_array($modalAttributes ?? null) ? $modalAttributes : ['data-modal' => null]);
$__message = (string)($config['message'] ?? ($message ?? ''));
$__messageAttributes = is_array($config['message_attributes'] ?? null)
    ? $config['message_attributes']
    : (is_array($messageAttributes ?? null) ? $messageAttributes : []);
$__cancelLabel = (string)($config['cancel_label'] ?? ($cancelLabel ?? $t('common.cancel', 'Cancel')));
$__confirmLabel = (string)($config['confirm_label'] ?? ($confirmLabel ?? $t('common.confirm', 'Confirm')));
$__cancelClass = trim((string)($config['cancel_class'] ?? ($cancelClass ?? 'btn btn-light')));
$__confirmClass = trim((string)($config['confirm_class'] ?? ($confirmClass ?? 'btn btn-primary')));
$__cancelAttributes = is_array($config['cancel_attributes'] ?? null)
    ? $config['cancel_attributes']
    : (is_array($cancelAttributes ?? null) ? $cancelAttributes : ['type' => 'button', 'data-modal-close' => null]);
$__confirmAttributes = is_array($config['confirm_attributes'] ?? null)
    ? $config['confirm_attributes']
    : (is_array($confirmAttributes ?? null) ? $confirmAttributes : ['type' => 'button', 'data-modal-confirm' => null]);

$renderAttrs = static function (array $attrs): string {
    $chunks = [];
    foreach ($attrs as $name => $value) {
        $attr = htmlspecialchars((string)$name, ENT_QUOTES, 'UTF-8');
        if ($value === null) {
            $chunks[] = $attr;
            continue;
        }
        $chunks[] = $attr . '="' . htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8') . '"';
    }
    return implode(' ', $chunks);
};
?>
<div class="modal-overlay"<?= $__modalId !== '' ? ' id="' . htmlspecialchars($__modalId, ENT_QUOTES, 'UTF-8') . '"' : '' ?> <?= $renderAttrs($__modalAttributes) ?>>
    <div class="modal">
        <p<?= $__messageAttributes === [] ? '' : ' ' . $renderAttrs($__messageAttributes) ?>><?= htmlspecialchars($__message, ENT_QUOTES, 'UTF-8') ?></p>
        <div class="modal-actions">
            <button class="<?= htmlspecialchars($__cancelClass, ENT_QUOTES, 'UTF-8') ?>" <?= $renderAttrs($__cancelAttributes) ?>><?= htmlspecialchars($__cancelLabel, ENT_QUOTES, 'UTF-8') ?></button>
            <button class="<?= htmlspecialchars($__confirmClass, ENT_QUOTES, 'UTF-8') ?>" <?= $renderAttrs($__confirmAttributes) ?>><?= htmlspecialchars($__confirmLabel, ENT_QUOTES, 'UTF-8') ?></button>
        </div>
    </div>
</div>
