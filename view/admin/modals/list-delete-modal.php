<div class="modal-overlay" data-<?= htmlspecialchars($listName, ENT_QUOTES, 'UTF-8') ?>-delete-modal>
    <div class="modal">
        <p><?= htmlspecialchars($deleteConfirmText, ENT_QUOTES, 'UTF-8') ?></p>
        <div class="modal-actions">
            <button class="btn btn-light" type="button" data-<?= htmlspecialchars($listName, ENT_QUOTES, 'UTF-8') ?>-delete-cancel><?= htmlspecialchars($t('common.cancel'), ENT_QUOTES, 'UTF-8') ?></button>
            <button class="btn btn-primary" type="button" data-<?= htmlspecialchars($listName, ENT_QUOTES, 'UTF-8') ?>-delete-confirm><?= htmlspecialchars($t('common.confirm'), ENT_QUOTES, 'UTF-8') ?></button>
        </div>
    </div>
</div>
