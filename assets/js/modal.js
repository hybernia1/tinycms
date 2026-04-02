document.addEventListener('click', function (event) {
    const trigger = event.target.closest('[data-modal-open]');
    const modal = document.querySelector('[data-modal]');

    if (trigger && modal) {
        event.preventDefault();

        const mode = trigger.getAttribute('data-modal-mode') || 'single';
        const type = trigger.getAttribute('data-type') || 'záznam';
        let count = parseInt(trigger.getAttribute('data-count') || '1', 10);
        let formId = trigger.getAttribute('data-form-id') || '';

        if (mode === 'bulk') {
            const checked = Array.from(document.querySelectorAll('[data-bulk-item]:checked'));
            count = checked.length;

            if (count === 0) {
                return;
            }

            const bulkField = document.querySelector('[name="ids"]');
            if (bulkField) {
                bulkField.value = checked.map((el) => el.value).join(',');
            }
        }

        const message = mode === 'bulk'
            ? `Skutečně smazat ${count} ${type}?`
            : `Skutečně smazat tento ${type}?`;

        const text = modal.querySelector('[data-modal-text]');
        const confirm = modal.querySelector('[data-modal-confirm]');

        if (text) {
            text.textContent = message;
        }

        if (confirm) {
            confirm.setAttribute('data-form-id', formId);
        }

        modal.classList.add('open');
        return;
    }

    const close = event.target.closest('[data-modal-close]');
    if (close && modal) {
        modal.classList.remove('open');
        return;
    }

    const confirm = event.target.closest('[data-modal-confirm]');
    if (confirm && modal) {
        const targetFormId = confirm.getAttribute('data-form-id') || '';
        const form = targetFormId ? document.getElementById(targetFormId) : null;

        if (form) {
            form.submit();
        }

        modal.classList.remove('open');
    }
});


document.addEventListener('change', function (event) {
    const toggle = event.target.closest('[data-bulk-toggle]');

    if (!toggle) {
        return;
    }

    document.querySelectorAll('[data-bulk-item]').forEach(function (item) {
        item.checked = toggle.checked;
    });
});
