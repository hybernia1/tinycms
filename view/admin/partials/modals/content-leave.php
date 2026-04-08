<div class="modal-overlay" data-content-leave-modal>
    <div class="modal">
        <p><?= htmlspecialchars($t('content.leave_page_confirm', 'Neuložené změny budou ztraceny. Opravdu chcete pokračovat?'), ENT_QUOTES, 'UTF-8') ?></p>
        <div class="modal-actions">
            <button class="btn btn-light" type="button" data-content-leave-cancel><?= htmlspecialchars($t('common.cancel', 'Cancel'), ENT_QUOTES, 'UTF-8') ?></button>
            <button class="btn btn-primary" type="button" data-content-leave-confirm><?= htmlspecialchars($t('common.confirm', 'Confirm'), ENT_QUOTES, 'UTF-8') ?></button>
        </div>
    </div>
</div>
