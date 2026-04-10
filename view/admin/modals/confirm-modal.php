<?php
$confirmModalId = isset($confirmModalId) ? (string)$confirmModalId : '';
$confirmModalDataAttr = isset($confirmModalDataAttr) ? (string)$confirmModalDataAttr : 'data-modal';
$confirmText = isset($confirmText) ? (string)$confirmText : '';
$confirmCancelAttr = isset($confirmCancelAttr) ? (string)$confirmCancelAttr : 'data-modal-close';
$confirmButtonAttr = isset($confirmButtonAttr) ? (string)$confirmButtonAttr : 'data-modal-confirm';
$confirmFormId = isset($confirmFormId) ? (string)$confirmFormId : '';
?>
<div class="modal-overlay" <?= $confirmModalDataAttr ?><?= $confirmModalId !== '' ? ' id="' . htmlspecialchars($confirmModalId, ENT_QUOTES, 'UTF-8') . '"' : '' ?>>
    <div class="modal">
        <p data-modal-text><?= htmlspecialchars($confirmText, ENT_QUOTES, 'UTF-8') ?></p>
        <div class="modal-actions">
            <button class="btn btn-light" type="button" <?= $confirmCancelAttr ?>><?= htmlspecialchars($t('common.cancel'), ENT_QUOTES, 'UTF-8') ?></button>
            <button class="btn btn-primary" type="button" <?= $confirmButtonAttr ?><?= $confirmFormId !== '' ? ' data-form-id="' . htmlspecialchars($confirmFormId, ENT_QUOTES, 'UTF-8') . '"' : '' ?>><?= htmlspecialchars($t('common.confirm'), ENT_QUOTES, 'UTF-8') ?></button>
        </div>
    </div>
</div>
