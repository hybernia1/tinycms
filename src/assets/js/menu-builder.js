(() => {
    const app = window.tinycms = window.tinycms || {};
    const root = document.querySelector('[data-menu-builder]');
    if (!root) {
        return;
    }

    const items = root.querySelector('[data-menu-items]');
    const template = root.querySelector('[data-menu-item-template]');
    const empty = root.querySelector('[data-menu-empty]');
    const addButton = root.querySelector('[data-menu-add-item]');
    const count = root.querySelector('[data-menu-count]');
    const draft = root.querySelector('[data-menu-draft]');
    const t = app.i18n?.t || ((key, fallback) => fallback || '');

    if (!items || !template) {
        return;
    }

    const rows = () => Array.from(items.querySelectorAll('[data-menu-item]'));
    const iconPickers = () => Array.from(root.querySelectorAll('[data-menu-icon-picker]'));
    const iconSvg = app.icons?.icon || (() => '');
    const customSelect = app.ui?.customSelect;

    const setAddOpen = (open) => {
        const form = draft?.querySelector('[data-menu-add-form]');
        const toggle = draft?.querySelector('[data-menu-add-toggle]');
        if (form) {
            form.hidden = !open;
        }
        if (toggle) {
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        }
        draft?.classList.toggle('is-open', open);

        if (open) {
            customSelect?.init(draft);
            draft?.querySelector('[data-menu-draft-label]')?.focus();
        }
    };

    const closeIconPickers = (except = null) => {
        iconPickers().forEach((picker) => {
            if (picker === except) {
                return;
            }

            const options = picker.querySelector('[data-menu-icon-options]');
            const trigger = picker.querySelector('[data-menu-icon-trigger]');
            if (options) {
                options.hidden = true;
            }
            if (trigger) {
                trigger.setAttribute('aria-expanded', 'false');
            }
        });
    };

    const syncIconPreview = (scope) => {
        const input = scope.querySelector('[data-menu-icon-value]');
        const preview = scope.querySelector('[data-menu-icon-preview]');
        if (!input || !preview) {
            return;
        }

        const icon = String(input.value || '').replace(/[^a-z0-9_-]/gi, '');
        preview.innerHTML = icon !== '' ? iconSvg(icon) : '';

        scope.querySelectorAll('[data-menu-icon-option]').forEach((option) => {
            option.classList.toggle('selected', String(option.dataset.icon || '') === icon);
        });
    };

    const setSelectValue = (select, value) => {
        if (!(select instanceof HTMLSelectElement)) {
            return;
        }
        select.value = value;
        select.dispatchEvent(new Event('change', { bubbles: true }));
    };

    const draftValues = () => ({
        label: draft?.querySelector('[data-menu-draft-label]')?.value.trim() || '',
        url: draft?.querySelector('[data-menu-draft-url]')?.value.trim() || '',
        icon: draft?.querySelector('[data-menu-draft-icon]')?.value || '',
        target: draft?.querySelector('[data-menu-draft-target]')?.value || '_self',
    });
    const hasDraftValues = (values) => values.label !== '' || values.url !== '' || values.icon !== '';

    const syncRowSummary = (row) => {
        const label = row.querySelector('[data-menu-item-label]');
        const url = row.querySelector('[data-menu-item-url]');
        const labelValue = row.querySelector('[data-menu-label-input]')?.value.trim() || '';
        const urlValue = row.querySelector('[data-menu-url-input]')?.value.trim() || '';
        if (label) {
            label.textContent = labelValue || t('menu.add_item', 'Add item');
        }
        if (url) {
            url.textContent = urlValue;
            url.hidden = urlValue === '';
        }
    };

    const setItemOpen = (row, open) => {
        const details = row.querySelector('[data-menu-item-details]');
        const toggle = row.querySelector('[data-menu-item-toggle]');
        if (details) {
            details.hidden = !open;
        }
        if (toggle) {
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        }
        row.classList.toggle('is-open', open);

        if (open) {
            customSelect?.init(row);
        }
    };

    const clearDraft = () => {
        if (!draft) {
            return;
        }
        const label = draft.querySelector('[data-menu-draft-label]');
        const url = draft.querySelector('[data-menu-draft-url]');
        const icon = draft.querySelector('[data-menu-draft-icon]');
        if (label) {
            label.value = '';
        }
        if (url) {
            url.value = '';
        }
        if (icon) {
            icon.value = '';
        }
        setSelectValue(draft.querySelector('[data-menu-draft-target]'), '_self');
        syncIconPreview(draft);
    };

    const fillRow = (row, values) => {
        const label = row.querySelector('input[name="item_label[]"]');
        const url = row.querySelector('input[name="item_url[]"]');
        const icon = row.querySelector('input[name="item_icon[]"]');
        if (label) {
            label.value = values.label;
        }
        if (url) {
            url.value = values.url;
        }
        if (icon) {
            icon.value = values.icon;
        }
        setSelectValue(row.querySelector('select[name="item_target[]"]'), values.target);
        syncIconPreview(row);
    };

    const syncRows = () => {
        const currentRows = rows();
        currentRows.forEach((row, index) => {
            syncIconPreview(row);
            syncRowSummary(row);

            const up = row.querySelector('[data-menu-item-up]');
            const down = row.querySelector('[data-menu-item-down]');
            if (up) {
                up.disabled = index === 0;
            }
            if (down) {
                down.disabled = index === currentRows.length - 1;
            }
        });

        if (empty) {
            empty.hidden = currentRows.length > 0;
        }
        if (count) {
            count.textContent = String(currentRows.length);
        }
    };

    const addRow = () => {
        const values = draftValues();
        const fragment = template.content.cloneNode(true);
        items.appendChild(fragment);
        const last = rows().at(-1);
        if (last) {
            fillRow(last, values);
            setItemOpen(last, true);
        }
        customSelect?.init(last || document);
        syncRows();
        clearDraft();
        setAddOpen(false);
    };

    const moveRow = (row, direction) => {
        if (direction < 0 && row.previousElementSibling) {
            items.insertBefore(row, row.previousElementSibling);
        }

        if (direction > 0 && row.nextElementSibling) {
            items.insertBefore(row.nextElementSibling, row);
        }
    };

    addButton?.addEventListener('click', addRow);

    draft?.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' && event.target instanceof HTMLInputElement) {
            event.preventDefault();
            addRow();
        }
    });

    root.addEventListener('submit', () => {
        const values = draftValues();
        if (hasDraftValues(values)) {
            addRow();
        }
    });

    root.addEventListener('click', (event) => {
        if (!(event.target instanceof Element)) {
            return;
        }

        if (event.target.closest('[data-menu-add-toggle]')) {
            setAddOpen(draft?.querySelector('[data-menu-add-form]')?.hidden !== false);
            return;
        }

        if (event.target.closest('[data-menu-add-cancel]')) {
            clearDraft();
            setAddOpen(false);
            return;
        }

        const option = event.target.closest('[data-menu-icon-option]');
        if (option) {
            const picker = option.closest('[data-menu-icon-picker]');
            const scope = option.closest('[data-menu-item], [data-menu-draft]');
            const input = picker?.querySelector('[data-menu-icon-value]');
            if (input) {
                input.value = String(option.dataset.icon || '');
                syncIconPreview(scope || picker);
            }
            closeIconPickers();
            return;
        }

        const trigger = event.target.closest('[data-menu-icon-trigger]');
        if (trigger) {
            const picker = trigger.closest('[data-menu-icon-picker]');
            const options = picker?.querySelector('[data-menu-icon-options]');
            if (options) {
                const willOpen = options.hidden;
                closeIconPickers(picker);
                options.hidden = !willOpen;
                trigger.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
            }
            return;
        }

        const row = event.target.closest('[data-menu-item]');
        if (!row) {
            return;
        }

        if (event.target.closest('[data-menu-item-toggle]')) {
            setItemOpen(row, row.querySelector('[data-menu-item-details]')?.hidden !== false);
            return;
        }

        if (event.target.closest('[data-menu-item-remove]')) {
            row.remove();
            syncRows();
            return;
        }

        if (event.target.closest('[data-menu-item-up]')) {
            moveRow(row, -1);
            syncRows();
            return;
        }

        if (event.target.closest('[data-menu-item-down]')) {
            moveRow(row, 1);
            syncRows();
        }
    });

    root.addEventListener('input', (event) => {
        if (!(event.target instanceof Element) || !event.target.matches('[data-menu-label-input], [data-menu-url-input]')) {
            return;
        }

        const row = event.target.closest('[data-menu-item]');
        if (row) {
            syncRowSummary(row);
        }
    });

    document.addEventListener('click', (event) => {
        if (!(event.target instanceof Element) || event.target.closest('[data-menu-icon-picker]')) {
            return;
        }

        closeIconPickers();
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeIconPickers();
        }
    });

    syncRows();
})();
