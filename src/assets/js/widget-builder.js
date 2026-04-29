(() => {
    const app = window.tinycms = window.tinycms || {};
    const root = document.querySelector('[data-widget-builder]');
    if (!root) {
        return;
    }

    const customSelect = app.ui?.customSelect;

    const rows = () => Array.from(root.querySelectorAll('[data-widget-item]'));
    const areas = () => Array.from(root.querySelectorAll('[data-widget-area]'));

    const escapeSelector = (value) => {
        if (window.CSS && typeof window.CSS.escape === 'function') {
            return window.CSS.escape(value);
        }

        return String(value).replace(/["\\]/g, '\\$&');
    };

    const renumber = () => {
        rows().forEach((row, index) => {
            const area = row.closest('[data-widget-area]')?.dataset.widgetArea || '';
            const areaInput = row.querySelector('[data-widget-item-area]');
            if (areaInput) {
                areaInput.value = area;
            }

            row.querySelectorAll('[name]').forEach((field) => {
                field.name = String(field.name).replace(/\[[^\]]+\]/, `[${index}]`);
            });
        });
    };

    const syncSummary = (row) => {
        const title = row.querySelector('[data-widget-item-title]');
        const input = row.querySelector('[data-widget-title-input]');
        if (!title) {
            return;
        }

        const value = String(input?.value || '').trim();
        title.textContent = value;
        title.hidden = value === '';
    };

    const setExpanded = (row, expanded) => {
        const details = row.querySelector('[data-widget-item-details]');
        const toggle = row.querySelector('[data-widget-item-toggle]');
        if (details) {
            details.hidden = !expanded;
        }
        if (toggle) {
            toggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');
        }
        row.classList.toggle('is-open', expanded);
    };

    const setAddOpen = (add, open) => {
        const form = add.querySelector('[data-widget-add-form]');
        const toggle = add.querySelector('[data-widget-add-toggle]');
        if (form) {
            form.hidden = !open;
        }
        if (toggle) {
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        }
        add.classList.toggle('is-open', open);

        if (open) {
            customSelect?.init(add);
            add.querySelector('[data-custom-select-button], [data-widget-add-select]')?.focus();
        }
    };

    const syncRows = () => {
        areas().forEach((area) => {
            const currentRows = Array.from(area.querySelectorAll('[data-widget-item]'));
            currentRows.forEach((row, index) => {
                syncSummary(row);
                const up = row.querySelector('[data-widget-item-up]');
                const down = row.querySelector('[data-widget-item-down]');
                if (up) {
                    up.disabled = index === 0;
                }
                if (down) {
                    down.disabled = index === currentRows.length - 1;
                }
            });

            const empty = area.querySelector('[data-widget-area-empty]');
            const count = area.querySelector('[data-widget-area-count]');
            if (empty) {
                empty.hidden = currentRows.length > 0;
            }
            if (count) {
                count.textContent = String(currentRows.length);
            }
        });
        renumber();
    };

    const addRow = (button) => {
        const add = button.closest('[data-widget-add-area]');
        const values = {
            area: add?.dataset.widgetAddArea || '',
            widget: add?.querySelector('[data-widget-add-select]')?.value || '',
        };
        if (values.area === '' || values.widget === '') {
            return;
        }

        const template = root.querySelector(`template[data-widget-template="${escapeSelector(values.widget)}"]`);
        if (!template) {
            return;
        }

        const targetArea = root.querySelector(`[data-widget-area="${escapeSelector(values.area)}"]`);
        const items = targetArea?.querySelector('[data-widget-items]');
        if (!items) {
            return;
        }

        items.appendChild(template.content.cloneNode(true));
        const row = Array.from(items.querySelectorAll('[data-widget-item]')).at(-1);
        const area = row?.querySelector('[data-widget-item-area]');
        if (area) {
            area.value = values.area;
        }

        customSelect?.init(row || document);
        if (row) {
            setExpanded(row, true);
        }
        if (add) {
            setAddOpen(add, false);
        }
        syncRows();
    };

    const moveRow = (row, direction) => {
        const items = row.closest('[data-widget-items]');
        if (!items) {
            return;
        }

        if (direction < 0 && row.previousElementSibling) {
            items.insertBefore(row, row.previousElementSibling);
        }

        if (direction > 0 && row.nextElementSibling) {
            items.insertBefore(row.nextElementSibling, row);
        }
    };

    root.addEventListener('submit', renumber);

    root.addEventListener('click', (event) => {
        if (!(event.target instanceof Element)) {
            return;
        }

        const addToggle = event.target.closest('[data-widget-add-toggle]');
        if (addToggle) {
            const add = addToggle.closest('[data-widget-add-area]');
            if (add) {
                setAddOpen(add, add.querySelector('[data-widget-add-form]')?.hidden !== false);
            }
            return;
        }

        const addCancel = event.target.closest('[data-widget-add-cancel]');
        if (addCancel) {
            const add = addCancel.closest('[data-widget-add-area]');
            if (add) {
                setAddOpen(add, false);
            }
            return;
        }

        const addButton = event.target.closest('[data-widget-add-item]');
        if (addButton) {
            addRow(addButton);
            return;
        }

        const row = event.target.closest('[data-widget-item]');
        if (!row) {
            return;
        }

        if (event.target.closest('[data-widget-item-toggle]')) {
            setExpanded(row, row.querySelector('[data-widget-item-details]')?.hidden !== false);
            return;
        }

        if (event.target.closest('[data-widget-item-remove]')) {
            row.remove();
            syncRows();
            return;
        }

        if (event.target.closest('[data-widget-item-up]')) {
            moveRow(row, -1);
            syncRows();
            return;
        }

        if (event.target.closest('[data-widget-item-down]')) {
            moveRow(row, 1);
            syncRows();
        }
    });

    root.addEventListener('input', (event) => {
        if (!(event.target instanceof Element) || !event.target.matches('[data-widget-title-input]')) {
            return;
        }

        const row = event.target.closest('[data-widget-item]');
        if (row) {
            syncSummary(row);
        }
    });

    syncRows();
})();
