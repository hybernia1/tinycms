document.addEventListener('click', function (event) {
    const trigger = event.target.closest('[data-modal-open]');
    const modal = document.querySelector('[data-modal]');

    if (trigger && modal) {
        event.preventDefault();

        const mode = trigger.getAttribute('data-modal-mode') || 'single';
        const type = trigger.getAttribute('data-type') || 'záznam';
        const formId = trigger.getAttribute('data-form-id') || '';
        let count = parseInt(trigger.getAttribute('data-count') || '1', 10);

        if (mode === 'bulk') {
            const checked = Array.from(document.querySelectorAll('[data-bulk-item]:checked'));
            count = checked.length;

            if (count === 0) {
                return;
            }
        }

        const text = modal.querySelector('[data-modal-text]');
        const confirm = modal.querySelector('[data-modal-confirm]');

        if (text) {
            text.textContent = mode === 'bulk' ? `Skutečně smazat ${count} ${type}?` : `Skutečně smazat tento ${type}?`;
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
        const form = document.getElementById(confirm.getAttribute('data-form-id') || '');

        if (form) {
            form.submit();
        }

        modal.classList.remove('open');
        return;
    }

    const bulkApply = event.target.closest('#bulk-apply');
    if (bulkApply) {
        const form = document.getElementById('bulk-action-form');
        const actionSelect = document.getElementById('bulk-action-select');

        if (!form || !actionSelect || actionSelect.value === '') {
            return;
        }

        const checked = Array.from(document.querySelectorAll('[data-bulk-item]:checked'));

        if (checked.length === 0) {
            return;
        }

        const idsField = form.querySelector('[name="ids"]');
        const actionField = document.getElementById('bulk-action-value');

        if (idsField) {
            idsField.value = checked.map((el) => el.value).join(',');
        }

        if (actionField) {
            actionField.value = actionSelect.value;
        }

        if (actionSelect.value === 'delete') {
            const fakeTrigger = document.createElement('button');
            fakeTrigger.setAttribute('data-modal-open', '1');
            fakeTrigger.setAttribute('data-modal-mode', 'bulk');
            fakeTrigger.setAttribute('data-type', 'uživatelů');
            fakeTrigger.setAttribute('data-form-id', 'bulk-action-form');
            fakeTrigger.setAttribute('data-count', String(checked.length));
            fakeTrigger.style.display = 'none';
            document.body.appendChild(fakeTrigger);
            fakeTrigger.click();
            fakeTrigger.remove();
            return;
        }

        form.submit();
    }
});

document.addEventListener('change', function (event) {
    const toggle = event.target.closest('[data-bulk-toggle]');

    if (toggle) {
        document.querySelectorAll('[data-bulk-item]').forEach(function (item) {
            item.checked = toggle.checked;
        });
    }

    const checkedCount = document.querySelectorAll('[data-bulk-item]:checked').length;
    const actionSelect = document.getElementById('bulk-action-select');
    const applyButton = document.getElementById('bulk-apply');

    if (actionSelect) {
        actionSelect.disabled = checkedCount === 0;
    }

    if (applyButton) {
        applyButton.disabled = checkedCount === 0;
    }
});
