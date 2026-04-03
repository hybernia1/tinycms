(($) => {
    const $doc = $(document);

    const checkedItems = () => $('[data-bulk-item]:checked');

    const syncBulkUi = () => {
        const hasChecked = checkedItems().length > 0;
        $('#bulk-action-select').prop('disabled', !hasChecked);
        $('#bulk-apply').prop('disabled', !hasChecked);
    };

    const openModal = ($trigger) => {
        const $modal = $('[data-modal]').first();
        if (!$modal.length) {
            return;
        }

        const mode = $trigger.data('modal-mode') || 'single';
        const type = $trigger.data('type') || 'záznam';
        const formId = $trigger.data('form-id') || '';
        let count = Number.parseInt(String($trigger.data('count') || '1'), 10);

        if (mode === 'bulk') {
            count = checkedItems().length;
            if (!count) {
                return;
            }
        }

        const text = mode === 'bulk'
            ? `Skutečně smazat ${count} ${type}?`
            : `Skutečně smazat tento ${type}?`;

        $modal.find('[data-modal-text]').text(text);
        $modal.find('[data-modal-confirm]').attr('data-form-id', formId);
        $modal.addClass('open');
    };

    $doc.on('click', '[data-modal-open]', function (event) {
        event.preventDefault();
        openModal($(this));
    });

    $doc.on('click', '[data-modal-close]', () => {
        $('[data-modal]').removeClass('open');
    });

    $doc.on('click', '[data-modal-confirm]', function () {
        const formId = String($(this).attr('data-form-id') || '');
        const form = formId ? document.getElementById(formId) : null;
        if (form) {
            form.submit();
        }
        $('[data-modal]').removeClass('open');
    });

    $doc.on('click', '#bulk-apply', () => {
        const form = document.getElementById('bulk-action-form');
        const actionSelect = document.getElementById('bulk-action-select');

        if (!form || !actionSelect || !actionSelect.value) {
            return;
        }

        const $checked = checkedItems();
        if (!$checked.length) {
            return;
        }

        const idsField = form.querySelector('[name="ids"]');
        const actionField = document.getElementById('bulk-action-value');

        if (idsField) {
            idsField.value = $checked.map((_, el) => el.value).get().join(',');
        }

        if (actionField) {
            actionField.value = actionSelect.value;
        }

        if (actionSelect.value === 'delete') {
            const $trigger = $('<button>', {
                type: 'button',
                'data-modal-open': '1',
                'data-modal-mode': 'bulk',
                'data-type': form.getAttribute('data-bulk-type') || 'záznamů',
                'data-form-id': 'bulk-action-form',
                'data-count': String($checked.length),
            }).hide();

            $(document.body).append($trigger);
            $trigger.trigger('click').remove();
            return;
        }

        form.submit();
    });

    $doc.on('change', '[data-bulk-toggle]', function () {
        $('[data-bulk-item]').prop('checked', this.checked);
        syncBulkUi();
    });

    $doc.on('change', '[data-bulk-item]', syncBulkUi);

    syncBulkUi();
})(jQuery);
