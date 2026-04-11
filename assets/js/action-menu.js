(() => {
    const bindFormSubmitButtons = () => {
        document.querySelectorAll('[data-save-action-form-submit]').forEach((button) => {
            const formSelector = String(button.getAttribute('data-save-action-form-submit') || '').trim();
            if (formSelector === '') {
                return;
            }
            button.addEventListener('click', () => {
                const form = document.querySelector(formSelector);
                if (form) {
                    form.requestSubmit();
                }
            });
        });
    };

    const initSplitMenu = (menu, form, selectors, onOptionClick = null) => {
        if (!menu || !form) {
            return;
        }

        const primaryButton = menu.querySelector(selectors.primary);
        const toggleButton = menu.querySelector(selectors.toggle);
        const options = menu.querySelector('.admin-header-action-options');
        const submitButtons = menu.querySelectorAll(selectors.submit);
        const deleteButton = selectors.delete ? menu.querySelector(selectors.delete) : null;

        if (!primaryButton || !toggleButton || !options) {
            return;
        }

        const close = () => {
            menu.classList.remove('open');
            options.hidden = true;
            toggleButton.setAttribute('aria-expanded', 'false');
        };

        const open = () => {
            menu.classList.add('open');
            options.hidden = false;
            toggleButton.setAttribute('aria-expanded', 'true');
        };

        primaryButton.addEventListener('click', () => {
            form.requestSubmit();
        });

        toggleButton.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();
            if (options.hidden) {
                open();
                return;
            }
            close();
        });

        const handleOutside = (event) => {
            if (!menu.contains(event.target)) {
                close();
            }
        };

        document.addEventListener('click', handleOutside, true);
        document.addEventListener('pointerdown', handleOutside, true);
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                close();
            }
        });

        submitButtons.forEach((button) => {
            button.addEventListener('click', () => {
                if (typeof onOptionClick === 'function') {
                    onOptionClick(button);
                }
                close();
                form.requestSubmit();
            });
        });

        if (deleteButton) {
            deleteButton.addEventListener('click', () => {
                close();
            });
        }

        close();
    };

    const initSaveMenus = () => {
        document.querySelectorAll('[data-save-action-menu]').forEach((menu) => {
            const formSelector = String(menu.getAttribute('data-save-action-form') || '').trim();
            const form = formSelector !== '' ? document.querySelector(formSelector) : null;
            initSplitMenu(menu, form, {
                primary: '[data-save-action-primary]',
                toggle: '[data-save-action-toggle]',
                submit: '[data-save-action-submit]',
                delete: '[data-save-action-delete]',
            });
        });
    };

    const initContentMenu = () => {
        const menu = document.querySelector('[data-content-action-menu]');
        const form = document.querySelector('#content-editor-form');
        if (!menu || !form) {
            return;
        }

        const statusField = form.querySelector('[data-content-status-hidden]');
        const statusLabel = menu.querySelector('[data-content-action-label]');
        const checks = menu.querySelectorAll('[data-content-action-check]');

        const resolveStatusLabel = (value) => {
            const statuses = window.tinycmsI18n?.content?.statuses || {};
            const key = String(value || 'draft');
            return statuses[key] || key;
        };

        const syncState = (value) => {
            if (statusLabel) {
                statusLabel.textContent = resolveStatusLabel(value);
            }
            checks.forEach((node) => {
                node.hidden = node.getAttribute('data-content-action-check') !== value;
            });
        };

        syncState(statusField ? statusField.value : 'draft');

        initSplitMenu(menu, form, {
            primary: '[data-content-action-primary]',
            toggle: '[data-content-action-toggle]',
            submit: '[data-content-action-submit]',
            delete: '[data-content-action-delete]',
        }, (button) => {
            if (statusField) {
                statusField.value = button.getAttribute('data-content-action-submit') || 'draft';
                syncState(statusField.value);
            }
        });
    };

    bindFormSubmitButtons();
    initSaveMenus();
    initContentMenu();
})();
