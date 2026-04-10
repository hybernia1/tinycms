(() => {
    const submitButtons = document.querySelectorAll('[data-save-action-form-submit]');
    submitButtons.forEach((button) => {
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

    const menus = document.querySelectorAll('[data-save-action-menu]');
    menus.forEach((menu) => {
        const formSelector = String(menu.getAttribute('data-save-action-form') || '').trim();
        const form = formSelector !== '' ? document.querySelector(formSelector) : null;
        if (!form) {
            return;
        }

        const primaryButton = menu.querySelector('[data-save-action-primary]');
        const toggleButton = menu.querySelector('[data-save-action-toggle]');
        const options = menu.querySelector('.admin-header-action-options');
        const submitButtons = menu.querySelectorAll('[data-save-action-submit]');
        const deleteButton = menu.querySelector('[data-save-action-delete]');
        const deleteTriggerSelector = String(menu.getAttribute('data-save-action-delete-trigger') || '').trim();
        const deleteTrigger = deleteTriggerSelector !== '' ? document.querySelector(deleteTriggerSelector) : null;

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
                close();
                form.requestSubmit();
            });
        });

        if (deleteButton) {
            deleteButton.addEventListener('click', (event) => {
                close();
                if (!deleteTrigger) {
                    return;
                }
                event.preventDefault();
                deleteTrigger.click();
            });
        }

        close();
    });
})();
