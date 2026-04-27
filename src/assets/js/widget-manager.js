(() => {
    const root = document.querySelector('[data-widget-manager]');
    if (!root) {
        return;
    }

    const customSelect = window.tinycms?.ui?.customSelect;
    const instances = (area) => Array.from(area.querySelectorAll('[data-widget-instance]'));
    const randomId = () => `w_${Math.random().toString(16).slice(2)}${Date.now().toString(16)}`.slice(0, 18);
    const escSelector = (value) => window.CSS?.escape
        ? CSS.escape(value)
        : String(value).replace(/["\\]/g, '\\$&');

    const setName = (field, sidebar, index) => {
        const key = field.getAttribute('data-widget-input');
        const setting = field.getAttribute('data-widget-setting');
        if (setting) {
            field.name = `widgets[${sidebar}][${index}][settings][${setting}]`;
            return;
        }

        if (key === 'enabled-hidden' || key === 'enabled') {
            field.name = `widgets[${sidebar}][${index}][enabled]`;
            return;
        }

        if (key) {
            field.name = `widgets[${sidebar}][${index}][${key}]`;
        }
    };

    const syncArea = (area) => {
        const sidebar = area.getAttribute('data-widget-area') || '';
        const rows = instances(area);
        rows.forEach((row, index) => {
            row.querySelectorAll('[data-widget-input], [data-widget-setting]').forEach((field) => {
                setName(field, sidebar, index);
            });

            const id = row.querySelector('[data-widget-input="id"]');
            if (id && String(id.value || '').trim() === '') {
                id.value = randomId();
            }

            const up = row.querySelector('[data-widget-up]');
            const down = row.querySelector('[data-widget-down]');
            if (up) {
                up.disabled = index === 0;
            }
            if (down) {
                down.disabled = index === rows.length - 1;
            }
        });

        const count = area.querySelector('[data-widget-count]');
        if (count) {
            count.textContent = String(rows.length);
        }
    };

    const syncAll = () => {
        root.querySelectorAll('[data-widget-area]').forEach(syncArea);
    };

    const addInstance = (area) => {
        const select = area.querySelector('[data-widget-add-select]');
        const type = String(select?.value || '').trim();
        const template = root.querySelector(`template[data-widget-template="${escSelector(type)}"]`);
        const items = area.querySelector('[data-widget-items]');
        if (!template || !items) {
            return;
        }

        const fragment = template.content.cloneNode(true);
        items.appendChild(fragment);
        customSelect?.init(items.lastElementChild || items);
        syncArea(area);
    };

    root.addEventListener('click', (event) => {
        if (!(event.target instanceof Element)) {
            return;
        }

        const area = event.target.closest('[data-widget-area]');
        if (!area) {
            return;
        }

        if (event.target.closest('[data-widget-add]')) {
            addInstance(area);
            return;
        }

        const row = event.target.closest('[data-widget-instance]');
        if (!row) {
            return;
        }

        if (event.target.closest('[data-widget-remove]')) {
            row.remove();
            syncArea(area);
            return;
        }

        if (event.target.closest('[data-widget-up]') && row.previousElementSibling) {
            row.parentElement.insertBefore(row, row.previousElementSibling);
            syncArea(area);
            return;
        }

        if (event.target.closest('[data-widget-down]') && row.nextElementSibling) {
            row.parentElement.insertBefore(row.nextElementSibling, row);
            syncArea(area);
        }
    });

    syncAll();
})();
