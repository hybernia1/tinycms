(() => {
    const menu = document.querySelector('[data-media-action-menu]');
    const form = document.querySelector('#media-editor-form');
    if (!menu || !form) {
        return;
    }

    const primaryButton = menu.querySelector('[data-media-action-primary]');
    const toggleButton = menu.querySelector('[data-media-action-toggle]');
    const options = menu.querySelector('.admin-header-action-options');
    const submitButtons = menu.querySelectorAll('[data-media-action-submit]');
    const deleteButton = menu.querySelector('[data-media-action-delete]');
    const deleteTrigger = document.querySelector('[data-media-delete-trigger]');

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

    if (deleteButton && deleteTrigger) {
        deleteButton.addEventListener('click', () => {
            close();
            deleteTrigger.click();
        });
    }

    close();
})();
