<div class="modal-overlay" data-modal id="content-delete-modal">
    <div class="modal">
        <p data-modal-text><?= htmlspecialchars($t('content.delete_confirm'), ENT_QUOTES, 'UTF-8') ?></p>
        <div class="modal-actions">
            <button class="btn btn-light" type="button" data-modal-close><?= htmlspecialchars($t('common.cancel'), ENT_QUOTES, 'UTF-8') ?></button>
            <button class="btn btn-primary" type="button" data-modal-confirm data-form-id="content-delete-form"><?= htmlspecialchars($t('common.confirm'), ENT_QUOTES, 'UTF-8') ?></button>
        </div>
    </div>
</div>
