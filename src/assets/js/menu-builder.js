(() => {
    const root = document.querySelector('[data-menu-builder]');
    if (!root) {
        return;
    }

    const items = root.querySelector('[data-menu-items]');
    const template = root.querySelector('[data-menu-item-template]');
    const empty = root.querySelector('[data-menu-empty]');
    const addButton = root.querySelector('[data-menu-add-item]');

    if (!items || !template) {
        return;
    }

    const rows = () => Array.from(items.querySelectorAll('[data-menu-item]'));

    const syncRows = () => {
        const currentRows = rows();
        currentRows.forEach((row, index) => {
            const position = row.querySelector('[data-menu-item-index]');
            if (position) {
                position.textContent = String(index + 1);
            }

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
    };

    const addRow = () => {
        const fragment = template.content.cloneNode(true);
        items.appendChild(fragment);
        syncRows();
        const last = rows().at(-1);
        window.tinycms?.ui?.customSelect?.init(last || document);
        last?.querySelector('input')?.focus();
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

    root.addEventListener('click', (event) => {
        const row = event.target.closest('[data-menu-item]');
        if (!row) {
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

    syncRows();
})();
