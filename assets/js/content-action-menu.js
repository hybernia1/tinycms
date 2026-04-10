(() => {
    const menu = document.querySelector('[data-content-action-menu]');
    if (!menu) {
        return;
    }

    const toggle = menu.querySelector('[data-content-action-toggle]');
    const options = menu.querySelector('.content-action-menu-options');
    const statusField = document.querySelector('[data-content-status-hidden]');
    const submits = menu.querySelectorAll('[data-content-action-submit]');

    if (!toggle || !options) {
        return;
    }

    const close = () => {
        options.hidden = true;
        toggle.setAttribute('aria-expanded', 'false');
    };

    toggle.addEventListener('click', () => {
        const willOpen = options.hidden;
        options.hidden = !willOpen;
        toggle.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
    });

    document.addEventListener('click', (event) => {
        if (!menu.contains(event.target)) {
            close();
        }
    });

    submits.forEach((button) => {
        button.addEventListener('click', () => {
            if (statusField) {
                statusField.value = button.getAttribute('data-content-action-submit') || 'draft';
            }
            close();
        });
    });
})();
