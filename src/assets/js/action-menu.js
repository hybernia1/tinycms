(() => {
    const app = window.tinycms = window.tinycms || {};
    const t = app.i18n?.t || ((key, fallback) => fallback || '');
    let openMenu = null;

    const closeMenu = (menu = openMenu) => {
        if (!menu) {
            return;
        }

        const toggle = menu.querySelector('[data-save-action-toggle], [data-content-action-toggle]');
        const options = menu.querySelector('.admin-header-action-options');
        menu.classList.remove('open');
        if (options) {
            options.hidden = true;
        }
        if (toggle) {
            toggle.setAttribute('aria-expanded', 'false');
        }
        if (openMenu === menu) {
            openMenu = null;
        }
    };

    const openSplitMenu = (menu, toggle, options) => {
        if (openMenu && openMenu !== menu) {
            closeMenu(openMenu);
        }

        openMenu = menu;
        menu.classList.add('open');
        options.hidden = false;
        toggle.setAttribute('aria-expanded', 'true');
    };

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

        primaryButton.addEventListener('click', () => {
            form.requestSubmit();
        });

        toggleButton.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();
            if (options.hidden) {
                openSplitMenu(menu, toggleButton, options);
                return;
            }
            closeMenu(menu);
        });

        submitButtons.forEach((button) => {
            button.addEventListener('click', () => {
                if (typeof onOptionClick === 'function') {
                    onOptionClick(button);
                }
                closeMenu(menu);
                form.requestSubmit();
            });
        });

        if (deleteButton) {
            deleteButton.addEventListener('click', () => {
                closeMenu(menu);
            });
        }

        closeMenu(menu);
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
            const key = String(value || 'draft');
            return t('content.statuses.' + key, key) || key;
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

    const handleOutside = (event) => {
        if (!openMenu || !(event.target instanceof Node) || openMenu.contains(event.target)) {
            return;
        }
        closeMenu(openMenu);
    };

    document.addEventListener('click', handleOutside, true);
    document.addEventListener('pointerdown', handleOutside, true);
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeMenu(openMenu);
        }
    });
})();
