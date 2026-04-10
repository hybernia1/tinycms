(() => {
    const menu = document.querySelector('[data-content-action-menu]');
    const form = document.querySelector('#content-editor-form');
    if (!menu || !form) {
        return;
    }

    const toggle = menu.querySelector('[data-content-action-toggle]');
    const options = menu.querySelector('.admin-header-action-options');
    const statusField = form.querySelector('[data-content-status-hidden]');
    const submits = menu.querySelectorAll('[data-content-action-submit]');
    const deleteButton = menu.querySelector('[data-content-action-delete]');
    const deleteTrigger = document.querySelector('[data-content-delete-trigger]');
    const contentId = Number((form.querySelector('[data-content-id-hidden]') || {}).value || 0);

    if (!toggle || !options) {
        return;
    }

    if (deleteButton && contentId <= 0) {
        deleteButton.hidden = true;
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
            form.requestSubmit();
        });
    });

    if (deleteButton && deleteTrigger) {
        deleteButton.addEventListener('click', () => {
            close();
            deleteTrigger.click();
        });
    }
})();
