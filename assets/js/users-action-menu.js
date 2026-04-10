(() => {
    const menu = document.querySelector('[data-users-action-menu]');
    const form = document.querySelector('#users-editor-form');
    if (!menu || !form) {
        return;
    }

    const primaryButton = menu.querySelector('[data-users-action-primary]');
    const toggleButton = menu.querySelector('[data-users-action-toggle]');
    const options = menu.querySelector('.admin-header-action-options');
    const submitButtons = menu.querySelectorAll('[data-users-action-submit]');

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

    close();
})();
