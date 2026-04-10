(() => {
    const menu = document.querySelector('[data-content-action-menu]');
    const form = document.querySelector('#content-editor-form');
    if (!menu || !form) {
        return;
    }

    const statusField = form.querySelector('[data-content-status-hidden]');
    const statusLabel = menu.querySelector('[data-content-action-label]');
    const primaryButton = menu.querySelector('[data-content-action-primary]');
    const toggleButton = menu.querySelector('[data-content-action-toggle]');
    const options = menu.querySelector('.admin-header-action-options');
    const submits = menu.querySelectorAll('[data-content-action-submit]');
    const checks = menu.querySelectorAll('[data-content-action-check]');
    const deleteButton = menu.querySelector('[data-content-action-delete]');
    const deleteTrigger = document.querySelector('[data-content-delete-trigger]');
    const contentId = Number((form.querySelector('[data-content-id-hidden]') || {}).value || 0);

    if (!primaryButton || !toggleButton || !options) {
        return;
    }

    if (deleteButton && contentId <= 0) {
        deleteButton.hidden = true;
    }

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

    syncState(statusField ? statusField.value : 'draft');

    submits.forEach((button) => {
        button.addEventListener('click', () => {
            if (statusField) {
                statusField.value = button.getAttribute('data-content-action-submit') || 'draft';
            }
            syncState(statusField ? statusField.value : 'draft');
            close();
            form.requestSubmit();
        });
    });

    if (deleteButton && deleteTrigger) {
        deleteButton.addEventListener('click', () => {
            close();
            deleteTrigger.click();
        });
    }

    close();
})();
