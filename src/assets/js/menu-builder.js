(() => {
    const root = document.querySelector('[data-menu-builder]');
    if (!root) {
        return;
    }

    const items = root.querySelector('[data-menu-items]');
    const template = root.querySelector('[data-menu-item-template]');
    const empty = root.querySelector('[data-menu-empty]');
    const addButton = root.querySelector('[data-menu-add-item]');
    const iconBase = String(window.tinycms?.icons?.sprite?.() || window.tinycmsIconSprite || '');

    if (!items || !template) {
        return;
    }

    const rows = () => Array.from(items.querySelectorAll('[data-menu-item]'));
    const iconPickers = () => Array.from(root.querySelectorAll('[data-menu-icon-picker]'));
    const iconSvg = (name) => window.tinycms?.icons?.icon?.(name) || `<svg class="icon" aria-hidden="true"><use href="${iconBase}#icon-${name}"></use></svg>`;
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

    const syncIconPreview = (row) => {
        const input = row.querySelector('[data-menu-icon-value]');
        const preview = row.querySelector('[data-menu-icon-preview]');
        if (!input || !preview) {
            return;
        }

        const icon = String(input.value || '').replace(/[^a-z0-9_-]/gi, '');
        preview.innerHTML = iconSvg(icon || 'cancel');

        row.querySelectorAll('[data-menu-icon-option]').forEach((option) => {
            option.classList.toggle('selected', String(option.dataset.icon || '') === icon);
        });
    };

    const syncRows = () => {
        const currentRows = rows();
        currentRows.forEach((row, index) => {
            syncIconPreview(row);

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
        if (!(event.target instanceof Element)) {
            return;
        }

        const row = event.target.closest('[data-menu-item]');
        if (!row) {
            return;
        }

        const option = event.target.closest('[data-menu-icon-option]');
        if (option) {
            const input = row.querySelector('[data-menu-icon-value]');
            if (input) {
                input.value = String(option.dataset.icon || '');
                syncIconPreview(row);
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
