<?php
$config = isset($confirmModal) && is_array($confirmModal) ? $confirmModal : [];
$modalId = isset($config['id']) ? (string)$config['id'] : '';
$text = isset($config['text']) ? (string)$config['text'] : '';
$overlayAttrs = isset($config['overlay_attrs']) && is_array($config['overlay_attrs']) ? $config['overlay_attrs'] : ['data-modal' => true];
$cancelAttrs = isset($config['cancel_attrs']) && is_array($config['cancel_attrs']) ? $config['cancel_attrs'] : ['data-modal-close' => true];
$confirmAttrs = isset($config['confirm_attrs']) && is_array($config['confirm_attrs']) ? $config['confirm_attrs'] : ['data-modal-confirm' => true];

if ($modalId !== '') {
    $overlayAttrs['id'] = $modalId;
}

$renderAttrs = static function (array $attrs): string {
    $parts = [];
    foreach ($attrs as $name => $value) {
        if (!is_string($name) || $name === '') {
            continue;
        }
        if ($value === true) {
            $parts[] = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
            continue;
        }
        if ($value === false || $value === null) {
            continue;
        }
        $parts[] = htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '="' . htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8') . '"';
    }
    return $parts === [] ? '' : ' ' . implode(' ', $parts);
};
?>
<div class="modal-overlay"<?= $renderAttrs($overlayAttrs) ?>>
    <div class="modal">
        <p data-modal-text><?= htmlspecialchars($text, ENT_QUOTES, 'UTF-8') ?></p>
        <div class="modal-actions">
            <button class="btn btn-light" type="button"<?= $renderAttrs($cancelAttrs) ?>><?= htmlspecialchars($t('common.cancel'), ENT_QUOTES, 'UTF-8') ?></button>
            <button class="btn btn-primary" type="button"<?= $renderAttrs($confirmAttrs) ?>><?= htmlspecialchars($t('common.confirm'), ENT_QUOTES, 'UTF-8') ?></button>
        </div>
    </div>
</div>
